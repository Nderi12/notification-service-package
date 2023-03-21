<?php

namespace Tests\Feature;

use App\Events\OrderStatusUpdated;
use App\Listeners\OrderStatusUpdatedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderStatusUpdatedListenerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function it_submits_a_notification_card_to_the_webhook_endpoint()
    {
        // Generate random order data
        $orderUuid = $this->faker->uuid;
        $newStatus = $this->faker->word;
        $updatedTimestamp = $this->faker->dateTimeThisMonth();

        // Mock the Guzzle client to intercept the webhook request
        $mockClient = new \GuzzleHttp\Handler\MockHandler([
            new \GuzzleHttp\Psr7\Response(200),
        ]);
        $handlerStack = \GuzzleHttp\HandlerStack::create($mockClient);
        $mockClient = new \GuzzleHttp\Client(['handler' => $handlerStack]);

        // Create a new instance of the OrderStatusUpdated event and listener
        $event = new OrderStatusUpdated($orderUuid, $newStatus, $updatedTimestamp);
        $listener = new OrderStatusUpdatedListener($mockClient);

        // Call the listener's handle method to submit the notification card
        $listener->handle($event);

        // Check that the webhook request was made with the correct JSON payload
        $expectedPayload = [
            "@type" => "MessageCard",
            "themeColor" => "0076D7",
            "title" => "Order Status Updated",
            "text" => "The order with UUID {$orderUuid} has been updated to status '{$newStatus}' at {$updatedTimestamp->format('Y-m-d H:i:s')}.",
            "potentialAction" => [
                [
                    "@type" => "OpenUri",
                    "name" => "View Order",
                    "targets" => [
                        [
                            "os" => "default",
                            "uri" => "https://example.com/orders/{$orderUuid}",
                        ],
                    ],
                ],
            ],
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expectedPayload), $mockClient->getLastRequest()->getBody()->getContents());

        // Check that the webhook request was successful
        $this->assertEquals(200, $mockClient->getLastResponse()->getStatusCode());
    }
}