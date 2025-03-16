<?php

namespace App\Listeners;

use App\Events\SubscriptionStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateInvoiceForSubscriptionStatus
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SubscriptionStatusUpdated $event): void
    {
        //
    }
}
