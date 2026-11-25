<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Encrypted backups of the whole database and uploaded files, in one portable file.
 *
 * Format: gzip-compressed JSON lines (a manifest, then table rows and files), encrypted in chunks with
 * libsodium secretstream (XChaCha20-Poly1305), so large backups never sit in memory and any tampering
 * is detected on restore. Works the same for SQLite and MySQL. The key is VENDRA_BACKUP_KEY, or derived
 * from APP_KEY when that isn't set (keep APP_KEY safe: without it the backup can't be opened).
 */
class Backup
{
    private const MAGIC = "VNDRBK1\n";

    private const CHUNK = 65536;

    /** Tables rebuilt by the app itself rather than restored. */
    private const SKIP_TABLES = ['migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'];

    public static function disk(): string
    {
        return (string) config('vendra.backup_disk', 'local');
    }

    public static function key(): string
    {
        $configured = (string) config('vendra.backup_key', '');
        if ($configured !== '') {
            $raw = base64_decode(str_starts_with($configured, 'base64:') ? substr($configured, 7) : $configured, true);
            if ($raw === false || strlen($raw) !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES) {
                throw new RuntimeException('VENDRA_BACKUP_KEY must be 32 random bytes, base64-encoded.');
            }

            return $raw;
        }

        return hash_hkdf('sha256', (string) config('app.key'), SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES, 'vendra-backup-v1');
    }

    /** @return array{name: string, bytes: int, tables: int, rows: int, files: int} */
    public function create(): array
    {
        $plain = tempnam(sys_get_temp_dir(), 'vbk');
        $gz = gzopen($plain, 'wb6');
        $tables = $this->tables();
        $rows = 0;
        $files = 0;
        $write = function (array $line) use ($gz) {
            gzwrite($gz, json_encode($line, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
        };
        $write(['manifest' => [
            'app' => 'vendra', 'version' => 1, 'created_at' => now()->toIso8601String(),
            'driver' => DB::connection()->getDriverName(), 'last_migration' => $this->lastMigration(), 'tables' => $tables,
        ]]);
        foreach ($tables as $table) {
            $write(['table' => $table]);
            $q = DB::table($table);
            $orderBy = Schema::hasColumn($table, 'id') ? 'id' : Schema::getColumnListing($table)[0];
            foreach ($q->orderBy($orderBy)->lazy(500) as $row) {
                $write(['row' => $this->encodeRow((array) $row)]);
                $rows++;
            }
        }
        foreach ($this->fileDisks() as $disk) {
            foreach (Storage::disk($disk)->allFiles() as $path) {
                if (str_starts_with($path, 'backups/') || str_starts_with($path, '.')) {
                    continue;
                }
                $write(['file' => ['disk' => $disk, 'path' => $path, 'data' => base64_encode((string) Storage::disk($disk)->get($path))]]);
                $files++;
            }
        }
        gzclose($gz);

        $enc = tempnam(sys_get_temp_dir(), 'vbk');
        $this->encrypt($plain, $enc);
        @unlink($plain);
        $name = 'backups/vendra-'.now()->utc()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.vbk';
        $in = fopen($enc, 'rb');
        $ok = Storage::disk(self::disk())->writeStream($name, $in);
        if (is_resource($in)) {
            fclose($in);
        }
        $bytes = filesize($enc);
        @unlink($enc);
        if (! $ok) {
            throw new RuntimeException('The backup could not be saved to the "'.self::disk().'" disk.');
        }

        return ['name' => $name, 'bytes' => $bytes, 'tables' => count($tables), 'rows' => $rows, 'files' => $files];
    }

    /** Keeps the newest $keep backups. */
    public function prune(int $keep): int
    {
        $all = $this->list();
        $old = array_slice($all, $keep);
        Storage::disk(self::disk())->delete($old);

        return count($old);
    }

    /** @return string[] newest first */
    public function list(): array
    {
        $files = array_filter(Storage::disk(self::disk())->files('backups'), fn ($f) => str_ends_with($f, '.vbk'));
        rsort($files);

        return array_values($files);
    }

    /** Reads a backup's manifest (proves the key works and the file is intact up to there). */
    public function manifest(string $name): array
    {
        foreach ($this->lines($name) as $line) {
            return $line['manifest'] ?? throw new RuntimeException('Not a Vendra backup.');
        }
        throw new RuntimeException('The backup is empty.');
    }

    /** Replaces all data and uploaded files with the backup's contents. @return array{rows: int, files: int} */
    public function restore(string $name, bool $force = false): array
    {
        $manifest = $this->manifest($name);
        if (! $force && $manifest['last_migration'] !== $this->lastMigration()) {
            throw new RuntimeException("This backup was made on a different app version ({$manifest['last_migration']}). Run the same version, or use --force.");
        }
        // Decrypt and check the whole file before touching anything.
        $count = 0;
        foreach ($this->lines($name) as $_) {
            $count++;
        }

        $rows = 0;
        $files = 0;
        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($name, $manifest, &$rows) {
                foreach ($manifest['tables'] as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->delete();
                    }
                }
                $table = null;
                $batch = [];
                $flush = function () use (&$batch, &$table) {
                    if ($batch && $table && Schema::hasTable($table)) {
                        DB::table($table)->insert($batch);
                    }
                    $batch = [];
                };
                foreach ($this->lines($name) as $line) {
                    if (isset($line['table'])) {
                        $flush();
                        $table = $line['table'];
                    } elseif (isset($line['row'])) {
                        $batch[] = $this->decodeRow($line['row']);
                        $rows++;
                        if (count($batch) >= 200) {
                            $flush();
                        }
                    }
                }
                $flush();
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }
        foreach ($this->fileDisks() as $disk) {
            foreach (Storage::disk($disk)->allFiles() as $path) {
                if (! str_starts_with($path, 'backups/') && ! str_starts_with($path, '.')) {
                    Storage::disk($disk)->delete($path);
                }
            }
        }
        foreach ($this->lines($name) as $line) {
            if (isset($line['file']) && in_array($line['file']['disk'], $this->fileDisks(), true)) {
                Storage::disk($line['file']['disk'])->put($line['file']['path'], base64_decode($line['file']['data']));
                $files++;
            }
        }

        return ['rows' => $rows, 'files' => $files];
    }

    /** @return \Generator<int, array> */
    private function lines(string $name): \Generator
    {
        $plain = tempnam(sys_get_temp_dir(), 'vbk');
        try {
            $this->decrypt($name, $plain);
            $gz = gzopen($plain, 'rb');
            while (($line = gzgets($gz)) !== false) {
                if (trim($line) !== '') {
                    yield json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                }
            }
            gzclose($gz);
        } finally {
            @unlink($plain);
        }
    }

    private function encrypt(string $from, string $to): void
    {
        [$state, $header] = sodium_crypto_secretstream_xchacha20poly1305_init_push(self::key());
        $in = fopen($from, 'rb');
        $out = fopen($to, 'wb');
        fwrite($out, self::MAGIC.$header);
        do {
            $chunk = (string) fread($in, self::CHUNK);
            $last = feof($in);
            fwrite($out, sodium_crypto_secretstream_xchacha20poly1305_push($state, $chunk, '',
                $last ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE));
        } while (! $last);
        fclose($in);
        fclose($out);
    }

    private function decrypt(string $name, string $to): void
    {
        $in = Storage::disk(self::disk())->readStream($name) ?: throw new RuntimeException("Backup {$name} not found.");
        $out = fopen($to, 'wb');
        try {
            $magic = fread($in, strlen(self::MAGIC));
            if ($magic !== self::MAGIC) {
                throw new RuntimeException('Not a Vendra backup file.');
            }
            $header = $this->readExactly($in, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
            $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, self::key());
            $chunkSize = self::CHUNK + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES;
            $final = false;
            while (! $final) {
                $c = $this->readExactly($in, $chunkSize, true);
                if ($c === '') {
                    throw new RuntimeException('The backup file is incomplete.');
                }
                $res = sodium_crypto_secretstream_xchacha20poly1305_pull($state, $c);
                if ($res === false) {
                    throw new RuntimeException('The backup can’t be opened: wrong key, or the file was changed or damaged.');
                }
                [$plain, $tag] = $res;
                fwrite($out, $plain);
                $final = $tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL;
            }
        } finally {
            fclose($in);
            fclose($out);
        }
    }

    private function readExactly($stream, int $n, bool $allowShort = false): string
    {
        $buf = '';
        while (strlen($buf) < $n && ! feof($stream)) {
            $part = fread($stream, $n - strlen($buf));
            if ($part === false || $part === '') {
                break;
            }
            $buf .= $part;
        }
        if (! $allowShort && strlen($buf) !== $n) {
            throw new RuntimeException('The backup file is damaged.');
        }

        return $buf;
    }

    /** Binary-safe: values that aren't valid UTF-8 are stored base64-tagged. */
    private function encodeRow(array $row): array
    {
        foreach ($row as $k => $v) {
            if (is_string($v) && ! mb_check_encoding($v, 'UTF-8')) {
                $row[$k] = ['__b64' => base64_encode($v)];
            }
        }

        return $row;
    }

    private function decodeRow(array $row): array
    {
        foreach ($row as $k => $v) {
            if (is_array($v) && isset($v['__b64'])) {
                $row[$k] = base64_decode($v['__b64']);
            }
        }

        return $row;
    }

    private function tables(): array
    {
        $names = array_map(fn ($t) => is_array($t) ? $t['name'] : $t, Schema::getTables());
        $names = array_values(array_filter($names, fn ($t) => ! in_array($t, self::SKIP_TABLES, true) && ! str_starts_with($t, 'sqlite_')));
        sort($names);

        return $names;
    }

    private function lastMigration(): ?string
    {
        return Schema::hasTable('migrations') ? DB::table('migrations')->orderByDesc('id')->value('migration') : null;
    }

    /** Server-side disks are included; R2 buckets already keep files safe on their own. */
    private function fileDisks(): array
    {
        return array_values(array_unique(array_filter(
            [config('vendra.media_disk'), config('vendra.files_disk')],
            fn ($d) => config("filesystems.disks.$d.driver") === 'local'
        ) ?: []));
    }
}
