<?php

namespace App\OpenApi\Extensions;

use ReflectionClass;
use ReflectionMethod;
use Illuminate\Support\Str;
use App\Attributes\RequiresRelation;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Database\Eloquent\Model;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;

/**
 * Universal Scramble OpenAPI Extension
 *
 * Automatically inspects Eloquent Models ($fillable, $hidden, $casts, $appends, #[RequiresRelation], $allowedIncludes)
 * and dynamically generates comprehensive OpenAPI query parameters (filter, search, sort, fields, include, append, pagination)
 * for all index and show controller endpoints across the entire application without requiring manual docblocks.
 */
class ApiQueryParametersExtension extends OperationExtension
{
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        // Only document query parameters for GET requests
        if (! in_array('GET', $routeInfo->route->methods(), true)) {
            return;
        }

        $model = $this->resolveModelFromRoute($routeInfo);

        if (! $model) {
            return;
        }

        $methodName = $routeInfo->methodName();

        if ($methodName === 'index') {
            $this->addIndexQueryParameters($operation, $model);
        } elseif ($methodName === 'show') {
            $this->addShowQueryParameters($operation, $model);
        }
    }

    /**
     * Resolve the Eloquent Model associated with the controller or route.
     */
    protected function resolveModelFromRoute(RouteInfo $routeInfo): ?Model
    {
        $controllerClass = $routeInfo->className();

        if (! $controllerClass) {
            return null;
        }

        $baseName = class_basename($controllerClass);
        $modelName = Str::replaceLast('Controller', '', $baseName);

        $possibleClasses = [
            "App\\Models\\{$modelName}",
            "App\\Models\\Api\\{$modelName}",
            "App\\Models\\V1\\{$modelName}",
        ];

        foreach ($possibleClasses as $modelClass) {
            if (class_exists($modelClass) && is_subclass_of($modelClass, Model::class)) {
                return new $modelClass;
            }
        }

        return null;
    }

    /**
     * Add full dynamic query parameters for collection index endpoints.
     */
    protected function addIndexQueryParameters(Operation $operation, Model $model): void
    {
        $hidden = array_unique(array_merge($model->getHidden(), ['password', 'remember_token']));
        $fillable = array_values(array_diff($model->getFillable(), $hidden));
        $casts = $model->getCasts();
        $keyName = $model->getKeyName();

        $parameters = [];

        // 1. Multi-Column Search
        $parameters[] = Parameter::make('search', 'query')
            ->description('Multi-column search across string fields (e.g. name, email).')
            ->setSchema(Schema::fromType(new StringType));

        // 2. Dynamic Column Filters
        foreach ($fillable as $column) {
            $parameters[] = Parameter::make("filter[{$column}]", 'query')
                ->description("Filter records by {$column}.")
                ->setSchema(Schema::fromType(new StringType));

            // Add date range filters for datetime/timestamp columns
            $castType = $casts[$column] ?? null;
            if (str_contains((string) $castType, 'date') || str_contains((string) $castType, 'time')) {
                $parameters[] = Parameter::make("filter[{$column}_after]", 'query')
                    ->description("Filter records where {$column} is on or after date (YYYY-MM-DD).")
                    ->setSchema(Schema::fromType(new StringType));

                $parameters[] = Parameter::make("filter[{$column}_before]", 'query')
                    ->description("Filter records where {$column} is on or before date (YYYY-MM-DD).")
                    ->setSchema(Schema::fromType(new StringType));
            }
        }

        // Timestamp date filters
        if ($model->usesTimestamps()) {
            $parameters[] = Parameter::make('filter[created_at_after]', 'query')
                ->description('Filter records created on or after date (YYYY-MM-DD).')
                ->setSchema(Schema::fromType(new StringType));

            $parameters[] = Parameter::make('filter[created_at_before]', 'query')
                ->description('Filter records created on or before date (YYYY-MM-DD).')
                ->setSchema(Schema::fromType(new StringType));
        }

        // 3. Multi-Column Sorting
        $sortColumns = array_values(array_unique(array_merge([$keyName], $fillable, $model->usesTimestamps() ? ['created_at', 'updated_at'] : [])));
        $sortExamples = array_map(fn ($col) => "-{$col},{$col}", array_slice($sortColumns, 0, 3));
        $parameters[] = Parameter::make('sort', 'query')
            ->description('Sort by column(s). Prefix with - for descending order. Available: ' . implode(', ', $sortColumns) . '. Example: ' . implode(', ', $sortExamples))
            ->setSchema(Schema::fromType(new StringType));

        // 4. Sparse Fieldsets (fields=id,name,email)
        $parameters[] = Parameter::make('fields', 'query')
            ->description('Comma-separated sparse fieldsets to select on the root model (e.g. ' . implode(',', array_slice($sortColumns, 0, 3)) . ').')
            ->setSchema(Schema::fromType(new StringType));

        // 5. Allowed Relation Includes & Relation Sparse Fields
        $includes = $this->resolveModelIncludes($model);
        if (! empty($includes)) {
            $parameters[] = Parameter::make('include', 'query')
                ->description('Comma-separated relationships to eager load without N+1. Available: ' . implode(', ', $includes))
                ->setSchema(Schema::fromType(new StringType));

            if (in_array('media', $includes, true)) {
                $tags = array_keys((array) config('media.tags', []));
                $parameters[] = Parameter::make('media', 'query')
                    ->description('Comma-separated media tags to eager-load in resource responses. Available: ' . implode(', ', $tags))
                    ->setSchema(Schema::fromType(new StringType));
            }

            foreach ($includes as $relation) {
                $parameters[] = Parameter::make("fields[{$relation}]", 'query')
                    ->description("Comma-separated sparse fieldsets for the '{$relation}' relationship.")
                    ->setSchema(Schema::fromType(new StringType));
            }
        }

        // 6. Computed Accessors Appends
        $appends = $this->resolveModelAppends($model);
        if (! empty($appends)) {
            $parameters[] = Parameter::make('append', 'query')
                ->description('Comma-separated computed accessors to append dynamically. Available: ' . implode(', ', $appends))
                ->setSchema(Schema::fromType(new StringType));
        }

        // 7. Pagination Controls
        $parameters[] = Parameter::make('per_page', 'query')
            ->description('Number of items per page (1-100). Default: 15.')
            ->setSchema(Schema::fromType(new IntegerType));

        $parameters[] = Parameter::make('limit', 'query')
            ->description('Set to -1 to retrieve all matching records in a single unpaginated response.')
            ->setSchema(Schema::fromType(new IntegerType));

        $parameters[] = Parameter::make('page', 'query')
            ->description('Page number for standard LengthAware pagination.')
            ->setSchema(Schema::fromType(new IntegerType));

        $parameters[] = Parameter::make('cursor', 'query')
            ->description('Cursor token for high-scale cursor pagination.')
            ->setSchema(Schema::fromType(new StringType));

        $operation->addParameters($parameters);
    }

    /**
     * Add dynamic query parameters for single-model show endpoints.
     */
    protected function addShowQueryParameters(Operation $operation, Model $model): void
    {
        $parameters = [];

        // Sparse Fieldsets
        $parameters[] = Parameter::make('fields', 'query')
            ->description('Comma-separated sparse fieldset columns to select on the model.')
            ->setSchema(Schema::fromType(new StringType));

        // Includes
        $includes = $this->resolveModelIncludes($model);
        if (! empty($includes)) {
            $parameters[] = Parameter::make('include', 'query')
                ->description('Comma-separated relationships to eager load. Available: ' . implode(', ', $includes))
                ->setSchema(Schema::fromType(new StringType));

            if (in_array('media', $includes, true)) {
                $tags = array_keys((array) config('media.tags', []));
                $parameters[] = Parameter::make('media', 'query')
                    ->description('Comma-separated media tags to eager-load in resource responses. Available: ' . implode(', ', $tags))
                    ->setSchema(Schema::fromType(new StringType));
            }

            foreach ($includes as $relation) {
                $parameters[] = Parameter::make("fields[{$relation}]", 'query')
                    ->description("Comma-separated sparse fieldsets for the '{$relation}' relationship.")
                    ->setSchema(Schema::fromType(new StringType));
            }
        }

        // Appends
        $appends = $this->resolveModelAppends($model);
        if (! empty($appends)) {
            $parameters[] = Parameter::make('append', 'query')
                ->description('Comma-separated computed accessors to append dynamically. Available: ' . implode(', ', $appends))
                ->setSchema(Schema::fromType(new StringType));
        }

        $operation->addParameters($parameters);
    }

    /**
     * Resolve allowed includes from model properties or relations.
     *
     * @return array<string>
     */
    protected function resolveModelIncludes(Model $model): array
    {
        if (property_exists($model, 'allowedIncludes') && is_array($model->allowedIncludes)) {
            return $model->allowedIncludes;
        }

        return [];
    }

    /**
     * Resolve available computed accessors directly from model accessors and #[RequiresRelation].
     *
     * @return array<string>
     */
    protected function resolveModelAppends(Model $model): array
    {
        $appends = property_exists($model, 'allowedAppends') && is_array($model->allowedAppends)
            ? $model->allowedAppends
            : $model->getAppends();

        $ignoredMethods = [
            'get_appends',
            'get_fillable',
            'get_hidden',
            'get_casts',
            'get_table',
            'get_key_name',
            'get_dates',
            'get_attributes',
            'use_factory',
            'api_query',
        ];

        // Discover accessors declared via methods
        $reflection = new ReflectionClass($model);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method) {
            $name = $method->getName();

            // Attribute method with RequiresRelation attribute
            if (! empty($method->getAttributes(RequiresRelation::class))) {
                $appends[] = Str::snake($name);

                continue;
            }

            // Modern Attribute accessor (return type Attribute)
            $returnType = (string) $method->getReturnType();
            if (str_contains($returnType, 'Attribute')) {
                $appends[] = Str::snake($name);

                continue;
            }

            // Classic getXAttribute methods
            if (str_starts_with($name, 'get') && str_ends_with($name, 'Attribute') && strlen($name) > 12) {
                $attr = substr($name, 3, -9);
                $appends[] = Str::snake($attr);
            }
        }

        $appends = array_diff($appends, $ignoredMethods);

        return array_values(array_unique(array_filter($appends)));
    }
}
