@extends('layouts.app')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                    <div class="flex space-x-4 items-center">
                        <a href="{{ route('scans.export', $scan->id) }}" 
                           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200">
                            Export PDF
                        </a>
                        <span class="inline-flex items-center px-3 py-1 text-sm font-semibold rounded-full
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
                @php
                    $gobusterOutput = is_array($results['gobuster'][0]->raw_output) ? implode("\n", $results['gobuster'][0]->raw_output) : $results['gobuster'][0]->raw_output;
                    preg_match_all('/\/([\w-]+(?:\.[\w-]+)*)\s+\(Status: (\d+)\)/', $gobusterOutput, $matches);
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
                
                @if(count($resources) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($resources as $resource)
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <a href="{{ $scan->domain }}/{{ $resource['path'] }}" 
                           target="_blank"
                           class="text-blue-600 dark:text-blue-400 hover:underline break-all">
                            /{{ $resource['path'] }}
                        </a>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-gray-600 dark:text-gray-400">No additional resources were discovered during the scan.</p>
                @endif
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