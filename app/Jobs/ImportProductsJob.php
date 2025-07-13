<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Str;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImportProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function handle(): void
    {
        $fullPath = storage_path("app/{$this->filePath}");
        if (!file_exists($fullPath)) {
            Log::error("Import failed: File not found - {$this->filePath}");
            return;
        }


        $handle = fopen($fullPath, 'r');
        if (!$handle) {
            Log::error("Import failed: Cannot open file.");
            return;
        }

        $header = fgetcsv($handle, 1000, ',');

        $requiredHeaders = ['vendor_id', 'name', 'description', 'price', 'stock'];

        if ($header === false || array_diff($requiredHeaders, $header)) {
            Log::error("Import failed: Invalid CSV headers.");
            fclose($handle);
            return;
        }

        $batch = [];
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $data = array_combine($header, $row);
            Log::info("Processing row: " . json_encode($data));

            $validator = Validator::make($data, [
                'vendor_id' => 'required|exists:vendors,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                Log::warning("Skipped row: " . json_encode($data));
                continue;
            }

            $data['id'] = Str::uuid();
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            $batch[] = $data;

            if (count($batch) >= 500) {
                DB::table('products')->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('products')->insert($batch);
        }

        fclose($handle);
        Storage::delete($this->filePath);
        Log::info("Product import completed: {$this->filePath}");
    }
}
