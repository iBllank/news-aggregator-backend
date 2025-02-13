<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Mockery;
use Carbon\Carbon;
use App\Models\Article;
use App\Services\NewsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NewsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NewsService $newsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->newsService = new NewsService();
    }

    public function test_fetch_news_stores_articles_successfully()
    {
        Http::fake([
            'https://newsapi.org/*' => Http::response([
                'articles' => [[
                    'title' => 'Test News',
                    'author' => 'John Doe',
                    'url' => 'https://example.com/article',
                    'publishedAt' => '2024-02-13T12:00:00Z',
                    'source' => ['name' => 'NewsAPI'],
                    'category' => 'General',
                    'content' => 'This is a test content.',
                    'urlToImage' => 'https://example.com/image.jpg',
                ]]
            ], 200)
        ]);

        $this->newsService->fetchNews();

        $this->assertDatabaseHas('articles', [
            'title' => 'Test News',
            'author' => 'John Doe',
            'url' => 'https://example.com/article',
        ]);
    }

    public function test_store_articles_skips_long_urls()
    {
        $longUrl = 'https://' . str_repeat('long', 260) . '.com';

        $articles = [[
            'title' => 'Test News',
            'url' => $longUrl, // Too long
            'author' => 'John Doe',
        ]];

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn($message) => str_contains($message, $longUrl));

        // Use reflection to access the protected property
        $reflection = new \ReflectionClass(NewsService::class);
        $sourcesProperty = $reflection->getProperty('sources');
        $sourcesProperty->setAccessible(true);
        $sources = $sourcesProperty->getValue($this->newsService); // Get the actual sources array

        // Invoke the method with the correct arguments
        $method = $reflection->getMethod('storeArticles');
        $method->setAccessible(true);
        $method->invoke($this->newsService, ['articles' => $articles], $sources['newsapi']['map']);

        // Assert that the article was NOT inserted
        $this->assertDatabaseMissing('articles', ['title' => 'Test News']);
    }


    public function test_validate_author_handles_url_properly()
    {
        $reflection = new \ReflectionClass(NewsService::class);
        $method = $reflection->getMethod('validateAuthor');
        $method->setAccessible(true);

        $this->assertEquals('Unknown', $method->invoke($this->newsService, 'http://example.com'));
        $this->assertEquals('John Doe', $method->invoke($this->newsService, 'John Doe'));
    }

    public function test_convert_to_sql_datetime_formats_correctly()
    {
        $reflection = new \ReflectionClass(NewsService::class);
        $method = $reflection->getMethod('convertToMySQLDatetime');
        $method->setAccessible(true);

        $this->assertEquals('2024-02-13 12:00:00', $method->invoke($this->newsService, '2024-02-13T12:00:00Z'));
    }

    public function test_clear_article_cache_removes_only_article_keys()
    {
        Redis::shouldReceive('select')->once();
        Redis::shouldReceive('keys')->andReturn([
            'laravel_database_articles:1',
            'laravel_database_articles:2',
            'laravel_database_other:3'
        ]);
        Cache::shouldReceive('forget')->twice();

        $reflection = new \ReflectionClass(NewsService::class);
        $method = $reflection->getMethod('clearArticleCache');
        $method->setAccessible(true);
        $method->invoke($this->newsService);
    }
}
