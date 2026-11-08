<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\MedicineListing;
use App\Models\Report;
use App\Models\User;
use App\Models\VerificationSubmission;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Loads the full demo dataset and opens every GET page as the right kind of user,
 * so a broken view anywhere fails the build. Also checks every GET route is covered.
 */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->seedReferenceData();
        $this->seed(DemoSeeder::class);
    }

    private function urls(): array
    {
        $nadia = User::where('email', 'nadia@medimatch.test')->firstOrFail();
        $listing = $nadia->listings()->firstOrFail();
        $request = $nadia->medicineRequests()->firstOrFail();
        $conversation = Conversation::forUser($nadia)->firstOrFail();
        $public = MedicineListing::publiclyVisible()->firstOrFail();

        return [
            'guest' => ['/', '/how-it-works', '/safety', '/about', '/contact', '/privacy', '/terms', '/medicines', '/search?q=napa', "/medicines/{$public->id}", "/members/{$public->user_id}", '/login', '/register', '/forgot-password', '/reset-password/x', '/admin/login'],
            'member' => ['/dashboard', '/profile', '/profile/edit', '/my/listings', '/my/listings?status=review', '/my/listings/create', "/my/listings/{$listing->id}/edit", '/my/requests', '/my/requests/create', "/my/requests/{$request->id}", "/my/requests/{$request->id}/edit", '/saved', '/messages', "/messages/{$conversation->id}", '/notifications', '/history', '/verification', '/settings/privacy', '/settings/security', "/medicines/{$public->id}", '/search?q=napa&sort=nearest'],
            'unverified' => ['/verify-account'],
            'reviewer' => ['/admin', '/admin/verifications', '/admin/verifications/'.VerificationSubmission::where('user_id', $nadia->id)->firstOrFail()->id],
            'admin' => ['/admin', '/admin/users', '/admin/users?q=dhaka', "/admin/users/{$nadia->id}", '/admin/medicines', '/admin/listings', '/admin/listings?status=pending_review', "/admin/listings/{$listing->id}", '/admin/requests', '/admin/verifications', '/admin/reports', '/admin/reports/'.Report::firstOrFail()->id, '/admin/interactions', '/admin/partners', '/admin/announcements', '/admin/audit-logs', '/admin/settings'],
        ];
    }

    public function test_every_page_renders_for_the_right_user(): void
    {
        $users = [
            'guest' => null,
            'member' => User::where('email', 'nadia@medimatch.test')->firstOrFail(),
            'unverified' => User::factory()->unverified()->create(['location_id' => $this->location()->id]),
            'reviewer' => User::where('email', 'reviewer@medimatch.test')->firstOrFail(),
            'admin' => User::where('email', 'admin@medimatch.test')->firstOrFail(),
        ];

        foreach ($this->urls() as $who => $urls) {
            foreach ($urls as $url) {
                $users[$who] ? $this->actingAs($users[$who]) : auth()->logout();
                $response = $this->get($url);
                $this->assertSame(200, $response->status(), "{$who} {$url} returned {$response->status()}");
            }
        }
    }

    public function test_every_get_route_is_covered_by_the_smoke_test(): void
    {
        $covered = collect($this->urls())->flatten()->map(fn ($u) => strtok(ltrim($u, '/'), '?'))->all();

        $missing = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => in_array('GET', $r->methods(), true) && ! preg_match('#^(_|up$|storage|notifications/\{|admin/verifications/\{verification\}/document)#', $r->uri()))
            ->map(fn ($r) => $r->uri())
            ->reject(fn ($uri) => collect($covered)->contains(fn ($c) => preg_match('#^'.preg_replace('/\\\{[^}]+\\\}/', '[^/]+', preg_quote($uri === '/' ? '' : $uri, '#')).'$#', $c)))
            ->values()->all();

        $this->assertSame([], $missing, 'GET routes without a smoke test: '.implode(', ', $missing));
    }
}
