<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\MedicineListing;
use App\Notifications\AnnouncementPublished;
use App\Notifications\ListingRequested;
use App\Services\InteractionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    public function test_backend_event_creates_a_stored_notification_with_a_working_link(): void
    {
        $listing = MedicineListing::factory()->create(['medicine_id' => $this->medicine()->id]);
        $interaction = app(InteractionService::class)->request($listing, $this->member());
        $donor = $listing->owner;

        $notification = $donor->notifications()->where('type', ListingRequested::class)->firstOrFail();
        $this->assertSame(route('messages.show', $interaction->conversation_id), $notification->data['url']);

        // Unread count in the header and dropdown
        $this->actingAs($donor)->get('/dashboard')->assertSee('Notifications, 1 unread');

        // Opening it marks it read and goes to the conversation.
        $this->actingAs($donor)->get(route('notifications.open', $notification->id))->assertRedirect($notification->data['url']);
        $this->assertNotNull($notification->fresh()->read_at);
        $this->actingAs($donor)->get('/dashboard')->assertSee('Notifications, 0 unread');
    }

    public function test_mark_one_and_mark_all_as_read(): void
    {
        $member = $this->member();
        foreach (range(1, 3) as $i) {
            app(InteractionService::class)->request(MedicineListing::factory()->create(['user_id' => $member->id, 'medicine_id' => $this->medicine()->id]), $this->member());
        }
        $this->assertSame(3, $member->unreadNotifications()->count());

        $first = $member->unreadNotifications()->first();
        $this->actingAs($member)->patch(route('notifications.read', $first->id));
        $this->assertSame(2, $member->unreadNotifications()->count());

        $this->actingAs($member)->get('/notifications?filter=unread')->assertOk();
        $this->actingAs($member)->patch(route('notifications.read-all'))->assertSessionHas('success');
        $this->assertSame(0, $member->unreadNotifications()->count());
        $this->actingAs($member)->get('/notifications')->assertOk()->assertSeeText('requested Napa 500');
    }

    public function test_notification_redirects_never_leave_the_site(): void
    {
        $member = $this->member();
        $member->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'test', 'data' => ['title' => 'x', 'body' => 'y', 'url' => 'https://evil.example.com']]);
        $id = $member->notifications()->first()->id;

        $this->actingAs($member)->get(route('notifications.open', $id))->assertRedirect(route('notifications.index'));
    }

    public function test_admin_announcement_reaches_the_chosen_audience(): void
    {
        $admin = $this->member();
        $admin->forceFill(['role' => Role::Admin])->save();
        $dhaka = $this->member();
        $sylhet = $this->member('Sylhet', 'Sylhet Sadar');

        $this->actingAs($admin)->post(route('admin.announcements.store'), ['title' => 'Safety reminder', 'body' => 'Check expiry dates at hand-over.', 'audience' => 'division', 'audience_division' => 'Sylhet'])
            ->assertSessionHas('success');

        $this->assertTrue($sylhet->notifications()->where('type', AnnouncementPublished::class)->exists());
        $this->assertFalse($dhaka->notifications()->where('type', AnnouncementPublished::class)->exists());
        $this->assertFalse($admin->notifications()->exists(), 'staff do not receive member announcements');
        $this->assertDatabaseHas('announcements', ['title' => 'Safety reminder', 'recipients_count' => 1]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'announcement.sent']);
    }
}
