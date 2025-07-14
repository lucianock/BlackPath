<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ScannerService
{
    private $client;
    private const CONTAINER_NAME = 'url-scanner-tools';

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 5,
            'verify' => false,
            'connect_timeout' => 3
        ]);
    }

    public function startScanProcess($scanId)
    {
        try {
            $scanInfo = Cache::get($scanId . '_info');
            if (!$scanInfo) {
                throw new \Exception("Scan not found: {$scanId}");
            }
            $domain = $scanInfo['domain'];
            $wordlist = $scanInfo['wordlist'];

            Cache::put($scanId . '_status', 'running', 3600);
            Cache::put($scanId . '_stage', 'http_check', 3600);
            Cache::put($scanId . '_progress', 5, 3600);
            Cache::put($scanId . '_message', 'Checking if website is accessible...', 3600);

            // Check website accessibility
            try {
                $this->client->request('GET', $domain);
                Cache::put($scanId . '_progress', 10, 3600);
                Cache::put($scanId . '_message', 'Website is accessible', 3600);
            } catch (\Exception $e) {
                Cache::put($scanId . '_status', 'failed', 3600);
                Cache::put($scanId . '_message', 'Website not accessible: ' . $e->getMessage(), 3600);
                Cache::put($scanId . '_progress', 100, 3600);
                Cache::put($scanId . '_finished_at', now(), 3600);
                return;
            }

            // WhatWeb
            Cache::put($scanId . '_stage', 'whatweb', 3600);
            Cache::put($scanId . '_progress', 15, 3600);
            Cache::put($scanId . '_message', 'Analyzing website technologies...', 3600);
            try {
                $whatwebOutput = $this->runWhatWeb($domain);
                Cache::put($scanId . '_results_whatweb', $whatwebOutput, 3600);
                Cache::put($scanId . '_progress', 30, 3600);
                Cache::put($scanId . '_message', 'Technology analysis completed', 3600);
            } catch (\Exception $e) {
                Cache::put($scanId . '_status', 'failed', 3600);
                Cache::put($scanId . '_message', 'WhatWeb failed: ' . $e->getMessage(), 3600);
                Cache::put($scanId . '_progress', 100, 3600);
                Cache::put($scanId . '_finished_at', now(), 3600);
                return;
            }

            // Nmap
            Cache::put($scanId . '_stage', 'nmap', 3600);
            Cache::put($scanId . '_progress', 35, 3600);
            Cache::put($scanId . '_message', 'Starting port scan...', 3600);
            try {
                $nmapOutput = $this->runNmap($domain);
                Cache::put($scanId . '_results_nmap', $nmapOutput, 3600);
                Cache::put($scanId . '_progress', 60, 3600);
                Cache::put($scanId . '_message', 'Port scan completed', 3600);
            } catch (\Exception $e) {
                Cache::put($scanId . '_status', 'failed', 3600);
                Cache::put($scanId . '_message', 'Nmap failed: ' . $e->getMessage(), 3600);
                Cache::put($scanId . '_progress', 100, 3600);
                Cache::put($scanId . '_finished_at', now(), 3600);
                return;
            }

            // Gobuster
            Cache::put($scanId . '_stage', 'gobuster', 3600);
            Cache::put($scanId . '_progress', 65, 3600);
            Cache::put($scanId . '_message', 'Starting directory scan...', 3600);
            try {
                $gobusterOutput = $this->runGobuster($domain, $wordlist, $scanId);
                Cache::put($scanId . '_results_gobuster', $gobusterOutput, 3600);
                Cache::put($scanId . '_progress', 95, 3600);
                Cache::put($scanId . '_message', 'Directory scan completed', 3600);
            } catch (\Exception $e) {
                Cache::put($scanId . '_status', 'failed', 3600);
                Cache::put($scanId . '_message', 'Gobuster failed: ' . $e->getMessage(), 3600);
                Cache::put($scanId . '_progress', 100, 3600);
                Cache::put($scanId . '_finished_at', now(), 3600);
                return;
            }

            Cache::put($scanId . '_status', 'completed', 3600);
            Cache::put($scanId . '_progress', 100, 3600);
            Cache::put($scanId . '_message', 'Scan completed successfully', 3600);
            Cache::put($scanId . '_finished_at', now(), 3600);
        } catch (\Exception $e) {
            Cache::put($scanId . '_status', 'failed', 3600);
            Cache::put($scanId . '_message', 'Scan failed: ' . $e->getMessage(), 3600);
            Cache::put($scanId . '_progress', 100, 3600);
            Cache::put($scanId . '_finished_at', now(), 3600);
        }
    }

    private function runWhatWeb($domain)
    {
        $domain = parse_url($domain, PHP_URL_HOST) ?: $domain;
        $cmd = [
            'docker', 'exec', '-u', 'scanner', self::CONTAINER_NAME,
            'whatweb', '--color=never', '--log-json=-', $domain
        ];
        $process = new Process($cmd);
        $process->setTimeout(60);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException("WhatWeb failed: " . $process->getErrorOutput());
        }
        return $process->getOutput();
    }

    private function runNmap($domain)
    {
        $domain = parse_url($domain, PHP_URL_HOST) ?: $domain;
        $cmd = [
            'docker', 'exec', '-u', 'scanner', self::CONTAINER_NAME,
            'nmap', '-p', '80,443', '-Pn', $domain
        ];
        $process = new Process($cmd);
        $process->setTimeout(45);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Nmap failed: " . $process->getErrorOutput());
        }
        return $process->getOutput();
    }

    private function runGobuster($domain, $wordlist, $scanId = null)
    {
        $inputUrl = trim($domain);
        if (!preg_match('#^https?://#', $inputUrl)) {
            $inputUrl = 'http://' . $inputUrl;
        }
        $wordlistPath = $wordlist === 'common' ? '/app/wordlists/common.txt' : '/app/wordlists/medium.txt';
        $cmd = [
            'docker', 'exec', '-u', 'scanner', self::CONTAINER_NAME,
            'gobuster', 'dir',
            '-u', $inputUrl,
            '-w', $wordlistPath,
            '-t', '20',
            '--timeout', '10s',
            '--status-codes-blacklist', '404',
            '--follow-redirect'
        ];
        $process = new Process($cmd);
        $process->setTimeout(null);
        $output = '';
        $process->run(function ($type, $buffer) use (&$output, $scanId) {
            $output .= $buffer;
            if ($scanId && preg_match('/Progress: (\d+) \/ (\d+) \(([0-9.]+)%\)/', $buffer, $matches)) {
                $current = (int)$matches[1];
                $total = (int)$matches[2];
                $percentage = (float)$matches[3];
                \Cache::put($scanId . '_progress', 65 + (30 * ($current / $total)), 3600);
                \Cache::put($scanId . '_message', "Gobuster: $current / $total ($percentage%)", 3600);
            }
        });
        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Gobuster failed: " . $process->getErrorOutput());
        }
        return $output;
    }
}