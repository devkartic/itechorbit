<?php

namespace App\Console\Commands;

use App\Services\Admin\MailNotices;
use Illuminate\Console\Command;

class DispatchMailNotices extends Command
{
    protected $signature = 'notices:dispatch';

    protected $description = 'Enqueue pending notice recipients and identify interrupted sends';

    public function handle(MailNotices $notices): int
    {
        if (! $notices->installed()) {
            $this->warn('Apply the mail notice migration first.');

            return self::FAILURE;
        }
        $this->info($notices->dispatchPending().' recipients enqueued.');

        return self::SUCCESS;
    }
}
