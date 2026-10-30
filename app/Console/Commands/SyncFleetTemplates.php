<?php

namespace App\Console\Commands;

use App\Services\Fleet\MessageTemplates;
use Illuminate\Console\Command;

/**
 * Checks templates waiting for Meta's review, so new wording takes over as soon as it's approved.
 */
class SyncFleetTemplates extends Command
{
    protected $signature = 'fleet:sync-templates';

    protected $description = 'Check WhatsApp templates waiting for Meta approval';

    public function handle(MessageTemplates $templates): int
    {
        $this->info('Checked '.$templates->refreshPending().' template(s) with Meta.');

        return self::SUCCESS;
    }
}
