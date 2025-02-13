<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test registration with valid data.
     */
    public function test_registration_succeeds()
    {
        $payload = [
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => 'secret123',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    /**
     * Test registration fails when email is already taken.
     */
    public function test_registration_fails_with_duplicate_email()
    {
        User::factory()->create(['email' => 'duplicate@example.com']);

        $payload = [
            'name'     => 'Test Duplicate',
            'email'    => 'duplicate@example.com',
            'password' => 'secret123',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test login succeeds with correct credentials.
     */
    public function test_login_succeeds_with_valid_credentials()
    {
        $password = 'secret123';
        $user = User::factory()->create([
            'email'    => 'login@example.com',
            'password' => Hash::make($password),
        ]);

        $payload = [
            'email'    => 'login@example.com',
            'password' => $password,
        ];

        $response = $this->postJson('/api/login', $payload);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token']);
    }

    /**
     * Test login fails with invalid credentials.
     */
    public function test_login_fails_with_invalid_credentials()
    {
        $password = 'secret123';
        $user = User::factory()->create([
            'email'    => 'loginfail@example.com',
            'password' => Hash::make($password),
        ]);

        $payload = [
            'email'    => 'loginfail@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/api/login', $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }
}
