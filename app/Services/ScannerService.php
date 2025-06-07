<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use GuzzleHttp\Client;
use App\Models\Scan;
use App\Models\ScanResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ScannerService
{
    private $client;
    private $scan;
    private $processes = [];
    private const CONTAINER_NAME = 'url-scanner-tools';

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 5,
            'verify' => false,
            'connect_timeout' => 3
        ]);

        // Set PHP timeout to 5 minutes for long running scans
        set_time_limit(300);
    }

    public function setScan(Scan $scan)
    {
        $this->scan = $scan;
        return $this;
    }

    private function updateProgress($progress, $tool, $message = null)
    {
        if ($this->scan) {
            // Parse Gobuster progress if available
            if ($tool === 'gobuster' && preg_match('/Progress: (\d+) \/ (\d+) \(([0-9.]+)%\)/', $message, $matches)) {
                $current = $matches[1];
                $total = $matches[2];
                $progress = 55 + (40 * ($current / $total)); // 55-95% range for Gobuster
            }

            $stages = [
                'http_check' => ['weight' => 10, 'name' => 'Checking website accessibility'],
                'nmap' => ['weight' => 40, 'name' => 'Analyzing security (Port scanning)'],
                'gobuster' => ['weight' => 50, 'name' => 'Discovering resources']
            ];

            $this->scan->progress = round($progress, 1);
            $this->scan->status_message = $message ?? $stages[$tool]['name'] ?? 'Processing...';
            Cache::put('scan_' . $this->scan->id . '_stage', $tool, 3600);
            Cache::put('scan_' . $this->scan->id . '_progress', $progress, 3600);
            Cache::put('scan_' . $this->scan->id . '_message', $this->scan->status_message, 3600);
        }
    }

    public function startScan()
    {
        try {
            // Aumentar el tiempo máximo de ejecución a 5 minutos
            ini_set('max_execution_time', 300);
            set_time_limit(300);

            // Iniciar verificación básica HTTP
            $this->updateProgress(5, 'http_check', 'Checking if website is accessible...');

            $response = $this->client->request('GET', $this->scan->domain);
            $this->updateProgress(10, 'http_check', 'Website is accessible');

            // Run nmap synchronously
            $this->updateProgress(15, 'nmap', 'Starting port scan...');
            $nmapOutput = $this->runNmap();

            // Guardar resultado de nmap
            ScanResult::create([
                'scan_id' => $this->scan->id,
                'tool' => 'nmap',
                'raw_output' => $nmapOutput
            ]);

            $this->updateProgress(50, 'nmap', 'Port scan completed');

            // Run gobuster synchronously
            $this->updateProgress(55, 'gobuster', 'Starting directory scan...');
            $gobusterOutput = $this->runGobuster();

            // Guardar resultado de gobuster
            ScanResult::create([
                'scan_id' => $this->scan->id,
                'tool' => 'gobuster',
                'raw_output' => $gobusterOutput
            ]);

            $this->updateProgress(95, 'gobuster', 'Directory scan completed');

            // Actualizar estado final
            $this->updateProgress(100, 'completed', 'Scan completed successfully');
            $this->scan->status = 'completed';
            $this->scan->finished_at = now();
            $this->scan->save();

            return true;

        } catch (\Exception $e) {
            Log::error("Error during scan: " . $e->getMessage());
            $this->scan->status = 'failed';
            $this->scan->error = $e->getMessage();
            $this->scan->save();
            return false;
        }
    }

    private function runNmap()
    {
        $domain = parse_url($this->scan->domain, PHP_URL_HOST) ?: $this->scan->domain;

        $nmapCmd = sprintf(
            'nmap -sV -sC -Pn --max-retries 2 --host-timeout 30s -p80,443,8080,8443 %s 2>&1',
            escapeshellarg($domain)
        );

        $command = [
            'docker',
            'exec',
            'url-scanner-tools',
            'sh',
            '-c',
            $nmapCmd
        ];

        $process = new Process($command);
        $process->setTimeout(45);
        $process->setEnv(['PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin']);

        Log::info("Starting nmap scan for {$domain}", [
            'command' => $nmapCmd
        ]);

        $output = '';
        $process->run(function ($type, $buffer) use (&$output) {
            $output .= $buffer;
            if (Process::ERR === $type) {
                Log::error('Nmap Error: ' . $buffer);
            } else {
                Log::info('Nmap Output: ' . $buffer);
            }
        });

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Nmap scan failed: " . $process->getErrorOutput());
        }

        if (empty(trim($output))) {
            throw new \RuntimeException("Nmap scan produced no output");
        }

        return $output;
    }

    private function runGobuster()
    {
        $domain = parse_url($this->scan->domain, PHP_URL_HOST) ?: $this->scan->domain;
        $wordlist = $this->scan->wordlist === 'common' ? '/app/wordlists/common.txt' : '/app/wordlists/medium.txt';

        $gobusterCmd = 'gobuster dir'
            . ' -u ' . escapeshellarg($this->scan->domain)
            . ' -w ' . escapeshellarg($wordlist)
            . ' -t 20'
            . ' --timeout 10s'
            . ' --status-codes-blacklist 404'
            . ' --follow-redirect'
            . ' 2>&1';

        $command = [
            'docker',
            'exec',
            'url-scanner-tools',
            'sh',
            '-c',
            $gobusterCmd
        ];

        $process = new Process($command);
        $process->setTimeout(300);
        $process->setIdleTimeout(60);
        $process->setEnv(['PATH' => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin']);

        Log::info("Starting gobuster scan for {$domain}");

        try {
            $output = '';
            $process->run(function ($type, $buffer) use (&$output) {
                $output .= $buffer;

                // Update progress based on Gobuster output
                if (preg_match('/Progress: (\d+) \/ (\d+) \(([0-9.]+)%\)/', $buffer, $matches)) {
                    $this->updateProgress(0, 'gobuster', $buffer);
                }

                if (Process::ERR === $type) {
                    Log::error('Gobuster Error: ' . $buffer);
                } else {
                    Log::info('Gobuster Output: ' . $buffer);
                }
            });

            if (!$process->isSuccessful()) {
                throw new \RuntimeException("Gobuster scan failed: " . $process->getErrorOutput());
            }

            if (empty(trim($output))) {
                throw new \RuntimeException("Gobuster scan produced no output");
            }

            return $output;
        } catch (\Exception $e) {
            Log::error("Error running Gobuster: " . $e->getMessage());
            throw $e;
        }
    }

    public function checkProgress()
    {
        return [
            'progress' => $this->scan->progress,
            'status' => $this->scan->status,
            'message' => $this->scan->status_message
        ];
    }

    public function cancel()
    {
        $this->scan->status = 'cancelled';
        $this->scan->finished_at = now();
        $this->scan->save();
    }

    public function startScanProcess($scanId)
    {
        try {
            // Get scan info from cache
            $scanInfo = Cache::get($scanId . '_info');
            if (!$scanInfo) {
                throw new \Exception("Scan not found: {$scanId}");
            }

            $domain = $scanInfo['domain'];
            $wordlist = $scanInfo['wordlist'];

            // Update status to running
            Cache::put($scanId . '_status', 'running', 3600);
            Cache::put($scanId . '_stage', 'http_check', 3600);
            Cache::put($scanId . '_progress', 5, 3600);
            Cache::put($scanId . '_message', 'Checking if website is accessible...', 3600);

            // Check website accessibility
            try {
                $response = $this->client->request('GET', $domain);
                Cache::put($scanId . '_progress', 10, 3600);
                Cache::put($scanId . '_message', 'Website is accessible', 3600);
            } catch (\Exception $e) {
                Log::error("Website not accessible: " . $e->getMessage());
                Cache::put($scanId . '_status', 'failed', 3600);
                Cache::put($scanId . '_message', 'Website not accessible: ' . $e->getMessage(), 3600);
                return;
            }

            // Run Nmap scan
            Cache::put($scanId . '_stage', 'nmap', 3600);
            Cache::put($scanId . '_progress', 15, 3600);
            Cache::put($scanId . '_message', 'Starting port scan...', 3600);

            try {
                $nmapOutput = $this->runNmapScan($domain);
                Cache::put($scanId . '_results_nmap', $nmapOutput, 3600);
                Cache::put($scanId . '_progress', 50, 3600);
                Cache::put($scanId . '_message', 'Port scan completed', 3600);
            } catch (\Exception $e) {
                Log::error("Nmap scan failed: " . $e->getMessage());
                Cache::put($scanId . '_status', 'failed', 3600);
                Cache::put($scanId . '_message', 'Port scan failed: ' . $e->getMessage(), 3600);
                return;
            }

            // Run Gobuster scan
            Cache::put($scanId . '_stage', 'gobuster', 3600);
            Cache::put($scanId . '_progress', 55, 3600);
            Cache::put($scanId . '_message', 'Starting directory scan...', 3600);

            try {
                $gobusterOutput = $this->runGobusterScan($domain, $wordlist);
                Cache::put($scanId . '_results_gobuster', $gobusterOutput, 3600);
                Cache::put($scanId . '_progress', 95, 3600);
                Cache::put($scanId . '_message', 'Directory scan completed', 3600);
            } catch (\Exception $e) {
                Log::error("Gobuster scan failed: " . $e->getMessage());
                Cache::put($scanId . '_status', 'failed', 3600);
                Cache::put($scanId . '_message', 'Directory scan failed: ' . $e->getMessage(), 3600);
                return;
            }

            // Mark scan as completed
            Cache::put($scanId . '_status', 'completed', 3600);
            Cache::put($scanId . '_progress', 100, 3600);
            Cache::put($scanId . '_message', 'Scan completed successfully', 3600);
            Cache::put($scanId . '_finished_at', now(), 3600);

        } catch (\Exception $e) {
            Log::error("Error processing scan {$scanId}: " . $e->getMessage());
            Cache::put($scanId . '_status', 'failed', 3600);
            Cache::put($scanId . '_message', 'Scan failed: ' . $e->getMessage(), 3600);
        }
    }

    private function runNmapScan($domain)
    {
        try {
            $domain = parse_url($domain, PHP_URL_HOST) ?: $domain;

            // Basic nmap command without complex options
            $nmapCmd = sprintf(
                'nmap -p 80,443 -Pn %s',
                escapeshellarg($domain)
            );

            Log::info("Executing nmap command: " . $nmapCmd);

            $process = new Process([
                'docker',
                'exec',
                '-u',
                'scanner',
                self::CONTAINER_NAME,
                'nmap',
                '-p',
                '80,443',
                '-Pn',
                $domain
            ]);

            $process->setTimeout(45);

            Log::info("Starting nmap scan for {$domain}");

            $output = '';
            $errorOutput = '';

            $process->run(function ($type, $buffer) use (&$output, &$errorOutput) {
                if (Process::ERR === $type) {
                    $errorOutput .= $buffer;
                    Log::error('Nmap Error Output: ' . $buffer);
                } else {
                    $output .= $buffer;
                    Log::info('Nmap Output: ' . $buffer);
                }
            });

            if (!$process->isSuccessful()) {
                Log::error("Nmap process failed with exit code: " . $process->getExitCode());
                Log::error("Nmap error output: " . $errorOutput);
                Log::error("Nmap standard output: " . $output);
                throw new \RuntimeException("Nmap scan failed: " . ($errorOutput ?: $output));
            }

            if (empty($output)) {
                throw new \RuntimeException("Nmap scan produced no output");
            }

            return $output;

        } catch (\Exception $e) {
            Log::error("Error in runNmapScan: " . $e->getMessage());
            Log::error("Exception trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    private function runGobusterScan($inputUrl, $wordlist)
    {
        // Kill any hanging gobuster processes first
        $killCmd = "pkill -f gobuster || true";
        $killProcess = new Process(['docker', 'exec', self::CONTAINER_NAME, 'sh', '-c', $killCmd]);
        $killProcess->run();

        // Sanitizar entrada
        $inputUrl = trim($inputUrl);
        if (!preg_match('#^https?://#', $inputUrl)) {
            $inputUrl = 'http://' . $inputUrl;
        }

        // Parsear URL para obtener dominio y path (si tiene)
        $parsed = parse_url($inputUrl);
        $scheme = $parsed['scheme'] ?? 'http';
        $host = $parsed['host'] ?? '';
        $path = isset($parsed['path']) ? rtrim($parsed['path'], '/') : '';

        if (empty($host)) {
            throw new \RuntimeException("Invalid domain or URL provided");
        }

        $baseUrl = $scheme . '://' . $host . $path;

        // Detectar si hay redirección a HTTPS
        $headers = @get_headers($baseUrl, 1);
        if (is_array($headers) && isset($headers[0]) && strpos($headers[0], '301') !== false) {
            foreach ($headers as $key => $value) {
                if (stripos($key, 'Location') !== false) {
                    $locations = is_array($value) ? $value : [$value];
                    foreach ($locations as $loc) {
                        if (stripos($loc, 'https://') === 0) {
                            $baseUrl = 'https://' . $host . $path;
                            Log::info("Redirect detected, switching to HTTPS: $baseUrl");
                            break 2;
                        }
                    }
                }
            }

        }

        Log::info("Processing Gobuster scan for domain: $baseUrl");

        $wordlistPath = $wordlist === 'common' ? '/app/wordlists/common.txt' : '/app/wordlists/medium.txt';

        // Paso 1: Detectar status/length con curl
        $randomPath = '/' . \Str::uuid();
        $testUrl = rtrim($baseUrl, '/') . $randomPath;
        $curlCmd = sprintf('curl -s -o /dev/null -w "%%{http_code} %%{size_download}" %s', escapeshellarg($testUrl));

        $curlProcess = new Process(['docker', 'exec', self::CONTAINER_NAME, 'sh', '-c', $curlCmd]);
        $curlProcess->run();
        $curlOutput = trim($curlProcess->getOutput());

        $dummyStatus = null;
        $dummyLength = null;

        if ($curlProcess->isSuccessful() && preg_match('/^\d{3} \d+$/', $curlOutput)) {
            [$dummyStatus, $dummyLength] = explode(' ', $curlOutput);
        } else {
            Log::warning("Curl failed inside container, using PHP fallback for: $testUrl");

            $headers = @get_headers($testUrl, 1);
            if (is_array($headers) && isset($headers[0])) {
                preg_match('/\d{3}/', $headers[0], $matches);
                $dummyStatus = $matches[0] ?? '404';

                $context = stream_context_create([
                    'http' => ['method' => 'GET', 'timeout' => 10],
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
                ]);

                $body = @file_get_contents($testUrl, false, $context);
                $dummyLength = is_string($body) ? strlen($body) : 0;
            }
        }

        // Si no se pudo detectar, usar valores seguros
        if (!$dummyStatus || !$dummyLength) {
            Log::error("Failed to detect dummy status/length. Using fallback values 404/0");
            $dummyStatus = '404';
            $dummyLength = '0';
        }

        Log::info("Detected dummy status: $dummyStatus | length: $dummyLength");

        $statusesToExclude = implode(',', array_filter([$dummyStatus, '301', '302']));

        $gobusterCmd = sprintf(
            'gobuster dir -u %s -w %s -t 20 --timeout 10s --no-error -b %s --exclude-length %s 2>&1',
            escapeshellarg($inputUrl),
            escapeshellarg($wordlistPath),
            escapeshellarg($statusesToExclude),
            escapeshellarg($dummyLength)
        );


        Log::info("Executing gobuster command: " . $gobusterCmd);

        $process = new Process([
            'docker',
            'exec',
            self::CONTAINER_NAME,
            'sh',
            '-c',
            $gobusterCmd
        ]);

        $process->setTimeout(300);
        $process->setIdleTimeout(60);

        Log::info("Starting gobuster scan for {$baseUrl}");

        $output = '';
        $process->run(function ($type, $buffer) use (&$output) {
            $output .= $buffer;
            if (Process::ERR === $type) {
                Log::error('Gobuster Error: ' . $buffer);
            } else {
                Log::info('Gobuster Output: ' . $buffer);
            }
        });

        if (!$process->isSuccessful()) {
            Log::error("Gobuster process failed with exit code: " . $process->getExitCode());
            Log::error("Gobuster error output: " . $process->getErrorOutput());
            throw new \RuntimeException("Gobuster scan failed: " . $process->getErrorOutput());
        }

        return $output;
    }

}