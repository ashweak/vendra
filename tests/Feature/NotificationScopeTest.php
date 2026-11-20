<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\StaffAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_unread_notifications_are_filtered_per_business_in_the_database(): void
    {
        $user = User::factory()->create();
        $user->notify(new StaffAlert('A1', 'For business 1', null, 1));
        $user->notify(new StaffAlert('A2', 'For business 1', null, 1));
        $user->notify(new StaffAlert('B1', 'For business 2', null, 2));

        $this->assertSame(2, $user->unreadNotificationsFor(1)->count());
        $this->assertSame(1, $user->unreadNotificationsFor(2)->count());
        $this->assertSame(0, $user->unreadNotificationsFor(3)->count());

        $user->unreadNotificationsFor(1)->update(['read_at' => now()]);
        $this->assertSame(0, $user->unreadNotificationsFor(1)->count());
        $this->assertSame(1, $user->unreadNotificationsFor(2)->count());
    }
}
