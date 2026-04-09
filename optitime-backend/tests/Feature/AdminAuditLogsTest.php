<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditLogsTest extends TestCase
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

    public function test_get_audit_logs_returns_paginated_shape(): void
    {
        $token = $this->adminToken();
        $response = $this->getJson('/api/admin/audit-logs', [
            'Authorization' => 'Bearer '.$token,
        ]);
        $response->assertOk();
        $response->assertJsonStructure([
            'current_page',
            'data',
            'per_page',
            'total',
        ]);
        $this->assertIsArray($response->json('data'));
    }
}
