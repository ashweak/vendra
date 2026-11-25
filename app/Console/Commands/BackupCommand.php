<?php

namespace App\Console\Commands;

use App\Services\Backup;
use Illuminate\Console\Command;

class BackupCommand extends Command
{
    protected $signature = 'vendra:backup {--list : Show existing backups} {--key : Print a new random VENDRA_BACKUP_KEY}';

    protected $description = 'Make an encrypted backup of the database and uploaded files (keeps the newest VENDRA_BACKUPS_KEEP)';

    public function handle(Backup $backup): int
    {
        if ($this->option('key')) {
            $this->line('VENDRA_BACKUP_KEY=base64:'.base64_encode(random_bytes(32)));
            $this->comment('Store it somewhere safe (a password manager). Without it, backups can’t be opened.');

            return self::SUCCESS;
        }
        if ($this->option('list')) {
            $rows = array_map(fn ($n) => [$n], $backup->list());
            $rows ? $this->table(['Backup (newest first)'], $rows) : $this->info('No backups yet.');

            return self::SUCCESS;
        }
        $r = $backup->create();
        $pruned = $backup->prune(max(1, (int) config('vendra.backups_keep', 14)));
        $this->info(sprintf('Backup saved: %s (%s KB, %d tables, %d rows, %d files). Removed %d old.',
            $r['name'], number_format($r['bytes'] / 1024, 1), $r['tables'], $r['rows'], $r['files'], $pruned));

        return self::SUCCESS;
    }
}
