<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RandomDomainController extends Controller
{
    public function __invoke()
    {
        try {
            $filePath = storage_path('app/domains/domain-names.txt');
            
            // Check if the file exists
            if (!file_exists($filePath)) {
                Log::error('Domain names file not found at: ' . $filePath);
                throw new \Exception('Domain names file not found. Please make sure domain-names.txt exists in the storage/app/domains directory.');
            }

            // Read all domains from the file
            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new \Exception('Unable to read domain names file');
            }

            $domains = collect(explode("\n", $content))
                ->filter(function ($domain) {
                    return !empty(trim($domain));
                })
                ->map(function ($domain) {
                    return trim($domain);
                })
                ->values()
                ->toArray();

            if (empty($domains)) {
                throw new \Exception('No domains found in the file');
            }

            // Get a random domain from the list
            $randomDomain = $domains[array_rand($domains)];
            
            return response()->json(['domain' => $randomDomain]);

        } catch (\Exception $e) {
            Log::error('Error in RandomDomainController:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error getting random domain: ' . $e->getMessage()
            ], 500);
        }
    }
} 