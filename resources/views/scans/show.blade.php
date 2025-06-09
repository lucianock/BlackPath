@extends('layouts.app')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Configure SweetAlert2 default options
    const swalConfig = Swal.mixin({
        background: '#1a1a1a',
        color: '#fff',
        confirmButtonColor: '#4f46e5'
    });

    function openAllLinks() {
        // Get base domain
        const domain = '{{ $scan->domain }}';
        
        // Get all discovered resources
        const resources = document.querySelectorAll('[data-resource-path]');
        
        // Confirm before opening multiple tabs
        swalConfig.fire({
            title: 'Open all discovered resources?',
            text: `This will open ${resources.length} tabs. Make sure your browser allows pop-ups.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, open all',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                resources.forEach(resource => {
                    const path = resource.getAttribute('data-resource-path');
                    const url = new URL(path, domain).href;
                    window.open(url, '_blank');
                });
            }
        });
    }
</script>
@endpush

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="space-y-6">
        <!-- Scan Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">
                        Scan Results for {{ parse_url($scan->domain, PHP_URL_HOST) }}
                    </h2>
                    <div class="flex items-center space-x-3">
                        <button onclick="openAllLinks()" 
                                class="inline-flex items-center px-3 py-1 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                                <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                            </svg>
                            Open All
                        </button>
                        <a href="{{ route('scans.export', $scan->id) }}" 
                           class="inline-flex items-center px-3 py-1 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd" />
                            </svg>
                            Export
                        </a>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-6 text-sm text-gray-600 dark:text-gray-400">
                    <div>
                        <span class="font-medium">Started:</span>
                        {{ $scan->started_at ? $scan->started_at->format('Y-m-d H:i:s') : 'N/A' }}
                    </div>
                    <div>
                        <span class="font-medium">Completed:</span>
                        {{ $scan->finished_at ? $scan->finished_at->format('Y-m-d H:i:s') : 'N/A' }}
                    </div>
                </div>
            </div>
        </div>

        @if(isset($scan->error) && $scan->error && $scan->status === 'failed')
        <div class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg relative">
            <strong class="font-bold">Error:</strong>
            <span class="block sm:inline">{{ $scan->error }}</span>
        </div>
        @endif

        <!-- Technology Analysis -->
        @if(isset($results['whatweb']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="p-8">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Website Technologies</h3>
                @php
                    $whatwebOutput = $results['whatweb'][0]->raw_output;
                    $info = [
                        'server' => [],           // Tecnologías del servidor
                        'frontend' => [],         // Tecnologías del frontend
                        'security' => [],         // Características de seguridad
                        'contact' => [],          // Información de contacto
                        'location' => null,       // Ubicación del servidor
                        'redirects' => []         // Cadena de redirecciones
                    ];
                    
                    // Parse the standard WhatWeb output format
                    $lines = explode("\n", $whatwebOutput);
                    foreach ($lines as $line) {
                        if (preg_match('/^(https?:\/\/[^\s]+)\s+\[(.*?)\](.*)$/', $line, $matches)) {
                            $url = $matches[1];
                            $status = $matches[2];
                            $details = $matches[3];
                            
                            // Store redirect information
                            if (strpos($status, '302') !== false || strpos($status, '301') !== false) {
                                if (preg_match('/RedirectLocation\[(.*?)\]/', $details, $redirectMatch)) {
                                    $info['redirects'][$url] = $redirectMatch[1];
                                }
                            }
                            
                            // Extract and categorize information
                            preg_match_all('/\[(.*?)\]/', $details, $techMatches);
                            foreach ($techMatches[1] as $tech) {
                                // Server Technologies
                                if (preg_match('/^(Apache|nginx|IIS|PHP)(?:\[(.*?)\])?$/', $tech, $matches)) {
                                    $version = isset($matches[2]) ? $matches[2] : null;
                                    $info['server'][$matches[1]] = $version;
                                }
                                // Frontend Technologies
                                elseif (preg_match('/^(jQuery|Bootstrap|HTML5|JavaScript|CSS|Lightbox|YouTube)(?:\[(.*?)\])?$/', $tech, $matches)) {
                                    $version = isset($matches[2]) ? $matches[2] : null;
                                    $info['frontend'][$matches[1]] = $version;
                                }
                                // Security Headers
                                elseif (strpos($tech, 'content-security-policy') !== false) {
                                    $info['security'][] = 'Content Security Policy (CSP)';
                                }
                                // Contact Information
                                elseif (preg_match('/^Email\[(.*?)\]$/', $tech, $matches)) {
                                    $info['contact']['email'] = $matches[1];
                                }
                                // Location Information
                                elseif (preg_match('/^Country\[(.*?)\]/', $tech, $matches)) {
                                    $info['location'] = $matches[1];
                                }
                            }
                        }
                    }
                @endphp

                @if(count($info['redirects']) > 0)
                <div class="mb-8">
                    <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">Detected Redirections</h4>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        @foreach($info['redirects'] as $from => $to)
                        <div class="flex items-center space-x-2 text-sm">
                            <span class="text-gray-600 dark:text-gray-400">{{ parse_url($from, PHP_URL_HOST) }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-gray-600 dark:text-gray-400">{{ parse_url($to, PHP_URL_HOST) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Server Technologies -->
                    @if(count($info['server']) > 0)
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">Web Server</h4>
                        <div class="space-y-2">
                            @foreach($info['server'] as $tech => $version)
                            <div class="flex items-center space-x-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L7.586 10 5.293 7.707a1 1 0 010-1.414zM11 12a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $tech }}
                                    @if($version)
                                    <span class="text-xs text-gray-500 dark:text-gray-400">version {{ $version }}</span>
                                    @endif
                                </span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Frontend Technologies -->
                    @if(count($info['frontend']) > 0)
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">Frontend Technologies</h4>
                        <div class="space-y-2">
                            @foreach($info['frontend'] as $tech => $version)
                            <div class="flex items-center space-x-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $tech }}
                                    @if($version)
                                    <span class="text-xs text-gray-500 dark:text-gray-400">version {{ $version }}</span>
                                    @endif
                                </span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Security Features -->
                    @if(count($info['security']) > 0)
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">Security Features</h4>
                        <div class="space-y-2">
                            @foreach($info['security'] as $feature)
                            <div class="flex items-center space-x-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $feature }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Contact Information -->
                    @if(isset($info['contact']['email']))
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-3">Contact Information</h4>
                        <div class="flex items-center space-x-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                            </svg>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $info['contact']['email'] }}</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Security Overview -->
        @if(isset($results['nmap']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="p-8">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Security Overview</h3>
                @php
                    $nmapOutput = $results['nmap'][0]->raw_output;
                    preg_match_all('/(\d+)\/tcp\s+(\w+)\s+(\w+)\s+(.*)/', $nmapOutput, $matches);
                    $ports = [];
                    for ($i = 0; $i < count($matches[0]); $i++) {
                        $ports[] = [
                            'number' => $matches[1][$i],
                            'state' => $matches[2][$i],
                            'service' => $matches[3][$i],
                            'version' => $matches[4][$i]
                        ];
                    }
                @endphp

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Port</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">State</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Service</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Details</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($ports as $port)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $port['number'] }}/tcp
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        {{ $port['state'] === 'open' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}
                                        {{ $port['state'] === 'filtered' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : '' }}
                                        {{ $port['state'] === 'closed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : '' }}">
                                        {{ $port['state'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $port['service'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $port['version'] }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Discovered Resources -->
        @if(isset($results['gobuster']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="p-8">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Discovered Resources</h3>
                
                <!-- HTTP Status Legend -->
                <div class="mb-6 flex flex-wrap gap-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                        200: OK
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                        301/302: Redirect
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                        403: Forbidden
                    </span>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    @php
                        $gobusterOutput = $results['gobuster'][0]->raw_output;
                        preg_match_all('/\/([\\w-]+(?:\\.[\\w-]+)*)\\s+\\(Status: (\\d+)\\)/', $gobusterOutput, $matches);
                        $resources = [];
                        foreach ($matches[1] as $index => $path) {
                            if ($path !== 'favicon.ico' && $matches[2][$index] !== '404') {
                                $resources[] = [
                                    'path' => $path,
                                    'status' => $matches[2][$index]
                                ];
                            }
                        }
                        
                        // Ordenar los recursos por código de estado
                        usort($resources, function($a, $b) {
                            // Definir el orden de prioridad de los códigos
                            $priority = [
                                '200' => 1,  // Primero los 200
                                '301' => 2,  // Luego los 301
                                '302' => 2,  // Los 302 tienen la misma prioridad que 301
                                '403' => 3   // Luego los 403
                            ];
                            
                            // Obtener la prioridad de cada código, si no está definida usar 999
                            $priorityA = $priority[$a['status']] ?? 999;
                            $priorityB = $priority[$b['status']] ?? 999;
                            
                            // Ordenar primero por prioridad
                            if ($priorityA !== $priorityB) {
                                return $priorityA - $priorityB;
                            }
                            
                            // Si tienen la misma prioridad, ordenar por path
                            return strcmp($a['path'], $b['path']);
                        });
                    @endphp
                    
                    @foreach($resources as $resource)
                    <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 rounded-lg px-4 py-2">
                        <a href="{{ url($scan->domain . '/' . $resource['path']) }}" 
                           target="_blank"
                           data-resource-path="{{ $resource['path'] }}"
                           class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 truncate flex-1">
                            /{{ $resource['path'] }}
                        </a>
                        <span class="ml-4 px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $resource['status'] >= 200 && $resource['status'] < 300 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}
                            {{ $resource['status'] >= 300 && $resource['status'] < 400 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : '' }}
                            {{ $resource['status'] >= 400 ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : '' }}">
                            {{ $resource['status'] }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle scan cancellation with SweetAlert2
    const cancelButtons = document.querySelectorAll('[data-cancel-scan]');
    cancelButtons.forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            
            const result = await Swal.fire({
                title: 'Cancel Scan?',
                text: "Are you sure you want to cancel this scan? This action cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, cancel scan',
                cancelButtonText: 'No, continue scanning'
            });

            if (result.isConfirmed) {
                const scanId = this.dataset.scanId;
                try {
                    const response = await fetch(`/scans/${scanId}/cancel`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        Swal.fire(
                            'Cancelled!',
                            'The scan has been cancelled.',
                            'success'
                        );
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        throw new Error('Failed to cancel scan');
                    }
                } catch (error) {
                    Swal.fire(
                        'Error!',
                        'Failed to cancel the scan. Please try again.',
                        'error'
                    );
                }
            }
        });
    });
});
</script>
@endpush

@endsection 