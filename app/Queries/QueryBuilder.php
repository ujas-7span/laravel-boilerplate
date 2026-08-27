<?php

namespace App\Queries;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Queries\Concerns\SortsQueries;
use App\Queries\Concerns\SelectsFields;
use Illuminate\Database\Eloquent\Model;
use App\Queries\Concerns\FiltersQueries;
use App\Queries\Concerns\ManagesAppends;
use Illuminate\Database\Eloquent\Builder;
use App\Queries\Concerns\IncludesRelations;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as ConcreteLengthAwarePaginator;

/**
 * Universal, high-performance, type-safe Eloquent query builder pipeline.
 *
 * Decomposed into dedicated concern traits for easy debugging and maintenance:
 * - SelectsFields: Sparse fieldsets & column whitelisting
 * - ManagesAppends: Dynamic accessors & #[RequiresRelation] N+1 resolution
 * - IncludesRelations: Eager-loading & nested relation fieldsets
 * - FiltersQueries: Dynamic filters, date ranges, & full-text search
 * - SortsQueries: Multi-column sorting & default order
 *
 * @template TModel of Model
 */
class QueryBuilder
{
    use FiltersQueries;
    use IncludesRelations;
    use ManagesAppends;
    use SelectsFields;
    use SortsQueries;

    /**
     * @var Builder<TModel>
     */
    protected Builder $query;

    /**
     * @var array<string, mixed>
     */
    protected array $params = [];

    /**
     * Optional explicit override for allowed filter columns.
     *
     * @var array<string>
     */
    protected array $allowedFilters = [];

    /**
     * Optional explicit override for multi-column search columns.
     *
     * @var array<string>
     */
    protected array $allowedSearchColumns = [];

    /**
     * Optional explicit override for allowed sort columns.
     *
     * @var array<string>
     */
    protected array $allowedSorts = [];

    /**
     * Default sort column and direction.
     *
     * @var array<string, string>
     */
    protected array $defaultSort = ['created_at' => 'desc'];

    /**
     * Relationships allowed for eager loading (Prevents N+1).
     *
     * @var array<string>
     */
    protected array $allowedIncludes = [];

    /**
     * Optional explicit override for allowed sparse fieldset columns.
     *
     * @var array<string>
     */
    protected array $allowedFields = [];

    /**
     * Allowed sparse fieldsets for relationships.
     * Format: ['relationName' => ['column1', 'column2', ...]]
     *
     * @var array<string, array<string>>
     */
    protected array $allowedRelationFields = [];

    /**
     * Optional explicit override for allowed dynamic appends.
     *
     * @var array<string>
     */
    protected array $allowedAppends = [];

    /**
     * All appends (default model $appends + valid requested dynamic appends) to compute.
     *
     * @var array<string>
     */
    protected array $resolvedAppends = [];

    /**
     * Relationships loaded internally only for computing appends.
     *
     * @var array<string>
     */
    protected array $internalIncludes = [];

    /**
     * Max items per page.
     */
    protected int $maxPerPage = 100;

    /**
     * Default items per page.
     */
    protected int $defaultPerPage = 15;

    /**
     * @param  Builder<TModel>  $query
     * @param  Request|array<string, mixed>|null  $requestOrParams
     */
    public function __construct(Builder $query, Request|array|null $requestOrParams = null)
    {
        $this->query = $query;
        $resolved = $requestOrParams ?? request();
        $this->params = $resolved instanceof Request ? $resolved->all() : (array) $resolved;

        $this->initializeFromModel();
    }

    /**
     * Read configuration overrides directly from the model if defined.
     */
    protected function initializeFromModel(): void
    {
        $model = $this->query->getModel();

        $properties = [
            'allowedFilters',
            'allowedSearchColumns',
            'allowedSorts',
            'defaultSort',
            'allowedIncludes',
            'allowedFields',
            'allowedRelationFields',
            'allowedAppends',
            'maxPerPage',
            'defaultPerPage',
        ];

        foreach ($properties as $prop) {
            if (property_exists($model, $prop)) {
                /** @var mixed $val */
                $val = $model->{$prop};
                if (! empty($val)) {
                    $this->{$prop} = $val;
                }
            }
        }
    }

    /**
     * Static factory helper.
     *
     * @template T of Model
     *
     * @param  Builder<T>  $query
     * @param  Request|array<string, mixed>|null  $requestOrParams
     * @return self<T>
     */
    public static function for(Builder $query, Request|array|null $requestOrParams = null): self
    {
        return new self($query, $requestOrParams);
    }

    /**
     * Apply all query modifications and return the underlying Builder.
     *
     * @return Builder<TModel>
     */
    public function build(): Builder
    {
        $this->applyFields()
            ->applyAppends()
            ->applyIncludes()
            ->applyFilters()
            ->applySearch()
            ->applySorts();

        return $this->query;
    }

    /**
     * Paginate the query using LengthAware or Cursor pagination and process appends/relations.
     */
    public function paginate(?int $perPage = null, bool $useCursor = false): LengthAwarePaginator|CursorPaginator
    {
        $this->build();

        $requestedLimit = $perPage ?? $this->params['limit'] ?? $this->params['per_page'] ?? null;

        // Support limit=-1 or per_page=-1 to fetch all records in a single paginator envelope
        if ($requestedLimit !== null && (int) $requestedLimit === -1) {
            $total = (clone $this->query)->count();
            $items = $this->query->get();
            $this->processResults($items);

            return new ConcreteLengthAwarePaginator(
                items: $items,
                total: $total,
                perPage: $total > 0 ? $total : 1,
                currentPage: 1,
                options: [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]
            );
        }

        $perPage = $this->resolvePerPage($perPage);

        $paginator = ($useCursor || isset($this->params['cursor']))
            ? $this->query->cursorPaginate($perPage)
            : $this->query->paginate($perPage);

        $this->processResults($paginator->items());

        return $paginator;
    }

    /**
     * Get all results and process appends/relations.
     *
     * @return Collection<int, TModel>
     */
    public function get(): Collection
    {
        $this->build();

        /** @var Collection<int, TModel> $results */
        $results = $this->query->get();

        $this->processResults($results);

        return $results;
    }

    /**
     * Find a model by primary key with pipeline processing (fields, includes, appends).
     *
     * @return TModel|null
     */
    public function find(mixed $id): ?Model
    {
        $this->build();
        $keyName = $this->query->getModel()->getQualifiedKeyName();

        /** @var TModel|null $item */
        $item = $this->query->where($keyName, $id)->first();

        if ($item) {
            $this->processResults([$item]);
        }

        return $item;
    }

    /**
     * Find a model by primary key or throw ModelNotFoundException.
     *
     * @return TModel
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(mixed $id): Model
    {
        $this->build();
        $keyName = $this->query->getModel()->getQualifiedKeyName();

        /** @var TModel $item */
        $item = $this->query->where($keyName, $id)->firstOrFail();

        $this->processResults([$item]);

        return $item;
    }

    /**
     * Execute the query and get the first result with pipeline processing.
     *
     * @return TModel|null
     */
    public function first(): ?Model
    {
        $this->build();

        /** @var TModel|null $item */
        $item = $this->query->first();

        if ($item) {
            $this->processResults([$item]);
        }

        return $item;
    }

    /**
     * Execute the query and get the first result or throw ModelNotFoundException.
     *
     * @return TModel
     *
     * @throws ModelNotFoundException
     */
    public function firstOrFail(): Model
    {
        $this->build();

        /** @var TModel $item */
        $item = $this->query->firstOrFail();

        $this->processResults([$item]);

        return $item;
    }

    /**
     * Resolve the requested per_page parameter within bounds.
     */
    protected function resolvePerPage(?int $perPage = null): int
    {
        $requested = (int) ($perPage ?? $this->params['per_page'] ?? $this->defaultPerPage);

        return max(1, min($requested, $this->maxPerPage));
    }
}
