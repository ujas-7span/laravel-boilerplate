<?php

namespace App\Attributes;

use Attribute;

/**
 * Declare relationship dependencies directly on model accessor / computed attribute methods.
 *
 * When the accessor is requested via dynamic ?append=..., the query pipeline
 * automatically eager-loads these relations to eliminate N+1 queries, computes
 * the attribute value, and strips the relation from the response unless ?include= was requested.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class RequiresRelation
{
    /**
     * @var array<string>
     */
    public array $relations;

    /**
     * @param  string|array<string>  $relations  Relation name(s) with optional column constraints (e.g. 'tokens:id,name,tokenable_id,tokenable_type')
     */
    public function __construct(string|array $relations)
    {
        $this->relations = is_array($relations) ? $relations : [$relations];
    }
}
