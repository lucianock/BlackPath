<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ScannerService;
use Illuminate\Support\Facades\Log;

class ProcessScan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $scanId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $scanId)
    {
        $this->scanId = $scanId;
    }

    /**
     * Execute the job.
     */
    public function handle(ScannerService $scannerService): void
    {
        try {
            $scannerService->startScanProcess($this->scanId);
        } catch (\Exception $e) {
            Log::error("Error processing scan {$this->scanId}: " . $e->getMessage());
            $this->fail($e);
        }
    }

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 1;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 3600;
} 