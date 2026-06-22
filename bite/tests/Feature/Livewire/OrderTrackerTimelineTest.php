<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Guest\OrderTracker;
use App\Models\Order;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase 5 (#25) — Order tracking re-skin.
 *
 * Verifies the customer-safe done/now/pending step timeline mapping, the
 * cancelled/completed states, that the rating write is scoped to the one
 * order looked up by tracking_token, and that branding review links render
 * only when present and are sanitized.
 */
class OrderTrackerTimelineTest extends TestCase
{
    use RefreshDatabase;

    private function makeShop(array $branding = []): Shop
    {
        return Shop::create([
            'name' => 'Sourdough',
            'slug' => 'sourdough-'.Str::random(6),
            'branding' => $branding,
        ]);
    }

    private function makeOrder(Shop $shop, string $status, array $extra = []): Order
    {
        return Order::forceCreate(array_merge([
            'shop_id' => $shop->id,
            'status' => $status,
            'total_amount' => 4.830,
            'subtotal_amount' => 4.830,
            'tax_amount' => 0,
        ], $extra));
    }

    /**
     * @return array<string, array{0: string, 1: array<int, string>}>
     */
    public static function statusTimelineProvider(): array
    {
        return [
            // unpaid → "Order received" is the now-step (NOT alarming)
            'unpaid' => ['unpaid', ['now', 'pending', 'pending', 'pending']],
            'paid' => ['paid', ['done', 'now', 'pending', 'pending']],
            'preparing' => ['preparing', ['done', 'done', 'now', 'pending']],
            'ready' => ['ready', ['done', 'done', 'done', 'now']],
            'completed' => ['completed', ['done', 'done', 'done', 'done']],
        ];
    }

    /**
     * @param  array<int, string>  $expected
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('statusTimelineProvider')]
    public function test_each_status_maps_to_the_right_timeline_step(string $status, array $expected): void
    {
        $shop = $this->makeShop();
        $order = $this->makeOrder($shop, $status);

        $state = Livewire::test(OrderTracker::class, ['trackingToken' => $order->tracking_token])
            ->instance()
            ->timelineState();

        $this->assertSame($expected, $state);
    }

    public function test_unpaid_status_uses_safe_copy_and_never_surfaces_the_word_unpaid(): void
    {
        $shop = $this->makeShop();
        $order = $this->makeOrder($shop, 'unpaid');

        $this->get(route('guest.track', $order->tracking_token))
            ->assertOk()
            ->assertSee(__('guest.track_step_received'))
            ->assertDontSee('unpaid', false)
            ->assertDontSee('Unpaid');
    }

    public function test_tracker_renders_figma_inspired_pickup_layout(): void
    {
        $shop = $this->makeShop();
        $order = $this->makeOrder($shop, 'paid', ['customer_name' => 'Layla']);
        $order->items()->create([
            'product_name_snapshot_en' => 'Karak Tea',
            'product_name_snapshot_ar' => 'شاي كرك',
            'price_snapshot' => 0.500,
            'quantity' => 2,
        ]);

        $this->get(route('guest.track', $order->tracking_token))
            ->assertOk()
            ->assertSee('guest-track__phone', false)
            ->assertSee('guest-track-hero', false)
            ->assertDontSee('guest-statusbar', false)
            ->assertSee('guest-track-lang', false)
            ->assertSee('guest-track-received-card', false)
            ->assertSee('guest-track-progress-list', false)
            ->assertSee('customer-ordering/assets/hopresso/cup-togo.png', false)
            ->assertSee(__('guest.track_received_title'))
            ->assertSee(__('guest.track_received_body'))
            ->assertSee(__('guest.track_simulate_next'))
            ->assertSee(__('guest.rate_your_visit'));
    }

    public function test_tracker_header_does_not_render_fake_mobile_statusbar(): void
    {
        $shop = $this->makeShop();
        $order = $this->makeOrder($shop, 'paid');

        $this->get(route('guest.track', $order->tracking_token))
            ->assertOk()
            ->assertDontSee('guest-statusbar', false)
            ->assertDontSee('9:41', false);

        $css = file_get_contents(resource_path('css/guest-track.css'));

        $this->assertStringNotContainsString('.guest-statusbar', $css);
        $this->assertStringNotContainsString('.guest-statusbar__icons', $css);
    }

    public function test_tracker_can_carry_table_context_from_checkout_redirect(): void
    {
        $shop = $this->makeShop();
        $order = $this->makeOrder($shop, 'unpaid');

        $this->get(route('guest.track', $order->tracking_token).'?table=12')
            ->assertOk()
            ->assertSee(__('guest.track_order_reference', [
                'order' => 'SO-' . str_pad((string) $order->id, 3, '0', STR_PAD_LEFT),
                'table' => '12',
            ]));
    }

    public function test_tracker_css_uses_bite_green_for_annotated_surfaces(): void
    {
        $css = file_get_contents(resource_path('css/guest-track.css'));

        $this->assertStringContainsString('--track-olive: #006334;', $css);
        $this->assertMatchesRegularExpression('/\.guest-track-header\s*\{[^}]*background: var\(--track-olive\);/s', $css);
        $this->assertMatchesRegularExpression('/\.guest-track-header h1\s*\{[^}]*color: #fff;/s', $css);
        $this->assertMatchesRegularExpression('/\.guest-track-progress-list\s*\{[^}]*color: var\(--track-olive\);/s', $css);
        $this->assertMatchesRegularExpression('/\.guest-track-progress-step\s*\{[^}]*color: var\(--track-olive\);/s', $css);
        $this->assertMatchesRegularExpression('/\.guest-track-progress-step__number\s*\{[^}]*color: var\(--track-olive\);/s', $css);
        $this->assertMatchesRegularExpression('/\.guest-track-progress-step--done,\s*\.guest-track-progress-step--now\s*\{[^}]*color: var\(--track-olive\);/s', $css);
        $this->assertMatchesRegularExpression('/\.guest-track-progress-step--done \.guest-track-progress-step__number,\s*\.guest-track-progress-step--now \.guest-track-progress-step__number\s*\{[^}]*color: var\(--track-olive\);/s', $css);
        $this->assertMatchesRegularExpression('/\.guest-track-action\s*\{[^}]*background: var\(--track-olive\);/s', $css);
        $this->assertMatchesRegularExpression('/\.guest-track-action--secondary\s*\{[^}]*color: var\(--track-olive\);/s', $css);
        $this->assertMatchesRegularExpression('/\.guest-track-action--secondary\s*\{[^}]*border-color: rgba\(0, 99, 52, 0\.38\);/s', $css);
        $this->assertMatchesRegularExpression('/\.guest-visit-review__hero\s*\{[^}]*background: var\(--track-olive\);/s', $css);
        $this->assertMatchesRegularExpression('/\.guest-visit-review__link--secondary\s*\{[^}]*color: var\(--track-olive\);/s', $css);
        $this->assertMatchesRegularExpression('/\.guest-visit-review__link--secondary\s*\{[^}]*border-color: rgb\(0 99 52 \/ 0\.22\);/s', $css);
        $this->assertMatchesRegularExpression('/\.guest-visit-review__button--secondary\s*\{[^}]*color: var\(--track-olive\);/s', $css);
        $this->assertMatchesRegularExpression('/\.guest-visit-review__button--secondary\s*\{[^}]*border-color: rgb\(0 99 52 \/ 0\.45\);/s', $css);
    }

    public function test_tracker_css_uses_customer_ordering_arabic_font_stack(): void
    {
        $css = file_get_contents(resource_path('css/guest-track.css'));

        $this->assertStringContainsString("--guest-track-font-arabic: 'GE Dinar One'", $css);
        $this->assertMatchesRegularExpression('/\[dir="rtl"\]\s+\.guest-track\s+:where\(\*\)\s*\{[^}]*font-family: var\(--guest-track-font-arabic\) !important;/s', $css);
    }

    public function test_cancelled_status_shows_a_distinct_cancelled_state(): void
    {
        $shop = $this->makeShop();
        $order = $this->makeOrder($shop, 'cancelled');

        $this->get(route('guest.track', $order->tracking_token))
            ->assertOk()
            ->assertSee(__('guest.track_cancelled_title'))
            ->assertSee(__('guest.status_cancelled'))
            // Cancelled short-circuits the step timeline.
            ->assertDontSee(__('guest.track_step_preparing'));
    }

    public function test_completed_status_shows_all_steps_done(): void
    {
        $shop = $this->makeShop();
        $order = $this->makeOrder($shop, 'completed');

        $component = Livewire::test(OrderTracker::class, ['trackingToken' => $order->tracking_token]);

        $this->assertSame(['done', 'done', 'done', 'done'], $component->instance()->timelineState());
    }

    public function test_demo_simulator_advances_to_next_customer_safe_status(): void
    {
        $shop = $this->makeShop();
        $order = $this->makeOrder($shop, 'unpaid');

        Livewire::test(OrderTracker::class, ['trackingToken' => $order->tracking_token])
            ->call('simulateNextStatus')
            ->assertHasNoErrors();

        $this->assertSame('paid', $order->fresh()->status);
    }

    public function test_review_invite_only_appears_when_ready_or_completed(): void
    {
        $shop = $this->makeShop();

        $preparing = $this->makeOrder($shop, 'preparing');
        $this->get(route('guest.track', $preparing->tracking_token))
            ->assertDontSeeText(__('guest.visit_review_title', ['shop' => $shop->name]));

        $ready = $this->makeOrder($shop, 'ready');
        $this->get(route('guest.track', $ready->tracking_token))
            ->assertSeeText(__('guest.visit_review_title', ['shop' => $shop->name]));
    }

    public function test_ready_order_exposes_standalone_visit_rating_screen(): void
    {
        $shop = $this->makeShop([
            'google_review_url' => 'https://www.google.com/maps/place/Sourdough',
            'instagram_url' => 'https://instagram.com/sourdough_om',
        ]);
        $order = $this->makeOrder($shop, 'ready');

        $this->get(route('guest.track', $order->tracking_token).'?table=12')
            ->assertOk()
            ->assertSee('id="visit-rating"', false)
            ->assertSee('guest-visit-review', false)
            ->assertSee('guest-visit-review__hero', false)
            ->assertSee(__('guest.visit_review_before_leave'))
            ->assertSeeText(__('guest.visit_review_title', ['shop' => $shop->name]))
            ->assertSee(__('guest.visit_review_body'))
            ->assertSee('guest-visit-review__stars', false)
            ->assertSee(__('guest.visit_google_cta'))
            ->assertSee('https://www.google.com/maps/place/Sourdough', false)
            ->assertSee(__('guest.visit_instagram_cta'))
            ->assertSee('https://instagram.com/sourdough_om', false)
            ->assertSee(__('guest.visit_back_to_menu'))
            ->assertSee(__('guest.visit_track_current_order'))
            ->assertSee('customer-ordering/assets/brand/bite-powered-logo.png', false);
    }

    public function test_rating_persists_only_to_the_one_order_for_that_token(): void
    {
        $shop = $this->makeShop();
        $target = $this->makeOrder($shop, 'completed');
        $other = $this->makeOrder($shop, 'completed');

        Livewire::test(OrderTracker::class, ['trackingToken' => $target->tracking_token])
            ->set('rating', 4)
            ->set('feedbackComment', 'Lovely sourdough')
            ->call('submitFeedback')
            ->assertHasNoErrors();

        $this->assertSame(4, $target->fresh()->customer_rating);
        $this->assertSame('Lovely sourdough', $target->fresh()->customer_feedback);

        // The other order must be untouched.
        $this->assertNull($other->fresh()->customer_rating);
        $this->assertNull($other->fresh()->customer_feedback);
    }

    public function test_review_links_render_only_when_branding_present(): void
    {
        $shopWith = $this->makeShop([
            'google_review_url' => 'https://www.google.com/maps/place/Sourdough',
            'instagram_url' => 'https://instagram.com/sourdough_om',
        ]);
        $order = $this->makeOrder($shopWith, 'completed');

        $this->get(route('guest.track', $order->tracking_token))
            ->assertSee(__('guest.visit_google_cta'))
            ->assertSee('https://www.google.com/maps/place/Sourdough', false)
            ->assertSee(__('guest.visit_instagram_cta'))
            ->assertSee('https://instagram.com/sourdough_om', false);

        $shopWithout = $this->makeShop();
        $bare = $this->makeOrder($shopWithout, 'completed');

        $this->get(route('guest.track', $bare->tracking_token))
            ->assertDontSee(__('guest.visit_google_cta'))
            ->assertDontSee(__('guest.visit_instagram_cta'));
    }

    public function test_unsafe_branding_url_is_sanitized_and_hidden(): void
    {
        $shop = $this->makeShop([
            'google_review_url' => 'javascript:alert(1)',
            'instagram_url' => 'https://instagram.com/sourdough_om',
        ]);
        $order = $this->makeOrder($shop, 'completed');

        $response = $this->get(route('guest.track', $order->tracking_token))->assertOk();

        // The dangerous scheme must never reach the rendered HTML, and the
        // Google action (its only source) must be hidden.
        $response->assertDontSee('javascript:alert', false);
        $response->assertDontSee(__('guest.visit_google_cta'));

        // The safe Instagram link still renders.
        $response->assertSee(__('guest.visit_instagram_cta'));
    }

    public function test_only_the_order_for_the_token_is_exposed(): void
    {
        $shop = $this->makeShop();
        $mine = $this->makeOrder($shop, 'ready', ['customer_name' => 'Layla']);
        $other = $this->makeOrder($shop, 'ready', ['customer_name' => 'SomeoneElse']);

        $this->get(route('guest.track', $mine->tracking_token))
            ->assertOk()
            ->assertSee('Layla')
            ->assertDontSee('SomeoneElse');
    }
}
