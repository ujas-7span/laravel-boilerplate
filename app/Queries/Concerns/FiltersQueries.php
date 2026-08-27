<?php

namespace App\Queries\Concerns;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

/**
 * Handles dynamic filtering, date-range filtering, type-casting, and multi-column searching.
 */
trait FiltersQueries
{
    /**
     * Apply allowed filters.
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

            if (str_ends_with($field, '_after') || str_ends_with($field, '_from')) {
                $column = (string) preg_replace('/_(after|from)$/', '', $field);
                $this->query->where($column, '>=', $value);

                continue;
            }

            if (str_ends_with($field, '_before') || str_ends_with($field, '_to')) {
                $column = (string) preg_replace('/_(before|to)$/', '', $field);
                $this->query->where($column, '<=', $value);

                continue;
            }

            $castValue = $this->castFilterValue($column, $value);

            if (is_array($castValue)) {
                $this->query->whereIn($column, $castValue);
            } else {
                $this->query->where($column, $castValue);
            }
        }

        return $this;
    }

    /**
     * Cast filter string value to appropriate type based on model $casts.
     */
    protected function castFilterValue(string $column, mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => $this->castFilterValue($column, $v), $value);
        }

        $model = $this->query->getModel();
        $casts = $model->getCasts();
        $castType = $casts[$column] ?? null;

        if ($castType === 'boolean' || $castType === 'bool') {
            if ($value === 'true' || $value === '1' || $value === 1 || $value === true) {
                return true;
            }
            if ($value === 'false' || $value === '0' || $value === 0 || $value === false) {
                return false;
            }
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
