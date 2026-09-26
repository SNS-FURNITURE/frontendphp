<?php

namespace App\Console\Commands;

use App\Services\OrderDeadlineAlertService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:send-deadline-alerts')]
#[Description('Send OMS/OMF phase deadline reminders and overdue alerts')]
class OrdersSendDeadlineAlerts extends Command
{
    public function handle(OrderDeadlineAlertService $alerts): int
    {
        $sent = $alerts->processDueAlerts();
        $this->info("Sent {$sent} deadline alert(s).");

        return self::SUCCESS;
    }
}
