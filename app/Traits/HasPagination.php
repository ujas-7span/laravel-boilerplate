<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Reusable Pagination Trait for Controllers, Resources, and Services.
 *
 * Provides standardized pagination metadata formatting, per-page resolution,
 * and paginated API response helpers for LengthAware and Cursor paginators.
 */
trait HasPagination
{
    /**
     * Default items per page if not specified in request.
     */
    protected int $defaultPerPage = 15;

    /**
     * Maximum allowed items per page to prevent memory exhaustion.
     */
    protected int $maxPerPage = 100;

    /**
     * Resolve the requested per_page parameter from request within bounds.
     */
    public function getPerPage(?int $default = null, ?int $max = null): int
    {
        $default = $default ?? config('site.pagination.per_page', $this->defaultPerPage);
        $max = $max ?? config('site.pagination.max_per_page', $this->maxPerPage);

        $requested = (int) request('per_page', $default);

        return max(1, min($requested, $max));
    }

    /**
     * Format paginator metadata into a clean array envelope.
     *
     * @return array<string, mixed>
     */
    public function formatPagination(LengthAwarePaginator|CursorPaginator|Paginator $paginator): array
    {
        $count = method_exists($paginator, 'count') ? $paginator->count() : count($paginator->items());

        $meta = [
            'per_page' => $paginator->perPage(),
            'count' => $count,
            'has_more_pages' => $paginator->hasMorePages(),
        ];

        if ($paginator instanceof LengthAwarePaginator) {
            $meta = array_merge($meta, [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
            ]);
        } elseif ($paginator instanceof CursorPaginator) {
            $meta = array_merge($meta, [
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
            ]);
        }

        return $meta;
    }

    /**
     * Return a standardized paginated JSON response with resource transformations.
     *
     * @param  class-string|null  $resourceClass
     */
    protected function paginatedResponse(
        LengthAwarePaginator|CursorPaginator|Paginator $paginator,
        ?string $resourceClass = null,
        string $message = 'Records retrieved successfully'
    ): JsonResponse {
        $items = $paginator->items();

        if ($resourceClass !== null) {
            $items = $resourceClass::collection($items);
        }

        $meta = [
            'pagination' => $this->formatPagination($paginator),
        ];

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $items,
            'meta' => [
                'pagination' => $this->formatPagination($paginator),
            ],
        ], Response::HTTP_OK);
    }
}
