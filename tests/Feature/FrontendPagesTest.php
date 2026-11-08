<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Part 1 (frontend preview): every page renders and every form action is an
 * honest placeholder. Part 2 replaces the placeholder tests with real
 * authentication, validation and authorisation tests.
 */
class FrontendPagesTest extends TestCase
{
    public static function pages(): array
    {
        return [
            // Public
            'home' => ['/'], 'how it works' => ['/how-it-works'], 'safety' => ['/safety'], 'about' => ['/about'],
            'contact' => ['/contact'], 'privacy' => ['/privacy'], 'terms' => ['/terms'],
            'browse' => ['/medicines'], 'browse list view' => ['/medicines?view=list'], 'search' => ['/search?q=napa'],
            'search with filters' => ['/search?q=paracetamol&division=Dhaka&district=Dhaka&type[]=donation&form=Tablet&sort=newest'],
            'medicine details' => ['/medicines/1'], 'public profile' => ['/members/2'],
            // Auth
            'login' => ['/login'], 'register' => ['/register'], 'forgot password' => ['/forgot-password'],
            'reset password' => ['/reset-password/sample-token'], 'verify account' => ['/verify-account'], 'admin login' => ['/admin/login'],
            // Member
            'dashboard' => ['/dashboard'], 'profile' => ['/profile'], 'edit profile' => ['/profile/edit'],
            'my listings' => ['/my/listings'], 'my listings tab' => ['/my/listings?status=review'], 'create listing' => ['/my/listings/create'], 'edit listing' => ['/my/listings/4/edit'],
            'my requests' => ['/my/requests'], 'create request' => ['/my/requests/create'], 'request details' => ['/my/requests/1'], 'request no matches' => ['/my/requests/2'], 'edit request' => ['/my/requests/1/edit'],
            'saved' => ['/saved'], 'messages' => ['/messages'], 'conversation' => ['/messages/1'], 'notifications' => ['/notifications'], 'unread notifications' => ['/notifications?filter=unread'],
            'history' => ['/history'], 'verification center' => ['/verification'], 'privacy settings' => ['/settings/privacy'], 'security settings' => ['/settings/security'],
            // Admin
            'admin dashboard' => ['/admin'], 'admin users' => ['/admin/users'], 'admin user search' => ['/admin/users?q=dhaka&status=active'], 'admin user' => ['/admin/users/4'],
            'admin medicines' => ['/admin/medicines'], 'admin listings' => ['/admin/listings'], 'admin listing filter' => ['/admin/listings?status=pending_review'], 'admin listing review' => ['/admin/listings/8'],
            'admin requests' => ['/admin/requests'], 'admin verifications' => ['/admin/verifications'], 'admin verification review' => ['/admin/verifications/2'],
            'admin reports' => ['/admin/reports'], 'admin report' => ['/admin/reports/5'], 'admin closed report' => ['/admin/reports/1'], 'admin interactions' => ['/admin/interactions'],
            'admin partners' => ['/admin/partners'], 'admin announcements' => ['/admin/announcements'], 'admin audit logs' => ['/admin/audit-logs'], 'admin settings' => ['/admin/settings'],
        ];
    }

    #[DataProvider('pages')]
    public function test_page_renders(string $url): void
    {
        $this->get($url)->assertOk()->assertSee('Frontend preview');
    }

    public function test_every_get_route_is_covered_by_a_page_test(): void
    {
        $covered = collect(self::pages())->map(fn ($p) => strtok(ltrim($p[0], '/'), '?'))->all();

        $missing = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => in_array('GET', $r->methods(), true) && ! str_starts_with($r->uri(), '_') && $r->uri() !== 'up' && ! str_starts_with($r->uri(), 'storage'))
            ->map(fn ($r) => $r->uri())
            ->reject(fn ($uri) => collect($covered)->contains(fn ($c) => preg_match('#^'.preg_replace('/\\\{[^}]+\\\}/', '[^/]+', preg_quote($uri === '/' ? '' : $uri, '#')).'$#', $c)))
            ->values()->all();

        $this->assertSame([], $missing, 'GET routes without a page test: '.implode(', ', $missing));
    }

    public function test_blocked_and_draft_listings_are_not_public(): void
    {
        $this->get('/medicines/13')->assertNotFound(); // blocked
        $this->get('/medicines/14')->assertNotFound(); // draft
        $this->get('/medicines/8')->assertNotFound();  // pending review
        $this->get('/medicines/999')->assertNotFound();
        $this->get('/search?q=neotack')->assertSee('No eligible medicine matches your search');
    }

    public function test_search_filters_results(): void
    {
        $this->get('/search?q=napa')->assertSee('Napa 500')->assertSee('Napa Extend')->assertDontSee('Seclo 20');
        $this->get('/search?q=paracetamol 500mg')->assertSee('Napa 500')->assertDontSee('Fexo 120');
        $this->get('/medicines?division=Sylhet')->assertSee('Comet 500')->assertDontSee('Napa Extend');
        $this->get('/medicines?type[]=affordable_access')->assertSee('Fexo 120')->assertDontSee('Napa Extend');
        $this->get('/medicines?division=NotADivision')->assertOk(); // unknown values are ignored, not trusted
    }

    public function test_empty_state_only_shows_when_there_are_no_results(): void
    {
        $this->get('/medicines')->assertDontSee('No eligible medicine matches your search');
        $this->get('/search?q=zzzz-not-a-medicine')->assertSee('No eligible medicine matches your search');
    }

    public function test_public_profile_hides_contact_details(): void
    {
        $this->get('/members/2')->assertOk()->assertDontSee('rahim2@example.com')->assertDontSee('+880');
    }

    public function test_staff_profiles_are_not_public(): void
    {
        $this->get('/members/8')->assertNotFound(); // admin
    }

    public function test_form_actions_do_not_pretend_to_work(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->from('/login')->post('/login', ['login' => 'a@b.com', 'password' => 'secret123'])
            ->assertRedirect('/login')->assertSessionHas('info', fn ($m) => str_starts_with($m, 'Preview:'));
        $this->from('/my/listings/create')->post('/my/listings')->assertRedirect('/my/listings/create')->assertSessionHas('info');
        $this->from('/admin/reports/5')->patch('/admin/reports/5/decision')->assertSessionHas('info', fn ($m) => str_contains($m, 'Admin action not recorded'));
    }

    public function test_unknown_url_shows_branded_404_page(): void
    {
        $this->get('/this-page-does-not-exist')->assertNotFound()->assertSee('Page not found');
    }

    public function test_preview_message_is_displayed_after_redirect(): void
    {
        $this->withSession(['info' => 'Preview: test message'])->get('/login')->assertSee('Preview: test message');
    }
}
