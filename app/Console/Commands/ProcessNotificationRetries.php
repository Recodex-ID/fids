<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class ProcessNotificationRetries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:retry {--cleanup : Also cleanup old notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process failed notification retries and optionally cleanup old notifications';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $this->info('Processing notification retry queue...');
        
        $notificationService->processRetryQueue();
        
        if ($this->option('cleanup')) {
            $this->info('Cleaning up old notifications...');
            $deleted = $notificationService->cleanupOldNotifications();
            $this->info("Cleaned up {$deleted} old notifications.");
        }
        
        // Display statistics
        $stats = $notificationService->getNotificationStatistics(1);
        
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Sent (24h)', $stats['total_sent']],
                ['Successful', $stats['successful']],
                ['Failed', $stats['failed']],
                ['Pending', $stats['pending']],
            ]
        );
        
        if (!empty($stats['by_type'])) {
            $this->newLine();
            $this->info('Notifications by Type (24h):');
            $this->table(
                ['Type', 'Count'],
                collect($stats['by_type'])->map(fn($count, $type) => [$type, $count])->toArray()
            );
        }
        
        $this->info('✅ Notification retry processing completed.');
        
        return Command::SUCCESS;
    }
}
