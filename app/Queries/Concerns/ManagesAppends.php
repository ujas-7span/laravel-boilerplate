<?php

namespace App\Queries\Concerns;

use ReflectionMethod;
use Illuminate\Support\Str;
use App\Attributes\RequiresRelation;
use Illuminate\Database\Eloquent\Model;

/**
 * Handles default and dynamic computed appends with automatic relationship discovery via #[RequiresRelation].
 */
trait ManagesAppends
{
    /**
     * Parse and apply default model $appends + dynamic ?append=... with automatic relationship eager loading.
     * Relation dependencies are discovered directly on the attribute function via #[RequiresRelation].
     */
    protected function applyAppends(): static
    {
        $model = $this->query->getModel();

        // 1. Always include default model $appends
        $defaultAppends = $model->getAppends();

        // 2. Parse requested dynamic appends from query string
        $appendParam = $this->params['append'] ?? ($this->params['appends'] ?? null);
        $dynamicAppends = [];

        if (! empty($appendParam)) {
            $raw = is_array($appendParam) ? array_values($appendParam) : explode(',', (string) $appendParam);
            $raw = array_map('trim', $raw);

            $dynamicAppends = ! empty($this->allowedAppends)
                ? array_intersect($raw, $this->allowedAppends)
                : $raw;
        }

        $this->resolvedAppends = array_values(array_unique(array_merge($defaultAppends, $dynamicAppends)));

        // 3. Auto-eager-load any relation dependencies declared via #[RequiresRelation]
        $explicitIncludes = $this->getExplicitIncludes();

        foreach ($this->resolvedAppends as $appendName) {
            $requiredRelations = $this->getRequiredRelationsForAppend($appendName);

            foreach ($requiredRelations as $relation) {
                $relationBase = explode(':', $relation)[0];

                // If not explicitly requested by user in ?include=, mark as internal so it gets stripped before response
                if (! in_array($relationBase, $explicitIncludes, true)) {
                    $this->internalIncludes[] = $relationBase;
                }

                // Eager load the required relationship automatically (Zero N+1)
                $this->query->with($relation);
            }
        }

        $this->internalIncludes = array_unique($this->internalIncludes);

        return $this;
    }

    /**
     * Discover relationship dependencies directly from the accessor method's #[RequiresRelation] attribute.
     *
     * @return array<string>
     */
    protected function getRequiredRelationsForAppend(string $appendName): array
    {
        $model = $this->query->getModel();

        // Check modern Attribute accessor method: function latestTokenName(): Attribute
        $methodName = Str::camel($appendName);
        if (method_exists($model, $methodName)) {
            $reflection = new ReflectionMethod($model, $methodName);
            $attributes = $reflection->getAttributes(RequiresRelation::class);
            if (! empty($attributes)) {
                /** @var RequiresRelation $instance */
                $instance = $attributes[0]->newInstance();

                return $instance->relations;
            }
        }

        // Check classic getXAttribute method: function getLatestTokenNameAttribute()
        $classicMethodName = 'get' . Str::studly($appendName) . 'Attribute';
        if (method_exists($model, $classicMethodName)) {
            $reflection = new ReflectionMethod($model, $classicMethodName);
            $attributes = $reflection->getAttributes(RequiresRelation::class);
            if (! empty($attributes)) {
                /** @var RequiresRelation $instance */
                $instance = $attributes[0]->newInstance();

                return $instance->relations;
            }
        }

        return [];
    }

    /**
     * Post-process retrieved model instances:
     * 1. Pre-evaluates all default and requested dynamic appends while relations are loaded in memory.
     * 2. Unsets relations loaded strictly for appends so raw relation data is never leaked in JSON.
     *
     * @param  iterable<mixed>  $items
     */
    public function processResults(iterable $items): void
    {
        foreach ($items as $item) {
            if (! $item instanceof Model) {
                continue;
            }

            // Pre-evaluate and set all resolved appends (default + dynamic)
            if (! empty($this->resolvedAppends)) {
                foreach ($this->resolvedAppends as $appendKey) {
                    $value = $item->{$appendKey};
                    $item->setAttribute($appendKey, $value);
                }
                $item->append($this->resolvedAppends);
            }

            // Strip relations loaded strictly for appends (not explicitly requested via ?include=)
            foreach ($this->internalIncludes as $internalRelation) {
                $item->unsetRelation($internalRelation);
            }
        }
    }
}
