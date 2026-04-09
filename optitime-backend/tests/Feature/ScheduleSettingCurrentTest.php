<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleSettingCurrentTest extends TestCase
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

    public function test_get_current_returns_nested_shape(): void
    {
        $token = $this->adminToken();
        $response = $this->getJson('/api/admin/schedule-settings/current', [
            'Authorization' => 'Bearer '.$token,
        ]);
        $response->assertOk();
        $response->assertJsonStructure([
            'id',
            'capacity_threshold',
            'day_start',
            'day_end',
            'slot_minutes',
            'gap_minutes',
            'max_daily_lectures',
            'hard_constraints',
            'soft_constraints',
            'study_days',
            'break_times',
            'room_constraints',
            'load_settings',
        ]);
        $this->assertIsArray($response->json('hard_constraints'));
        $this->assertIsArray($response->json('soft_constraints'));
    }

    public function test_put_current_round_trip(): void
    {
        $token = $this->adminToken();
        $get = $this->getJson('/api/admin/schedule-settings/current', [
            'Authorization' => 'Bearer '.$token,
        ]);
        $get->assertOk();
        $body = $get->json();
        $this->assertArrayHasKey('room_constraints', $body);
        $this->assertArrayHasKey('load_settings', $body);
        unset($body['id']);
        $body['capacity_threshold'] = 88;

        $put = $this->putJson('/api/admin/schedule-settings/current', $body, [
            'Authorization' => 'Bearer '.$token,
        ]);
        $put->assertOk();
        $put->assertJsonPath('capacity_threshold', 88);

        $again = $this->getJson('/api/admin/schedule-settings/current', [
            'Authorization' => 'Bearer '.$token,
        ]);
        $again->assertOk();
        $again->assertJsonPath('capacity_threshold', 88);
    }
}
