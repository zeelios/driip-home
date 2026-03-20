<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queued job that exports all inventory data to a downloadable file.
 *
 * Dispatched by InventoryController::export(). The job runs asynchronously
 * on the default queue. Extend the handle() method to implement the actual
 * export logic (e.g. generate a CSV/XLSX and store in S3).
 */
class ExportInventoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  string  $requestedBy  UUID of the staff member who triggered the export.
     */
    public function __construct(
        public readonly string $requestedBy,
    ) {}

    /**
     * Execute the inventory export job.
     *
     * Implement the export logic here: query all inventory records, generate
     * a CSV or spreadsheet, upload to cloud storage, and optionally notify
     * the requesting staff member with a download link.
     *
     * @return void
     */
    public function handle(): void
    {
        // TODO: Implement inventory export logic.
        // Example: query Inventory with variant/warehouse, write CSV, store in S3,
        // then dispatch a notification to $this->requestedBy.
    }
}
