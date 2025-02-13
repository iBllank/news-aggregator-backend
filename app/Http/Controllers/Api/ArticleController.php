<?php

namespace App\Http\Controllers\Api;

use App\Models\Article;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth('sanctum')->id() ?? 'guest';
        $cacheKey = 'articles:' . md5(json_encode($request->all()) . $userId);

        return Cache::remember($cacheKey, 600, function () use ($request) {
            $query = Article::query();

            // Apply user preferences if available
            $this->applyPreferences($query, $request);

            // Apply additional filters from request
            $this->applyFilters($query, $request);

            return $query->latest()->paginate(10);
        });
    }

    /**
     * Apply user preference filters to the query.
     */
    protected function applyPreferences(&$query, Request $request): void
    {
        if (auth('sanctum')->check() && $request->boolean('use_preferences', true)) {
            $preferences = auth('sanctum')->user()->preferences;
            if ($preferences) {
                $filters = [
                    'categories' => 'category',
                    'sources'    => 'source',
                    'authors'    => 'author',
                ];

                foreach ($filters as $prefKey => $column) {
                    $values = $preferences->{$prefKey};
                    if (!empty($values)) {
                        $query->whereIn($column, $values);
                    }
                }
            }
        }
    }

    /**
     * Apply request filters to the query.
     */
    protected function applyFilters(&$query, Request $request): void
    {
        if ($request->filled('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        // Map request parameters to their respective columns
        $filterMap = [
            'category' => 'category',
            'source'   => 'source',
            'author'   => 'author',
        ];

        foreach ($filterMap as $param => $column) {
            if ($request->filled($param)) {
                $query->whereIn($column, (array) $request->get($param));
            }
        }

        if ($request->filled('date')) {
            $query->whereDate('published_at', $request->date);
        }
    }

    public function filters()
    {
        $filters = [
            'categories' => 'category',
            'sources'    => 'source',
            'authors'    => 'author',
        ];

        $data = [];
        foreach ($filters as $key => $column) {
            $data[$key] = $this->getDistinctValues($column);
        }

        return response()->json($data);
    }

    /**
     * Retrieve distinct, non-empty values for a given column.
     */
    protected function getDistinctValues(string $column)
    {
        return Article::whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->pluck($column);
    }

}
