<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSetting;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function adminToken(): string
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@optitime.local',
            'password' => 'password',
        ]);
        $response->assertOk();

        return $response->json('token');
    }

    public function test_get_profile_settings_requires_auth(): void
    {
        $this->getJson('/api/profile/settings')->assertUnauthorized();
    }

    public function test_get_profile_settings_creates_row_and_returns_booleans(): void
    {
        $token = $this->adminToken();
        $user = User::query()->where('email', 'admin@optitime.local')->first();
        UserSetting::query()->where('user_id', $user->id)->delete();

        $response = $this->getJson('/api/profile/settings', [
            'Authorization' => 'Bearer '.$token,
        ]);
        $response->assertOk();
        $response->assertJsonPath('email_schedule_updates', true);
        $response->assertJsonPath('weekly_digest', false);

        $this->assertDatabaseHas('user_settings', ['user_id' => $user->id]);
    }

    public function test_put_profile_settings_updates_partial_fields(): void
    {
        $token = $this->adminToken();

        $response = $this->putJson(
            '/api/profile/settings',
            [
                'weekly_digest' => true,
                'email_reminders' => false,
            ],
            ['Authorization' => 'Bearer '.$token],
        );
        $response->assertOk();
        $response->assertJsonPath('weekly_digest', true);
        $response->assertJsonPath('email_reminders', false);
    }

    public function test_put_profile_settings_validates_types(): void
    {
        $token = $this->adminToken();

        $response = $this->putJson(
            '/api/profile/settings',
            [
                'weekly_digest' => 'not-a-bool',
            ],
            ['Authorization' => 'Bearer '.$token],
        );
        $response->assertStatus(422);
    }
}
