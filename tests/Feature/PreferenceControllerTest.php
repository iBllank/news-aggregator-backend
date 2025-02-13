<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PreferenceControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that preferences can be stored for an authenticated user.
     */
    public function test_store_preferences_succeeds()
    {
        // Create a user and simulate authentication using Sanctum.
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $payload = [
            'categories' => ['Technology', 'Health'],
            'sources'    => ['BBC News', 'CNN'],
            'authors'    => ['John Doe', 'Jane Smith'],
        ];

        $response = $this->postJson('/api/preferences', $payload);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Preferences saved']);

        // Check that the preferences record exists in the database.
        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
        ]);

        // Optionally verify that the stored preferences match what we sent.
        $user->refresh();
        $this->assertEquals($payload['categories'], $user->preferences->categories);
        $this->assertEquals($payload['sources'], $user->preferences->sources);
        $this->assertEquals($payload['authors'], $user->preferences->authors);
    }

    /**
     * Test that the show endpoint returns the authenticated user's preferences.
     */
    public function test_show_preferences_returns_data()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // Create a preferences record for the user.
        $user->preferences()->create([
            'categories' => ['Technology', 'Health'],
            'sources'    => ['BBC News', 'CNN'],
            'authors'    => ['John Doe', 'Jane Smith'],
        ]);

        $response = $this->getJson('/api/preferences');

        $response->assertStatus(200)
                 ->assertJson([
                     'categories' => ['Technology', 'Health'],
                     'sources'    => ['BBC News', 'CNN'],
                     'authors'    => ['John Doe', 'Jane Smith'],
                 ]);
    }
}
