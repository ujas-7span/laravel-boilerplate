<?php

namespace App\Queries\Concerns;

/**
 * Handles root model sparse fieldsets and allowed column selection.
 */
trait SelectsFields
{
    /**
     * Apply root model sparse fieldset selection.
     */
    protected function applyFields(): static
    {
        $fieldsParam = $this->params['fields'] ?? null;

        if (empty($fieldsParam)) {
            return $this;
        }

        $rootFields = [];

        if (is_array($fieldsParam)) {
            $modelTable = $this->query->getModel()->getTable();
            if (isset($fieldsParam[$modelTable])) {
                $rootFields = is_array($fieldsParam[$modelTable])
                    ? $fieldsParam[$modelTable]
                    : explode(',', (string) $fieldsParam[$modelTable]);
            } elseif (isset($fieldsParam['root'])) {
                $rootFields = is_array($fieldsParam['root'])
                    ? $fieldsParam['root']
                    : explode(',', (string) $fieldsParam['root']);
            }
        } else {
            $allFields = explode(',', (string) $fieldsParam);
            foreach ($allFields as $field) {
                $field = trim($field);
                if (! str_contains($field, '.')) {
                    $rootFields[] = $field;
                }
            }
        }

        $rootFields = array_map('trim', $rootFields);
        $allowedFields = $this->resolveAllowedFields();
        $validFields = array_intersect($rootFields, $allowedFields);

        if (! empty($validFields)) {
            $keyName = $this->query->getModel()->getKeyName();
            if (! in_array($keyName, $validFields, true)) {
                $validFields[] = $keyName;
            }

            $this->query->select($validFields);
        }

        return $this;
    }

    /**
     * Resolve the list of hidden fields on the model to strictly exclude.
     *
     * @return array<string>
     */
    protected function resolveHiddenFields(): array
    {
        $model = $this->query->getModel();

        return array_unique(array_merge($model->getHidden(), ['password', 'remember_token']));
    }

    /**
     * Resolve allowed selectable fields from $fillable + primary key + timestamps, excluding $hidden.
     *
     * @return array<string>
     */
    protected function resolveAllowedFields(): array
    {
        $hidden = $this->resolveHiddenFields();

        if (! empty($this->allowedFields)) {
            return array_values(array_diff($this->allowedFields, $hidden));
        }

        $model = $this->query->getModel();
        $keyName = $model->getKeyName();
        $fillable = $model->getFillable();

        $defaults = array_unique(array_merge(
            [$keyName],
            $fillable,
            ['email_verified_at', 'created_at', 'updated_at']
        ));

        return array_values(array_diff($defaults, $hidden));
    }
}
