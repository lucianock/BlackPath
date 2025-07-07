<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ScannerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RunScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    protected $scanId;

    public function __construct($scanId)
    {
        $this->scanId = $scanId;
    }

    public function handle(ScannerService $scanner)
    {
        try {
            // Set aggressive timeout limits
            ini_set('max_execution_time', '600');
            ini_set('default_socket_timeout', '600');
            set_time_limit(600);
            
            // Increase memory limit
            ini_set('memory_limit', '512M');
            
            // Disable output buffering
            if (ob_get_level()) ob_end_clean();
            
            // Start the scan process
            $scanner->startScanProcess($this->scanId);
        } catch (\Exception $e) {
            Log::error("Error in RunScanJob for scan {$this->scanId}: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            
            // Update cache with error status
            Cache::put($this->scanId . '_status', 'failed', 3600);
            Cache::put($this->scanId . '_message', 'Scan failed: ' . $e->getMessage(), 3600);
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("RunScanJob failed for scan {$this->scanId}: " . $exception->getMessage());
        Log::error("Stack trace: " . $exception->getTraceAsString());
        
        // Update cache with failure status
        Cache::put($this->scanId . '_status', 'failed', 3600);
        Cache::put($this->scanId . '_message', 'Scan failed: ' . $exception->getMessage(), 3600);
    }
} 