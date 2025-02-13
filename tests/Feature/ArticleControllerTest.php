<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Article;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the index endpoint returns paginated articles for a guest.
     */
    public function test_index_returns_paginated_articles()
    {
        Article::factory()->count(15)->create();

        $response = $this->getJson('/api/articles');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                    'data',
                    'current_page',
                    'next_page_url',
                    'last_page_url',
                    'links',
                    'per_page',
                    'total',
                 ]);
    }

    /**
     * Test the index endpoint filters articles by search.
     */
    public function test_index_filters_by_search()
    {
        Article::factory()->create(['title' => 'Unique Article Title']);
        Article::factory()->create(['title' => 'Another Title']);

        $response = $this->getJson('/api/articles?search=Unique');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Unique Article Title', $data[0]['title']);
    }

    /**
     * Test the index endpoint applies user preferences.
     */
    public function test_index_applies_user_preferences()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // Create articles with different attributes.
        Article::factory()->create([
            'category' => 'Tech',
            'source'   => 'BBC News',
            'author'   => 'John Doe',
        ]);
        Article::factory()->create([
            'category' => 'Sports',
            'source'   => 'CNN',
            'author'   => 'Jane Smith',
        ]);

        // Set user preferences to only show Tech articles from BBC News by John Doe.
        $user->preferences()->create([
            'categories' => ['Tech'],
            'sources'    => ['BBC News'],
            'authors'    => ['John Doe'],
        ]);

        $response = $this->getJson('/api/articles?use_preferences=true');

        $response->assertStatus(200);
        $data = $response->json('data');
        // We expect only one article matching the preferences.
        $this->assertCount(1, $data);
        $this->assertEquals('Tech', $data[0]['category']);
        $this->assertEquals('BBC News', $data[0]['source']);
        $this->assertEquals('John Doe', $data[0]['author']);
    }

    /**
     * Test the filters endpoint returns distinct, non-empty values.
     */
    public function test_filters_endpoint_returns_distinct_values()
    {
        // Create some articles with overlapping values.
        Article::factory()->create([
            'category' => 'Tech',
            'source'   => 'BBC News',
            'author'   => 'John Doe',
        ]);
        Article::factory()->create([
            'category' => 'Health',
            'source'   => 'CNN',
            'author'   => 'Jane Smith',
        ]);
        Article::factory()->create([
            'category' => 'Tech',
            'source'   => 'BBC News',
            'author'   => 'John Doe',
        ]);

        $response = $this->getJson('/api/filters');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'categories',
                     'sources',
                     'authors',
                 ]);

        $data = $response->json();
        $this->assertContains('Tech', $data['categories']);
        $this->assertContains('Health', $data['categories']);
        $this->assertContains('BBC News', $data['sources']);
        $this->assertContains('CNN', $data['sources']);
        $this->assertContains('John Doe', $data['authors']);
        $this->assertContains('Jane Smith', $data['authors']);
    }
}
