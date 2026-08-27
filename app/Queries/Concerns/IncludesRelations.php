<?php

namespace App\Queries\Concerns;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;

/**
 * Handles relationship eager loading, inclusion whitelisting, and nested relationship sparse fieldsets.
 */
trait IncludesRelations
{
    /**
     * Get explicitly requested includes from query params.
     *
     * @return array<string>
     */
    protected function getExplicitIncludes(): array
    {
        $includeParam = $this->params['include'] ?? null;
        $mediaParam = $this->params['media'] ?? null;

        $includes = [];

        if (! empty($includeParam)) {
            $parsed = is_array($includeParam)
                ? array_values($includeParam)
                : explode(',', (string) $includeParam);
            $includes = array_merge($includes, array_map('trim', $parsed));
        }

        if (! empty($mediaParam)) {
            $includes[] = 'media';
        }

        return array_values(array_unique($includes));
    }

    /**
     * Apply whitelisted relation eager loading with relation-level sparse fieldsets.
     */
    protected function applyIncludes(): static
    {
        $explicitIncludes = $this->getExplicitIncludes();
        $validIncludes = array_intersect($explicitIncludes, $this->allowedIncludes);

        foreach ($validIncludes as $include) {
            $relationFields = $this->getRelationFields($include);

            if (! empty($relationFields)) {
                $this->query->with([
                    $include => function (Relation $relationQuery) use ($relationFields): void {
                        $this->applyRelationFieldSelection($relationQuery, $relationFields);
                    },
                ]);
            } else {
                $this->query->with($include);
            }
        }

        return $this;
    }

    /**
     * Resolve requested sparse fields for a specific relation.
     *
     * @return array<string>
     */
    protected function getRelationFields(string $relation): array
    {
        $fieldsParam = $this->params['fields'] ?? null;
        $requested = [];

        if (is_array($fieldsParam) && isset($fieldsParam[$relation])) {
            $requested = is_array($fieldsParam[$relation])
                ? $fieldsParam[$relation]
                : explode(',', (string) $fieldsParam[$relation]);
        } elseif (is_string($fieldsParam)) {
            $parts = explode(',', $fieldsParam);
            $prefix = $relation . '.';
            foreach ($parts as $part) {
                $part = trim($part);
                if (str_starts_with($part, $prefix)) {
                    $requested[] = substr($part, strlen($prefix));
                }
            }
        }

        $requested = array_map('trim', $requested);
        $allowed = $this->allowedRelationFields[$relation] ?? [];

        return empty($allowed) ? $requested : array_intersect($requested, $allowed);
    }

    /**
     * Ensure required primary and foreign keys are included when selecting sparse relation columns.
     *
     * @param  array<string>  $fields
     */
    protected function applyRelationFieldSelection(Relation $relationQuery, array $fields): void
    {
        $relatedModel = $relationQuery->getRelated();
        $keyName = $relatedModel->getKeyName();

        if (! in_array($keyName, $fields, true)) {
            $fields[] = $keyName;
        }

        if ($relationQuery instanceof HasOneOrMany) {
            $foreignKey = $relationQuery->getForeignKeyName();
            if (! in_array($foreignKey, $fields, true)) {
                $fields[] = $foreignKey;
            }
        }

        if ($relationQuery instanceof MorphOneOrMany) {
            $morphType = $relationQuery->getMorphType();
            if (! in_array($morphType, $fields, true)) {
                $fields[] = $morphType;
            }
        }

        if ($relationQuery instanceof BelongsTo) {
            $ownerKey = $relationQuery->getOwnerKeyName();
            if (! in_array($ownerKey, $fields, true)) {
                $fields[] = $ownerKey;
            }
        }

        $relationQuery->select($fields);
    }
}
