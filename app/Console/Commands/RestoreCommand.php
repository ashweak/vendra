<?php

namespace App\Console\Commands;

use App\Services\Backup;
use Illuminate\Console\Command;

class RestoreCommand extends Command
{
    protected $signature = 'vendra:restore {backup? : File name from vendra:backup --list (default: newest)} {--force : Skip the questions (and the app-version check)}';

    protected $description = 'Replace ALL data and uploaded files with a backup';

    public function handle(Backup $backup): int
    {
        $name = $this->argument('backup') ?: ($backup->list()[0] ?? null);
        if (! $name) {
            $this->error('No backups found.');

            return self::FAILURE;
        }
        $name = str_starts_with($name, 'backups/') ? $name : 'backups/'.$name;
        try {
            $m = $backup->manifest($name);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $this->info("Backup {$name}: made {$m['created_at']} on {$m['driver']}, ".count($m['tables']).' tables.');
        if (! $this->option('force') && ! $this->confirm('This REPLACES all current data and uploaded files. Make a fresh backup first if unsure. Continue?')) {
            $this->comment('Nothing changed.');

            return self::SUCCESS;
        }
        try {
            $r = $backup->restore($name, (bool) $this->option('force'));
        } catch (\Throwable $e) {
            $this->error('Restore stopped: '.$e->getMessage());

            return self::FAILURE;
        }
        $this->info("Restored {$r['rows']} rows and {$r['files']} files.");

        return self::SUCCESS;
    }
}
