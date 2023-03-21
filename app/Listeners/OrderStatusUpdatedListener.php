<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use GuzzleHttp\Client;

class OrderStatusUpdatedListener implements ShouldQueue
{
    public function handle(OrderStatusUpdated $event)
    {
        // Build the notification card JSON object
        $notificationCard = [
            "@type" => "MessageCard",
            "themeColor" => "0076D7",
            "title" => "Order Status Updated",
            "text" => "The order with UUID {$event->orderUuid} has been updated to status '{$event->newStatus}' at {$event->updatedTimestamp}.",
            "potentialAction" => [
                [
                    "@type" => "OpenUri",
                    "name" => "View Order",
                    "targets" => [
                        [
                            "os" => "default",
                            "uri" => "https://example.com/orders/{$event->orderUuid}",
                        ],
                    ],
                ],
            ],
        ];

        // Submit the notification card to the webhook endpoint
        $client = new Client();
        $response = $client->post('https://example.com/webhook', [
            'json' => $notificationCard,
        ]);

        // Check the response status code to ensure the webhook request was successful
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Webhook request failed with status code ' . $response->getStatusCode());
        }
    }
}


