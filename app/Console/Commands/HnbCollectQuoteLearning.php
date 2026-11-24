<?php
namespace App\Console\Commands;

use App\Services\HnbQuoteLearning;
use Illuminate\Console\Command;

final class HnbCollectQuoteLearning extends Command
{
    protected $signature='hnb:quote-learning-collect';
    protected $description='Capture prospective quote features and observe resolved outcomes locally';
    public function handle(HnbQuoteLearning $learning): int
    {
        $r=$learning->collect();
        $this->info("Captured: {$r['captured']}; resolved: {$r['resolved']}");
        return self::SUCCESS;
    }
}
