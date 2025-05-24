<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Http\Controllers\TipDistribution;
use Illuminate\Support\Facades\Log;

class DistributeTip
{
    public function handle(PaymentCompleted $event)
    {
        try {
            $tipDistributor = new TipDistribution();
            $response = $tipDistributor->distributeTipsToChefs($event->payment->order_id);

            Log::info('Tips distributed successfully.', ['response' => $response]);
        } catch (\Exception $e) {
            Log::error('Tip distribution failed: ' . $e->getMessage());
        }
    }
}
