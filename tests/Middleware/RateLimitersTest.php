<?php

namespace RTippin\Messenger\Tests\Middleware;

use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class RateLimitersTest extends FeatureTestCase
{
    private MessengerProvider $tippin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withMiddleware(ThrottleRequests::class);

        $this->tippin = $this->userTippin();
    }

    /** @test */
    public function general_api_limits_request_120_per_minute()
    {
        $this->actingAs($this->tippin);

        $response = $this->getJson(route('api.messenger.threads.index'));

        $limit = $response->headers->get('X-Ratelimit-Limit');

        $remaining = $response->headers->get('X-RateLimit-Remaining');

        $this->assertEquals(120, $limit);

        $this->assertEquals(119, $remaining);
    }

    /** @test */
    public function setting_limit_to_zero_results_in_unlimited_request_per_minute()
    {
        Messenger::setApiRateLimit(0);

        $this->actingAs($this->tippin);

        $response = $this->getJson(route('api.messenger.threads.index'));

        $limit = $response->headers->get('X-Ratelimit-Limit');

        $remaining = $response->headers->get('X-RateLimit-Remaining');

        $this->assertNull($limit);

        $this->assertNull($remaining);
    }

    /** @test */
    public function search_api_limits_request_45_per_minute()
    {
        $this->actingAs($this->tippin);

        $response = $this->getJson(route('api.messenger.search'));

        $limit = $response->headers->get('X-Ratelimit-Limit');

        $remaining = $response->headers->get('X-RateLimit-Remaining');

        $this->assertEquals(45, $limit);

        $this->assertEquals(44, $remaining);
    }

    /** @test */
    public function store_message_api_limits_request_60_per_minute()
    {
        $group = $this->createGroupThread($this->tippin);

        $this->actingAs($this->tippin);

        $response = $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $group->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ]);

        $limit = $response->headers->get('X-Ratelimit-Limit');

        $remaining = $response->headers->get('X-RateLimit-Remaining');

        $this->assertEquals(60, $limit);

        $this->assertEquals(59, $remaining);
    }

    /** @test */
    public function store_image_message_api_limits_request_10_per_minute()
    {
        Storage::fake(Messenger::getThreadStorage('disk'));

        $group = $this->createGroupThread($this->tippin);

        $this->actingAs($this->tippin);

        $response = $this->postJson(route('api.messenger.threads.images.store', [
            'thread' => $group->id,
        ]), [
            'image' => UploadedFile::fake()->image('picture.jpg'),
            'temporary_id' => '123-456-789',
        ]);

        $limit = $response->headers->get('X-Ratelimit-Limit');

        $remaining = $response->headers->get('X-RateLimit-Remaining');

        $this->assertEquals(10, $limit);

        $this->assertEquals(9, $remaining);
    }

    /** @test */
    public function store_document_message_api_limits_request_10_per_minute()
    {
        Storage::fake(Messenger::getThreadStorage('disk'));

        $group = $this->createGroupThread($this->tippin);

        $this->actingAs($this->tippin);

        $response = $this->postJson(route('api.messenger.threads.documents.store', [
            'thread' => $group->id,
        ]), [
            'document' => UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
            'temporary_id' => '123-456-789',
        ]);

        $limit = $response->headers->get('X-Ratelimit-Limit');

        $remaining = $response->headers->get('X-RateLimit-Remaining');

        $this->assertEquals(10, $limit);

        $this->assertEquals(9, $remaining);
    }
}
