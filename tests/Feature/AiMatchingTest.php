<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\MedicineListing;
use App\Models\MedicineRequest;
use App\Models\Setting;
use App\Services\Ai\SemanticRanker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Laravel <-> AI service integration. The Python service is faked here; its own
 * behaviour is tested in ai-service/tests and measured in ai-service/evaluation.
 */
class AiMatchingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
        Setting::put(['ai_matching' => true]);
    }

    /** Fake the service: similarity per candidate decided by $similarity(queryText, candidateText). */
    private function fakeAi(callable $similarity): void
    {
        Http::fake(['ai.test/v1/rank' => function (Request $request) use ($similarity) {
            $results = collect($request['candidates'])
                ->map(fn ($c) => ['id' => $c['id'], 'similarity' => $s = $similarity($request['query'], $c['text']), 'score' => (int) round(max(0, ($s - 0.8) / 0.2) * 100)])
                ->sortByDesc('similarity')->values()->all();

            return Http::response(['model' => 'fake', 'results' => $results]);
        }]);
    }

    private function freeTextRequest(array $overrides = []): MedicineRequest
    {
        $this->actingAs($member = $this->member());
        $this->post('/my/requests', array_merge([
            'medicine_id' => '', 'medicine_name' => 'paracitamol', 'generic_name' => '', 'strength' => '500 mg', 'dosage_form' => 'Tablet',
            'quantity' => '10 tablets', 'preference' => 'either', 'division' => 'Dhaka', 'district' => 'Dhaka', 'upazila' => 'Mirpur',
        ], $overrides))->assertSessionHasNoErrors();

        return MedicineRequest::where('user_id', $member->id)->firstOrFail();
    }

    public function test_misspelled_request_gets_a_suggested_identity_and_labelled_matches(): void
    {
        $napa = MedicineListing::factory()->create(['medicine_id' => $this->medicine('Napa 500')->id, 'location_id' => $this->location()->id]);
        $this->fakeAi(fn ($q, $text) => str_contains($text, 'Paracetamol') ? 0.93 : 0.84);

        $request = $this->freeTextRequest();

        $this->assertSame($this->medicine('Napa 500')->id, $request->suggested_medicine_id);
        $this->assertEqualsWithDelta(0.93, $request->suggestion_similarity, 0.001);
        $match = $request->matches()->firstOrFail();
        $this->assertSame($napa->id, $match->medicine_listing_id);
        $this->assertSame('semantic_identity', $match->method);
        $this->get(route('requests.show', $request))->assertSeeText('Did you mean Napa 500?')->assertSeeText('Medicine name suggested by AI');
    }

    public function test_without_ai_the_misspelled_request_finds_nothing(): void
    {
        Setting::put(['ai_matching' => false]);
        MedicineListing::factory()->create(['medicine_id' => $this->medicine('Napa 500')->id]);
        Http::fake();

        $request = $this->freeTextRequest();

        $this->assertNull($request->suggested_medicine_id);
        $this->assertSame(0, $request->matches()->count());
        Http::assertNothingSent();
    }

    public function test_ai_never_overrides_strength_or_form(): void
    {
        MedicineListing::factory()->create(['medicine_id' => $this->medicine('Napa Extend')->id]); // paracetamol 665 mg
        MedicineListing::factory()->create(['medicine_id' => $this->medicine('Ace Syrup')->id]);   // paracetamol syrup
        $this->fakeAi(fn () => 0.99); // the model "loves" everything

        $request = $this->freeTextRequest(); // asks for 500 mg tablets

        $this->assertNotSame($this->medicine('Napa Extend')->id, $request->suggested_medicine_id);
        $this->assertNotSame($this->medicine('Ace Syrup')->id, $request->suggested_medicine_id);
        $this->assertSame(0, $request->matches()->count());
    }

    public function test_only_catalogue_entries_with_same_strength_and_form_are_sent(): void
    {
        $this->fakeAi(fn () => 0.9);
        $this->freeTextRequest();

        Http::assertSent(function (Request $r) {
            $texts = collect($r['candidates'])->pluck('text');

            return $texts->isNotEmpty() && $texts->every(fn ($t) => str_contains($t, '500 mg') && str_contains($t, 'Tablet'));
        });
    }

    public function test_low_similarity_or_ambiguous_results_are_not_suggested(): void
    {
        // Below the threshold.
        $this->fakeAi(fn () => 0.85);
        $this->assertNull($this->freeTextRequest()->suggested_medicine_id);

        // Above the threshold but not clearly ahead of a different ingredient (Napa 0.93 vs Comet 0.925).
        $this->fakeAi(fn ($q, $text) => str_contains($text, 'Paracetamol') ? 0.93 : (str_contains($text, 'Metformin') ? 0.925 : 0.8));
        $this->assertNull($this->freeTextRequest()->suggested_medicine_id);
    }

    public function test_not_allowed_medicines_are_never_candidates(): void
    {
        $this->fakeAi(fn () => 0.99);
        $this->freeTextRequest(['medicine_name' => 'ranitidin', 'strength' => '150 mg']);

        Http::assertNotSent(fn (Request $r) => collect($r['candidates'])->contains(fn ($c) => str_contains($c['text'], 'Neotack')));
    }

    public function test_semantic_scores_rank_catalogue_matches(): void
    {
        MedicineListing::factory()->create(['medicine_id' => $this->medicine('Napa 500')->id, 'location_id' => $this->location('Sylhet', 'Sylhet Sadar')->id]);
        $this->fakeAi(fn () => 0.95);

        $request = $this->freeTextRequest(['medicine_id' => $this->medicine('Napa 500')->id]);
        $match = $request->matches()->firstOrFail();

        $this->assertSame('semantic', $match->method);
        $this->assertSame(70 + 11 + 3, $match->score); // identity 70 + round(15 * 75/100) + recent 3 (different division)
    }

    public function test_no_personal_data_is_sent_to_the_ai_service(): void
    {
        MedicineListing::factory()->create(['medicine_id' => $this->medicine('Napa 500')->id]);
        $this->fakeAi(fn () => 0.9);
        $request = $this->freeTextRequest(['notes' => 'For my mother, call 01712345678']);

        Http::assertSent(function (Request $r) use ($request) {
            $body = json_encode($r->data());

            return $r->hasHeader('X-API-Key', 'test-key')
                && array_keys($r->data()) === ['query', 'candidates']
                && ! str_contains($body, '01712345678') && ! str_contains($body, 'mother')
                && ! str_contains($body, $request->owner->email) && ! str_contains($body, $request->owner->name)
                && ! str_contains($body, 'Mirpur');
        });
    }

    public function test_service_failure_falls_back_to_keyword_matching_and_pauses_calls(): void
    {
        MedicineListing::factory()->create(['medicine_id' => $this->medicine('Napa 500')->id]);
        Http::fake(['ai.test/*' => fn () => throw new ConnectionException('Connection refused')]);

        $request = $this->freeTextRequest(['medicine_id' => $this->medicine('Napa 500')->id]);

        $this->assertSame('keyword', $request->matches()->firstOrFail()->method); // still matched
        $this->assertFalse(app(SemanticRanker::class)->enabled(), 'circuit breaker is open');
        $calls = count(Http::recorded());
        $this->freeTextRequest();
        $this->assertSame($calls, count(Http::recorded()), 'no further calls while the service is down');
    }

    public function test_search_with_no_keyword_results_shows_labelled_ai_suggestions(): void
    {
        MedicineListing::factory()->create(['medicine_id' => $this->medicine('Napa 500')->id]);
        $this->fakeAi(fn ($q, $text) => str_contains($text, 'Napa 500') ? 0.92 : 0.82);

        $this->get('/search?q=paracitamol')->assertOk()
            ->assertSeeText('No exact matches — showing AI suggestions')
            ->assertSeeText('Napa 500');
    }

    public function test_search_with_keyword_results_does_not_call_ai(): void
    {
        MedicineListing::factory()->create(['medicine_id' => $this->medicine('Napa 500')->id]);
        Http::fake();

        $this->get('/search?q=napa')->assertOk()->assertDontSeeText('showing AI suggestions');
        Http::assertNothingSent();
    }

    public function test_search_suggestions_respect_strength_in_the_query(): void
    {
        $this->fakeAi(fn () => 0.95);
        $this->get('/search?q=paracitamol 665mg')->assertOk();

        Http::assertSent(fn (Request $r) => collect($r['candidates'])->every(fn ($c) => str_contains($c['text'], '665 mg')));
    }

    public function test_admin_can_switch_ai_on_and_sees_service_status(): void
    {
        Setting::put(['ai_matching' => false]);
        Http::fake(['ai.test/health' => Http::response(['status' => 'ok', 'model' => 'intfloat/multilingual-e5-small'])]);
        $admin = $this->member();
        $admin->forceFill(['role' => Role::Admin])->save();

        $this->actingAs($admin)->get('/admin/settings')->assertOk()->assertSeeText('Matching service: Online')->assertSeeText('intfloat/multilingual-e5-small');
        $this->actingAs($admin)->patch('/admin/settings', ['ai_matching' => '1', 'min_expiry_months' => 1, 'request_expiry_days' => 30])->assertSessionHas('success');
        $this->assertTrue(Setting::get('ai_matching'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'settings.updated']);
    }

    public function test_admin_sees_when_service_is_down(): void
    {
        Http::fake(['ai.test/health' => Http::response('', 500)]);
        $admin = $this->member();
        $admin->forceFill(['role' => Role::Admin])->save();

        $this->actingAs($admin)->get('/admin/settings')->assertOk()->assertSeeText('Matching service: Not reachable');
    }
}
