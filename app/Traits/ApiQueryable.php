<?php

namespace App\Traits;

use Illuminate\Http\Request;
use App\Queries\QueryBuilder;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait to make any Eloquent Model instantly queryable via REST API query strings.
 *
 * Automatically pulls parameters from the current HTTP request if not explicitly provided.
 */
trait ApiQueryable
{
    /**
     * Create an API query builder pipeline for this model.
     * Automatically extracts parameters from the current HTTP request if not passed.
     *
     * @param  Request|array<string, mixed>|null  $requestOrParams
     * @return QueryBuilder<static>
     */
    public static function apiQuery(Request|array|null $requestOrParams = null): QueryBuilder
    {
        /** @var Builder<static> $query */
        $query = static::query();

        return QueryBuilder::for($query, $requestOrParams ?? request());
    }

    /**
     * Scope to apply API query pipeline on an existing query builder.
     * Automatically extracts parameters from the current HTTP request if not passed.
     *
     * @param  Builder<static>  $query
     * @param  Request|array<string, mixed>|null  $requestOrParams
     * @return QueryBuilder<static>
     */
    public function scopeApiQuery(Builder $query, Request|array|null $requestOrParams = null): QueryBuilder
    {
        return QueryBuilder::for($query, $requestOrParams ?? request());
    }
}
