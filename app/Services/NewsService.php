<?php

namespace app\Services;

use Carbon\Carbon;
use App\Models\Article;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;


class NewsService
{
    protected array $sources;

    public function __construct()
    {
        $this->sources = [
            'newsapi' => [
                'url'    => 'https://newsapi.org/v2/top-headlines',
                'params' => [
                    'country' => 'us',
                    'apiKey'  => config('app.news_api_key'),
                ],
                'map'    => [
                    'title'        => 'title',
                    'author'       => 'author',
                    'url'          => 'url',
                    'published_at' => 'publishedAt',
                    'source'       => 'source.name',
                    'category'     => 'category',
                    'content'      => 'content',
                    'image'        => 'urlToImage',
                ],
            ],
            'guardian' => [
                'url'    => 'https://content.guardianapis.com/search',
                'params' => [
                    'api-key'     => config('app.guardian_api_key'),
                    'show-fields' => 'thumbnail,bodyText',
                ],
                'map'    => [
                    'title'        => 'webTitle',
                    'author'       => '', // Not provided by Guardian
                    'url'          => 'webUrl',
                    'published_at' => 'webPublicationDate',
                    'source'       => 'The Guardian',
                    'category'     => 'sectionName',
                    'content'      => 'fields.bodyText',
                    'image'        => 'fields.thumbnail',
                ],
            ],
            'nytimes' => [
                'url'    => 'https://api.nytimes.com/svc/search/v2/articlesearch.json',
                'params' => [
                    'api-key' => config('app.nyt_api_key'),
                ],
                'map'    => [
                    'title'        => 'headline.main',
                    'url'          => 'web_url',
                    'published_at' => 'pub_date',
                    'source'       => 'The New York Times',
                    'author'       => 'byline.original',
                    'category'     => 'section_name',
                    'content'      => 'lead_paragraph',
                    'image'        => 'multimedia.0.url',
                ],
            ],
            // More sources can be added here.
        ];
    }

    /**
     * Fetch news from all sources.
     */
    public function fetchNews(): void
    {
        foreach ($this->sources as $sourceKey => $source) {
            $response = Http::get($source['url'], $source['params']);

            if ($response->failed()) {
                Log::error("Failed to fetch news from {$sourceKey}", ['response' => $response->body()]);
                continue;
            }

            Log::info("Fetched news from {$sourceKey}", ['response' => $response->json()]);
            $this->storeArticles($response->json(), $source['map']);
        }
    }

    /**
     * Convert a datetime string to MySQL datetime format.
     */
    protected function convertToMySQLDatetime($datetime)
    {
        return $datetime ? Carbon::parse($datetime)->format('Y-m-d H:i:s') : now();
    }

    /**
     * Validate the author field, ensuring it is not a URL.
     */
    private function validateAuthor($author)
    {
        return filter_var($author, FILTER_VALIDATE_URL) ? 'Unknown' : $author;
    }

    /**
     * Store articles from the API response.
     */
    protected function storeArticles(array $response, array $map): void
    {
        $articles = $response['articles']
            ?? $response['response']['results']
            ?? $response['response']['docs']
            ?? [];

        $maxUrlLength = 255;

        foreach ($articles as $article) {

            $rawUrl = data_get($article, $map['url']);

            // Skip the article if the URL is too long
            if (strlen($rawUrl) > $maxUrlLength) {
                Log::warning("Skipping article because URL exceeds {$maxUrlLength} characters: " . $rawUrl);
                continue;
            }

            Article::updateOrCreate(
                ['url' => $rawUrl],
                [
                    'title'        => data_get($article, $map['title']),
                    'author'       => $this->validateAuthor(data_get($article, $map['author'], 'Unknown')),
                    'published_at' => $this->convertToMySQLDatetime(data_get($article, $map['published_at'], now())),
                    'source'       => $article['source']['name'] ?? $map['source'] ?? 'Unknown',
                    'category'     => data_get($article, $map['category'], 'General'),
                    'content'      => $article['fields']['bodyText'] ?? data_get($article, $map['content'], null),
                    'image'        => $article['fields']['thumbnail'] ?? data_get($article, $map['image'], null),
                ]
            );
        }

        $this->clearArticleCache();
    }

    /**
     * Clear only article-related cache keys.
     */
    protected function clearArticleCache(): void
    {
        // Select the correct cache database (as configured, e.g., database 1)
        Redis::select(config('database.redis.cache.database') ?? 1);

        // Retrieve all keys from Redis in the current cache DB.
        $keys = Redis::keys('*');

        // Determine the cache prefix. If empty, default to 'laravel_database_'.
        $prefix = config('cache.prefix') ?: 'laravel_database_';

        // Filter keys that start with the article prefix.
        $articleKeys = array_filter($keys, fn($key) => str_starts_with($key, $prefix . 'articles:'));

        // Remove the prefix before calling Cache::forget() since Cache automatically adds it.
        foreach ($articleKeys as $articleKey) {
            $rawKey = str_replace($prefix, '', $articleKey);
            Cache::forget($rawKey);
        }
    }
}

