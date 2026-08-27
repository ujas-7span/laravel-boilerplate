<?php

namespace App\Queries\Concerns;

/**
 * Handles multi-column sorting and default order.
 */
trait SortsQueries
{
    /**
     * Apply multi-column sorting.
     */
    protected function applySorts(): static
    {
        $sortParam = $this->params['sort'] ?? null;
        $allowedSorts = $this->resolveAllowedSorts();

        if (empty($sortParam)) {
            foreach ($this->defaultSort as $column => $direction) {
                $this->query->orderBy($column, $direction);
            }

            return $this;
        }

        $sorts = is_array($sortParam)
            ? array_values($sortParam)
            : explode(',', (string) $sortParam);

        $applied = false;

        foreach ($sorts as $sortField) {
            $sortField = trim($sortField);
            if (empty($sortField)) {
                continue;
            }

            $direction = 'asc';
            if (str_starts_with($sortField, '-')) {
                $direction = 'desc';
                $sortField = substr($sortField, 1);
            }

            if (in_array($sortField, $allowedSorts, true)) {
                $this->query->orderBy($sortField, $direction);
                $applied = true;
            }
        }

        if (! $applied) {
            foreach ($this->defaultSort as $column => $direction) {
                $this->query->orderBy($column, $direction);
            }
        }

        return $this;
    }

    /**
     * Resolve allowed sort columns from allowed fields, excluding $hidden.
     *
     * @return array<string>
     */
    protected function resolveAllowedSorts(): array
    {
        $hidden = $this->resolveHiddenFields();

        if (! empty($this->allowedSorts)) {
            return array_values(array_diff($this->allowedSorts, $hidden));
        }

        return $this->resolveAllowedFields();
    }
}
