<?php

namespace App\Console\Commands;

use App\Services\BoardingCallService;
use Illuminate\Console\Command;

class ProcessBoardingCalls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fids:boarding-calls {--cleanup : Also cleanup old cache entries}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process automated boarding calls for eligible flights';

    /**
     * Execute the console command.
     */
    public function handle(BoardingCallService $boardingCallService): int
    {
        $this->info('Processing automated boarding calls...');
        
        try {
            $boardingCallService->processAutomatedBoardingCalls();
            
            if ($this->option('cleanup')) {
                $this->info('Cleaning up old boarding call cache entries...');
                $cleaned = $boardingCallService->cleanupBoardingCallCache();
                $this->info("Cleaned up {$cleaned} cache entries.");
            }
            
            // Display statistics
            $stats = $boardingCallService->getBoardingCallStatistics(1);
            
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Boarding Calls (24h)', $stats['total_boarding_calls']],
                    ['Successful Deliveries', $stats['successful_deliveries']],
                    ['Failed Deliveries', $stats['failed_deliveries']],
                    ['Unique Flights', $stats['unique_flights']],
                    ['Avg Advance Time (min)', $stats['average_advance_time']],
                ]
            );
            
            if (!empty($stats['by_channel'])) {
                $this->newLine();
                $this->info('Boarding Calls by Channel (24h):');
                $this->table(
                    ['Channel', 'Count'],
                    collect($stats['by_channel'])->map(fn($count, $channel) => [$channel, $count])->toArray()
                );
            }
            
            $this->info('✅ Boarding call processing completed successfully.');
            
        } catch (\Exception $e) {
            $this->error('❌ Boarding call processing failed: ' . $e->getMessage());
            
            \Log::error('Boarding call processing command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}
