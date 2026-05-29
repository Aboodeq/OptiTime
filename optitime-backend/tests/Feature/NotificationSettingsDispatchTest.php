<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\User;
use App\Models\UserSetting;
use App\Services\NotificationDispatchService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSettingsDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_push_announcements_setting_blocks_lecture_request_submitted_notifications(): void
    {
        $user = User::factory()->create();
        UserSetting::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'push_announcements' => false,
                'push_system_alerts' => true,
                'email_schedule_updates' => true,
                'email_reminders' => true,
                'weekly_digest' => false,
            ]
        );

        app(NotificationDispatchService::class)->notifyUsers(
            [$user->id],
            'New lecture request',
            'Coordinator review needed.',
            'lecture_request_submitted'
        );

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $user->id,
            'type' => 'lecture_request_submitted',
        ]);
    }

    public function test_schedule_update_requires_alert_or_email_schedule_setting(): void
    {
        $blocked = User::factory()->create();
        UserSetting::query()->updateOrCreate(
            ['user_id' => $blocked->id],
            [
                'push_system_alerts' => false,
                'email_schedule_updates' => false,
                'push_announcements' => true,
                'email_reminders' => true,
                'weekly_digest' => false,
            ]
        );

        $allowed = User::factory()->create();
        UserSetting::query()->updateOrCreate(
            ['user_id' => $allowed->id],
            [
                'push_system_alerts' => false,
                'email_schedule_updates' => true,
                'push_announcements' => true,
                'email_reminders' => true,
                'weekly_digest' => false,
            ]
        );

        app(NotificationDispatchService::class)->notifyUsers(
            [$blocked->id, $allowed->id],
            'Schedule updated',
            'Your weekly schedule has changed.',
            'schedule_updated'
        );

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $blocked->id,
            'type' => 'schedule_updated',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $allowed->id,
            'type' => 'schedule_updated',
        ]);
        $this->assertSame(1, AppNotification::query()->where('type', 'schedule_updated')->count());
    }
}

