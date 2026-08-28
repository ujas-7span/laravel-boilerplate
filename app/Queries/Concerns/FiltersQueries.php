<?php

namespace App\Queries\Concerns;

use Throwable;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

/**
 * Handles dynamic filtering, date-range filtering, strict type-casting coercion, and multi-column searching.
 */
trait FiltersQueries
{
    /**
     * Sentinel value indicating a filter failed type coercion.
     */
    protected const FILTER_INVALID = '__QUERY_FILTER_INVALID__';

    /**
     * Force the query to return zero rows.
     */
    public function forceEmptyResult(): static
    {
        $this->query->whereRaw('0 = 1');

        return $this;
    }

    /**
     * Apply allowed filters with strict type coercion and invalid input protection.
     */
    protected function applyFilters(): static
    {
        $filters = $this->params['filter'] ?? [];
        $allowedFilters = $this->resolveAllowedFilters();

        foreach ($allowedFilters as $allowedFilter) {
            if (isset($this->params[$allowedFilter]) && ! isset($filters[$allowedFilter])) {
                $filters[$allowedFilter] = $this->params[$allowedFilter];
            }
        }

        if (! is_array($filters) || empty($filters)) {
            return $this;
        }

        foreach ($filters as $field => $value) {
            if ($value === null || $value === '' || ! in_array($field, $allowedFilters, true)) {
                continue;
            }

            $customMethod = 'filter' . Str::studly($field);
            if (method_exists($this, $customMethod)) {
                $this->{$customMethod}($value);

                continue;
            }

            $column = $field;
            $operator = '=';

            if (str_ends_with($field, '_after') || str_ends_with($field, '_from')) {
                $column = (string) preg_replace('/_(after|from)$/', '', $field);
                $operator = '>=';
            } elseif (str_ends_with($field, '_before') || str_ends_with($field, '_to')) {
                $column = (string) preg_replace('/_(before|to)$/', '', $field);
                $operator = '<=';
            }

            $castValue = $this->castFilterValue($column, $value);

            if ($castValue === self::FILTER_INVALID) {
                $this->forceEmptyResult();

                return $this;
            }

            if (is_array($castValue)) {
                if (in_array(self::FILTER_INVALID, $castValue, true)) {
                    $this->forceEmptyResult();

                    return $this;
                }

                if ($operator === '=') {
                    $this->query->whereIn($column, $castValue);
                } else {
                    $this->query->where(function (Builder $sub) use ($column, $operator, $castValue): void {
                        foreach ($castValue as $val) {
                            $sub->orWhere($column, $operator, $val);
                        }
                    });
                }
            } else {
                $this->query->where($column, $operator, $castValue);
            }
        }

        return $this;
    }

    /**
     * Cast filter string value to appropriate type based on model $casts.
     * Returns FILTER_INVALID if the value fails type coercion.
     */
    protected function castFilterValue(string $column, mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => $this->castFilterValue($column, $v), $value);
        }

        $model = $this->query->getModel();
        $casts = $model->getCasts();
        $castType = strtolower((string) ($casts[$column] ?? ''));

        // Handle boolean casts
        if ($castType === 'boolean' || $castType === 'bool') {
            if ($value === 'true' || $value === '1' || $value === 1 || $value === true) {
                return true;
            }
            if ($value === 'false' || $value === '0' || $value === 0 || $value === false) {
                return false;
            }

            return self::FILTER_INVALID;
        }

        // Handle integer & timestamp casts
        if ($castType === 'int' || $castType === 'integer' || $castType === 'timestamp') {
            if (is_int($value)) {
                return $value;
            }
            if (is_string($value) && filter_var($value, FILTER_VALIDATE_INT) !== false) {
                return (int) $value;
            }

            return self::FILTER_INVALID;
        }

        // Handle float / decimal casts
        if ($castType === 'real' || $castType === 'float' || $castType === 'double' || str_starts_with($castType, 'decimal')) {
            if (is_numeric($value)) {
                return (float) $value;
            }

            return self::FILTER_INVALID;
        }

        // Handle date / datetime casts or date-named columns (_at, _date, timestamps)
        $isDateColumn = str_starts_with($castType, 'date')
            || str_starts_with($castType, 'immutable_date')
            || str_starts_with($castType, 'custom_datetime')
            || str_ends_with($column, '_at')
            || str_ends_with($column, '_date')
            || in_array($column, ['created_at', 'updated_at', 'deleted_at'], true);

        if ($isDateColumn) {
            if (is_string($value) || is_numeric($value)) {
                try {
                    return Carbon::parse($value)->toDateTimeString();
                } catch (Throwable) {
                    return self::FILTER_INVALID;
                }
            }

            return self::FILTER_INVALID;
        }

        return $value;
    }

    /**
     * Apply multi-column search.
     */
    protected function applySearch(): static
    {
        $search = $this->params['search'] ?? ($this->params['filter']['search'] ?? null);
        $allowedSearchColumns = $this->resolveAllowedSearchColumns();

        if (empty($search) || empty($allowedSearchColumns)) {
            return $this;
        }

        $term = '%' . trim((string) $search) . '%';

        $this->query->where(function (Builder $subQuery) use ($term, $allowedSearchColumns): void {
            foreach ($allowedSearchColumns as $index => $column) {
                if ($index === 0) {
                    $subQuery->where($column, 'LIKE', $term);
                } else {
                    $subQuery->orWhere($column, 'LIKE', $term);
                }
            }
        });

        return $this;
    }

    /**
     * Resolve allowed filter columns from allowed fields + date range variations (_after, _before, _from, _to).
     *
     * @return array<string>
     */
    protected function resolveAllowedFilters(): array
    {
        $hidden = $this->resolveHiddenFields();

        if (! empty($this->allowedFilters)) {
            return array_values(array_diff($this->allowedFilters, $hidden));
        }

        $fields = $this->resolveAllowedFields();
        $filters = $fields;

        foreach ($fields as $field) {
            $filters[] = $field . '_after';
            $filters[] = $field . '_before';
            $filters[] = $field . '_from';
            $filters[] = $field . '_to';
        }

        return array_values(array_diff($filters, $hidden));
    }

    /**
     * Resolve allowed search columns across string-like fillable fields, excluding $hidden.
     *
     * @return array<string>
     */
    protected function resolveAllowedSearchColumns(): array
    {
        $hidden = $this->resolveHiddenFields();

        if (! empty($this->allowedSearchColumns)) {
            return array_values(array_diff($this->allowedSearchColumns, $hidden));
        }

        $fields = $this->resolveAllowedFields();
        $commonSearchables = ['name', 'email', 'title', 'description', 'username', 'first_name', 'last_name'];
        $searchable = array_filter($fields, fn (string $col): bool => in_array($col, $commonSearchables, true));

        return ! empty($searchable) ? array_values($searchable) : $fields;
    }
}
