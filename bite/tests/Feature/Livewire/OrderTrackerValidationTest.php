<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Guest\OrderTracker;
use App\Models\Order;
use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * SEC-03 input validation tests — OrderTracker feedback form.
 *
 * These tests verify that the guest order tracker enforces validation on
 * the feedback rating and comment fields, and that stored XSS payloads
 * in comments are sanitized before persistence (D-16).
 */
class OrderTrackerValidationTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(): Order
    {
        $shop = Shop::create(['name' => 'Test Shop', 'slug' => 'test-shop']);

        return Order::forceCreate([
            'shop_id' => $shop->id,
            'status' => 'completed',
            'total_amount' => 10.000,
        ]);
    }

    public function test_feedback_rating_zero_is_rejected_with_validation_error(): void
    {
        $order = $this->makeOrder();

        Livewire::test(OrderTracker::class, ['trackingToken' => $order->tracking_token])
            ->set('rating', 0)
            ->set('feedbackComment', 'Nice place')
            ->call('submitFeedback')
            ->assertHasErrors(['rating']);

        // Rating must not be saved when validation fails
        $this->assertNull($order->fresh()->customer_rating);
    }

    public function test_feedback_rating_six_is_rejected_with_validation_error(): void
    {
        $order = $this->makeOrder();

        Livewire::test(OrderTracker::class, ['trackingToken' => $order->tracking_token])
            ->set('rating', 6)
            ->set('feedbackComment', 'Excellent')
            ->call('submitFeedback')
            ->assertHasErrors(['rating']);

        $this->assertNull($order->fresh()->customer_rating);
    }

    public function test_feedback_comment_over_500_chars_is_rejected(): void
    {
        $order = $this->makeOrder();

        $longComment = str_repeat('a', 501);

        Livewire::test(OrderTracker::class, ['trackingToken' => $order->tracking_token])
            ->set('rating', 4)
            ->set('feedbackComment', $longComment)
            ->call('submitFeedback')
            ->assertHasErrors(['feedbackComment']);

        $this->assertNull($order->fresh()->customer_rating);
    }

    public function test_valid_feedback_is_accepted_and_persisted(): void
    {
        $order = $this->makeOrder();

        Livewire::test(OrderTracker::class, ['trackingToken' => $order->tracking_token])
            ->set('rating', 5)
            ->set('feedbackComment', 'Great service!')
            ->call('submitFeedback')
            ->assertHasNoErrors();

        $this->assertSame(5, $order->fresh()->customer_rating);
        $this->assertSame('Great service!', $order->fresh()->customer_feedback);
    }

    public function test_xss_payload_in_comment_is_stripped_before_storage(): void
    {
        $order = $this->makeOrder();

        Livewire::test(OrderTracker::class, ['trackingToken' => $order->tracking_token])
            ->set('rating', 3)
            ->set('feedbackComment', "<script>alert('xss')</script>Good food")
            ->call('submitFeedback')
            ->assertHasNoErrors();

        $savedComment = $order->fresh()->customer_feedback;

        // The <script> tag must not be stored
        $this->assertStringNotContainsString('<script>', (string) $savedComment);
        $this->assertStringNotContainsString('</script>', (string) $savedComment);
    }

    public function test_feedback_cannot_be_submitted_twice(): void
    {
        $order = $this->makeOrder();

        // First submission
        Livewire::test(OrderTracker::class, ['trackingToken' => $order->tracking_token])
            ->set('rating', 5)
            ->set('feedbackComment', 'First')
            ->call('submitFeedback');

        $firstRating = $order->fresh()->customer_rating;
        $this->assertSame(5, $firstRating);

        // Second submission must be silently ignored
        Livewire::test(OrderTracker::class, ['trackingToken' => $order->tracking_token])
            ->set('rating', 1)
            ->set('feedbackComment', 'Changed my mind')
            ->call('submitFeedback');

        // Rating must not change from first submission
        $this->assertSame(5, $order->fresh()->customer_rating);
    }
}
