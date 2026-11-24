<?php

namespace App\Services;

use App\Models\Business;
use App\Models\CatalogItem;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Imports catalog items from a CSV (Excel / Google Sheets "Save as CSV").
 * All rows are checked first; if any row has a problem nothing is imported, and every problem is listed by row.
 * Items whose name already exists are skipped, never overwritten.
 */
class CatalogImport
{
    public const COLUMNS = ['type', 'name', 'category', 'price', 'duration_minutes', 'stock', 'description', 'active'];

    public const MAX_ROWS = 500;

    /** @return array{created: int, skipped: string[], errors: string[]} */
    public function import(Business $business, string $path): array
    {
        $rows = $this->read($path);
        if (isset($rows['error'])) {
            return ['created' => 0, 'skipped' => [], 'errors' => [$rows['error']]];
        }
        $existing = CatalogItem::forBusiness($business)->pluck('name')->map(fn ($n) => mb_strtolower(trim($n)))->flip();
        $errors = [];
        $skipped = [];
        $new = [];
        $seen = [];
        foreach ($rows as $line => $r) {
            $v = Validator::make($r, [
                'type' => 'required|in:service,product',
                'name' => 'required|string|max:120',
                'category' => 'nullable|string|max:60',
                'price' => 'required|numeric|min:0|max:1000000',
                'duration_minutes' => 'nullable|integer|min:15|max:600',
                'stock' => 'nullable|integer|min:0|max:100000',
                'description' => 'nullable|string|max:1000',
                'active' => 'nullable|in:yes,no,1,0,true,false,y,n',
            ], [], ['duration_minutes' => 'duration', 'price' => 'price']);
            if ($v->fails()) {
                foreach ($v->errors()->all() as $msg) {
                    $errors[] = "Row {$line}: {$msg}";
                }
                continue;
            }
            $key = mb_strtolower(trim($r['name']));
            if (isset($existing[$key]) || isset($seen[$key])) {
                $skipped[] = "Row {$line}: “{$r['name']}” is already in your catalog";
                continue;
            }
            $seen[$key] = true;
            $new[] = $r;
        }
        if ($errors) {
            return ['created' => 0, 'skipped' => $skipped, 'errors' => array_slice($errors, 0, 30)];
        }
        DB::transaction(function () use ($business, $new) {
            foreach ($new as $r) {
                $item = new CatalogItem([
                    'type' => $r['type'],
                    'name' => trim($r['name']),
                    'category' => filled($r['category'] ?? null) ? trim($r['category']) : null,
                    'description' => filled($r['description'] ?? null) ? trim($r['description']) : null,
                    'price_cents' => Money::toCents($r['price']),
                    'duration_minutes' => $r['type'] === 'service' ? ((int) ($r['duration_minutes'] ?? 0) ?: 60) : null,
                    'is_active' => ! in_array($r['active'] ?? 'yes', ['no', '0', 'false', 'n'], true),
                    'requires_approval' => false,
                ]);
                $item->business_id = $business->id;
                if ($r['type'] === 'product' && filled($r['stock'] ?? null)) {
                    $item->stock_on_hand = (int) $r['stock'];
                }
                $item->save();
            }
        });

        return ['created' => count($new), 'skipped' => $skipped, 'errors' => []];
    }

    /** @return array<int, array<string, ?string>>|array{error: string} rows keyed by spreadsheet line number */
    private function read(string $path): array
    {
        $raw = (string) file_get_contents($path);
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw); // Excel's UTF-8 marker
        if (! mb_check_encoding($raw, 'UTF-8')) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252'); // older Excel "CSV" files
        }
        $first = strtok($raw, "\n") ?: '';
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        $fh = fopen('php://temp', 'r+');
        fwrite($fh, $raw);
        rewind($fh);
        $header = fgetcsv($fh, 0, $delimiter, '"', '');
        if (! $header) {
            return ['error' => 'The file is empty.'];
        }
        $header = array_map(fn ($h) => str_replace([' ', '-'], '_', mb_strtolower(trim((string) $h))), $header);
        $header = array_map(fn ($h) => ['duration' => 'duration_minutes', 'minutes' => 'duration_minutes', 'stock_on_hand' => 'stock', 'is_active' => 'active'][$h] ?? $h, $header);
        if (array_diff(['type', 'name', 'price'], $header)) {
            return ['error' => 'The first row must have column names, including type, name and price. Download the template to see the format.'];
        }
        $rows = [];
        $line = 1;
        while (($cells = fgetcsv($fh, 0, $delimiter, '"', '')) !== false) {
            $line++;
            if (count(array_filter($cells, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue; // blank line
            }
            if (count($rows) >= self::MAX_ROWS) {
                return ['error' => 'Up to '.self::MAX_ROWS.' rows can be imported at a time.'];
            }
            $row = [];
            foreach ($header as $i => $h) {
                if (in_array($h, self::COLUMNS, true)) {
                    $value = isset($cells[$i]) ? trim((string) $cells[$i]) : '';
                    // Spreadsheet formulas are never evaluated or stored as formulas.
                    $row[$h] = $value === '' ? null : ltrim($value, '=+@');
                }
            }
            if (isset($row['type'])) {
                $row['type'] = mb_strtolower($row['type']);
            }
            if (isset($row['active'])) {
                $row['active'] = mb_strtolower($row['active']);
            }
            if (isset($row['price'])) {
                $row['price'] = str_replace(['₱', 'PHP', 'php', ',', ' '], '', $row['price']);
            }
            $rows[$line] = $row;
        }
        fclose($fh);

        return $rows ?: ['error' => 'No rows to import were found under the column names.'];
    }

    public static function template(): string
    {
        return implode("\r\n", [
            implode(',', self::COLUMNS),
            'service,Haircut,Hair,250,45,,Wash and cut,yes',
            'service,Hair Spa,Hair,600,60,,,yes',
            'product,Argan Hair Oil 50ml,Hair care,450,,12,,yes',
        ])."\r\n";
    }
}
