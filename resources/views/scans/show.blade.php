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
        // Obtener el dominio base
        const domain = '{{ $scan->domain }}';
        
        // Obtener todos los recursos descubiertos
        const resources = document.querySelectorAll('[data-resource-path]');
        
        // Confirmar antes de abrir múltiples pestañas
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
                        <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full
                            {{ $scan->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : '' }}
                            {{ $scan->status === 'running' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : '' }}
                            {{ $scan->status === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' : '' }}
                            {{ $scan->status === 'queued' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300' : '' }}">
                            {{ ucfirst($scan->status) }}
                        </span>
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

        <!-- Security Summary -->
        @if(isset($results['nmap']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="p-8">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Security Overview</h3>
                @php
                    $nmapOutput = $results['nmap'][0]->raw_output;
                    $isSecure = !str_contains(strtolower($nmapOutput), 'vulnerable');
                @endphp
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center {{ $isSecure ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                        <i class="fas {{ $isSecure ? 'fa-shield-check' : 'fa-shield-exclamation' }} text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $isSecure ? 'No Critical Issues Found' : 'Some Attention Required' }}
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Basic security scan completed for {{ parse_url($scan->domain, PHP_URL_HOST) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Discovered Resources -->
        @if(isset($results['gobuster']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="p-8">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Discovered Resources</h3>
                
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
                    <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                        404: Not Found
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @php
                        $gobusterOutput = $results['gobuster'][0]->raw_output;
                        preg_match_all('/\/([\\w-]+(?:\\.[\\w-]+)*)\\s+\\(Status: (\\d+)\\)/', $gobusterOutput, $matches);
                        $resources = [];
                        foreach ($matches[1] as $index => $path) {
                            if ($path !== 'favicon.ico') {
                                $resources[] = [
                                    'path' => $path,
                                    'status' => $matches[2][$index]
                                ];
                            }
                        }
                    @endphp
                    
                    @foreach($resources as $resource)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
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