<?php

namespace App\Http\Controllers;

use App\Services\ScannerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;

class ScanController extends Controller
{
    protected $scannerService;

    public function __construct(ScannerService $scannerService)
    {
        $this->scannerService = $scannerService;
    }

    public function index()
    {
        // Get all scan IDs from cache
        $scanKeys = Cache::get('scan_ids', []);
        $scans = collect();

        foreach ($scanKeys as $scanId) {
            $scanInfo = Cache::get($scanId . '_info');
            if ($scanInfo) {
                $scanInfo['id'] = $scanId;
                $scanInfo['status'] = Cache::get($scanId . '_status', 'unknown');
                $scanInfo['progress'] = Cache::get($scanId . '_progress', 0);
                $scanInfo['started_at'] = Cache::get($scanId . '_start_time');
                $scanInfo['finished_at'] = Cache::get($scanId . '_finished_at');
                $scans->push((object)$scanInfo);
            }
        }

        return view('scans.index', [
            'scans' => $scans->sortByDesc('started_at')
        ]);
    }

    public function create()
    {
        return view('scans.create');
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'domain' => 'required|string|max:255',
                'wordlist' => 'required|in:common,medium'
            ]);

            $domain = $request->input('domain');
            if (!preg_match('/^https?:\/\//', $domain)) {
                $domain = 'http://' . $domain;
            }

            $scanId = uniqid('scan_');
            
            // Store scan info in cache
            Cache::put($scanId . '_info', [
                'domain' => $domain,
                'status' => 'queued',
                'progress' => 0,
                'wordlist' => $request->input('wordlist'),
                'started_at' => now()
            ], 3600);

            // Add scan ID to the list of scans
            $scanIds = Cache::get('scan_ids', []);
            $scanIds[] = $scanId;
            Cache::put('scan_ids', $scanIds, 3600);

            // Start scan process directly
            $this->scannerService->startScanProcess($scanId);

            return response()->json([
                'scan_id' => $scanId,
                'estimated_time' => $request->input('wordlist') === 'common' ? 45 : 60
            ]);

        } catch (\Exception $e) {
            Log::error("Error starting scan: " . $e->getMessage());
            return response()->json([
                'error' => 'Error starting scan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($scanId)
    {
        $scanInfo = Cache::get($scanId . '_info');
        
        if (!$scanInfo) {
            abort(404, 'Scan not found');
        }
        
        // Add the scan ID and other properties to the info
        $scanInfo['id'] = $scanId;
        $scanInfo['status'] = Cache::get($scanId . '_status', 'unknown');
        $scanInfo['progress'] = Cache::get($scanId . '_progress', 0);
        $scanInfo['started_at'] = Cache::get($scanId . '_start_time');
        $scanInfo['finished_at'] = Cache::get($scanId . '_finished_at');
        $scanInfo['error'] = Cache::get($scanId . '_message'); // Add error message if any
        
        $results = [
            'nmap' => [
                (object)[
                    'raw_output' => Cache::get($scanId . '_results_nmap')
                ]
            ],
            'gobuster' => [
                (object)[
                    'raw_output' => Cache::get($scanId . '_results_gobuster')
                ]
            ]
        ];
        
        return view('scans.show', [
            'scan' => (object)$scanInfo,
            'results' => $results
        ]);
    }

    public function status($scanId)
    {
        try {
            $stage = Cache::get($scanId . '_stage');
            $progress = Cache::get($scanId . '_progress', 0);
            $message = Cache::get($scanId . '_message');
            $status = Cache::get($scanId . '_status', 'running');
            $startTime = Cache::get($scanId . '_start_time');

            return response()->json([
                'status' => $status,
                'stage' => $stage,
                'progress' => round($progress, 1),
                'message' => $message,
                'elapsed_time' => $startTime ? now()->diffInSeconds($startTime) : 0
            ]);
        } catch (\Exception $e) {
            Log::error("Error checking scan status: " . $e->getMessage());
            return response()->json([
                'error' => 'Error checking scan status'
            ], 500);
        }
    }

    public function cancel($scanId)
    {
        try {
            Cache::put($scanId . '_status', 'cancelled', 3600);
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error("Error cancelling scan: " . $e->getMessage());
            return response()->json([
                'error' => 'Error cancelling scan'
            ], 500);
        }
    }

    public function export($scanId)
    {
        $scanInfo = Cache::get($scanId . '_info');
        $results = [
            'nmap' => [
                (object)[
                    'raw_output' => Cache::get($scanId . '_results_nmap')
                ]
            ],
            'gobuster' => [
                (object)[
                    'raw_output' => Cache::get($scanId . '_results_gobuster')
                ]
            ]
        ];
        
        if (!$scanInfo) {
            abort(404, 'Scan not found');
        }

        $pdf = PDF::loadView('scans.pdf', [
            'scan' => (object)$scanInfo,
            'results' => $results
        ]);

        return $pdf->download('scan-report-' . date('Y-m-d') . '.pdf');
    }
}
