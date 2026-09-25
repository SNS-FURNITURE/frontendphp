<?php

namespace App\Console\Commands;

use App\Services\CommercialReportService;
use Illuminate\Console\Command;

class GenerateCommercialReportsCommand extends Command
{
    protected $signature = 'commercial:reports {cadence : weekly, monthly, or yearly}';

    protected $description = 'Generate commercial contact/review reports for marketing managers';

    public function handle(CommercialReportService $reports): int
    {
        $cadence = strtolower((string) $this->argument('cadence'));

        if (! in_array($cadence, ['weekly', 'monthly', 'yearly'], true)) {
            $this->error('Cadence must be weekly, monthly, or yearly.');

            return self::FAILURE;
        }

        $count = $reports->generatePeriodicReports($cadence);
        $this->info("Generated {$count} {$cadence} commercial report(s).");

        return self::SUCCESS;
    }
}
