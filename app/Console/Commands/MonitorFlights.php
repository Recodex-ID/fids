<?php

namespace App\Console\Commands;

use App\Services\FlightMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorFlights extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fids:monitor {--continuous : Run monitoring continuously}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor flight operations and log performance metrics';

    /**
     * Execute the console command.
     */
    public function handle(FlightMonitoringService $monitoringService): int
    {
        $this->info('Starting FIDS flight monitoring...');

        if ($this->option('continuous')) {
            $this->info('Running in continuous mode. Press Ctrl+C to stop.');

            while (true) {
                $this->runMonitoringCycle($monitoringService);
                sleep(60); // Wait 1 minute between checks
            }
        } else {
            $this->runMonitoringCycle($monitoringService);
            $this->info('Monitoring cycle completed.');
        }

        return Command::SUCCESS;
    }

    /**
     * Run a single monitoring cycle
     */
    private function runMonitoringCycle(FlightMonitoringService $monitoringService): void
    {
        $this->line('Running monitoring cycle at ' . now()->format('Y-m-d H:i:s'));

        try {
            // Monitor flight performance
            $monitoringService->monitorFlightPerformance();

            // Get performance metrics
            $metrics = $monitoringService->getPerformanceMetrics();

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Flights', $metrics['total_flights']],
                    ['On-Time %', $metrics['on_time_percentage'] . '%'],
                    ['Delayed %', $metrics['delay_percentage'] . '%'],
                    ['Cancelled %', $metrics['cancellation_percentage'] . '%'],
                    ['Avg Delay (min)', $metrics['average_delay_minutes']],
                ]
            );

            $this->info('✅ Monitoring cycle completed successfully');

        } catch (\Exception $e) {
            $this->error('❌ Monitoring cycle failed: ' . $e->getMessage());
            Log::error('Flight monitoring failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now(),
            ]);
        }
    }
}
