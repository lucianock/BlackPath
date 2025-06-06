<?php

namespace App\Console\Commands;

use App\Models\Scan;
use App\Services\ScannerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RunScan extends Command
{
    protected $signature = 'scan:run {scan_id}';
    protected $description = 'Run a scan process in background';

    protected $scannerService;

    public function __construct(ScannerService $scannerService)
    {
        parent::__construct();
        $this->scannerService = $scannerService;
    }

    public function handle()
    {
        $scanId = $this->argument('scan_id');
        $scan = Scan::find($scanId);

        if (!$scan) {
            $this->error('Scan not found');
            return 1;
        }

        try {
            $this->scannerService->setScan($scan);

            // Ejecutar Nmap
            $this->info('Starting port scan...');
            $this->scannerService->runNmap($scan->domain, $scan->id);

            // Verificar si el escaneo fue cancelado
            if ($this->wasCancelled($scan)) {
                return 1;
            }

            // Analizar servidor web
            $this->info('Analyzing web server...');
            $this->scannerService->analyzeWebServer($scan->domain, $scan->id);

            // Verificar si el escaneo fue cancelado
            if ($this->wasCancelled($scan)) {
                return 1;
            }

            // Ejecutar Gobuster
            $this->info('Scanning directories...');
            $this->scannerService->runGobuster($scan->domain, $scan->id, $scan->wordlist);

            $scan->status = 'completed';
            $scan->finished_at = now();
            $scan->save();

            // Limpiar cache
            Cache::forget('scan_' . $scan->id . '_current_tool');
            Cache::forget('scan_' . $scan->id . '_start_time');

            return 0;

        } catch (\Exception $e) {
            Log::error("Error running scan: " . $e->getMessage());
            $scan->status = 'failed';
            $scan->error = $e->getMessage();
            $scan->finished_at = now();
            $scan->save();

            // Limpiar cache
            Cache::forget('scan_' . $scan->id . '_current_tool');
            Cache::forget('scan_' . $scan->id . '_start_time');

            return 1;
        }
    }

    private function wasCancelled(Scan $scan): bool
    {
        $scan->refresh();
        return $scan->status === 'cancelled';
    }
} 