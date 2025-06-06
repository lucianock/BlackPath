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
</script>
@endpush

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
        <div class="p-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">New Security Scan</h2>
            
            <form id="scanForm" 
                x-data="{ 
                    scanning: false,
                    progress: 0,
                    scanId: null,
                    error: null,
                    stage: null,
                    elapsedTime: 0,
                    estimatedTime: 0,
                    timeRemaining: 0,
                    statusInterval: null,
                    stages: {
                        'http_check': { name: 'Checking website accessibility', weight: 10 },
                        'nmap': { name: 'Analyzing security (Port scanning)', weight: 40 },
                        'gobuster': { name: 'Discovering resources', weight: 50 }
                    },
                    getCurrentStatus() {
                        if (!this.stage) return 'Initializing scan...';
                        return this.stages[this.stage]?.name || 'Processing...';
                    },
                    getStageProgress(stageName) {
                        if (this.stage === stageName) {
                            return this.progress;
                        }
                        if (Object.keys(this.stages).indexOf(stageName) < Object.keys(this.stages).indexOf(this.stage)) {
                            return 100;
                        }
                        return 0;
                    },
                    formatTime(seconds) {
                        if (!seconds || seconds < 0) return '0:00';
                        const mins = Math.floor(Math.abs(seconds) / 60);
                        const secs = Math.floor(Math.abs(seconds) % 60);
                        return `${mins}:${secs.toString().padStart(2, '0')}`;
                    },
                    async startScan() {
                        try {
                            this.scanning = true;
                            this.error = null;
                            this.progress = 0;
                            this.elapsedTime = 0;
                            
                            const response = await fetch('{{ route('scans.store') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                },
                                body: JSON.stringify({
                                    domain: document.getElementById('domain').value,
                                    wordlist: document.getElementById('wordlist').value
                                })
                            });
                            
                            if (!response.ok) {
                                const errorData = await response.json();
                                throw new Error(errorData.message || 'Error starting scan');
                            }

                            const data = await response.json();
                            this.scanId = data.scan_id;
                            this.estimatedTime = data.estimated_time;
                            this.startStatusCheck();
                        } catch (e) {
                            this.error = e.message;
                            this.scanning = false;
                        }
                    },
                    async cancelScan() {
                        if (!window.Swal) return;

                        const result = await swalConfig.fire({
                            title: 'Cancel Scan?',
                            text: 'Are you sure you want to cancel the scan? This action cannot be undone.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#dc2626',
                            cancelButtonColor: '#4f46e5',
                            confirmButtonText: 'Yes, cancel scan',
                            cancelButtonText: 'No, continue scanning'
                        });

                        if (result.isConfirmed) {
                            try {
                                const response = await fetch(`/scans/${this.scanId}/cancel`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                    }
                                });
                                
                                if (!response.ok) {
                                    throw new Error('Error cancelling scan');
                                }

                                this.error = 'Scan cancelled';
                                this.scanning = false;
                                clearInterval(this.statusInterval);
                            } catch (e) {
                                this.error = e.message;
                            }
                        }
                    },
                    startStatusCheck() {
                        let startTime = Date.now();
                        
                        this.statusInterval = setInterval(async () => {
                            try {
                                const response = await fetch(`/scans/${this.scanId}/status`);
                                const data = await response.json();
                                
                                this.progress = data.progress || 0;
                                this.stage = data.stage;
                                this.elapsedTime = Math.floor((Date.now() - startTime) / 1000);
                                this.timeRemaining = Math.max(0, this.estimatedTime - this.elapsedTime);
                                
                                if (data.status === 'completed' || data.status === 'failed' || data.status === 'cancelled') {
                                    clearInterval(this.statusInterval);
                                    if (data.status === 'completed' && window.Swal) {
                                        await swalConfig.fire({
                                            title: 'Scan Completed!',
                                            text: 'Your security scan has finished successfully.',
                                            icon: 'success'
                                        });
                                        window.location.href = `/scans/${this.scanId}`;
                                    } else {
                                        const messages = {
                                            'failed': 'Scan failed. Please try again.',
                                            'cancelled': 'Scan cancelled.'
                                        };
                                        this.error = messages[data.status] || data.error;
                                        this.scanning = false;
                                    }
                                }
                            } catch (e) {
                                clearInterval(this.statusInterval);
                                this.error = 'Error checking scan status';
                                this.scanning = false;
                            }
                        }, 1000);
                    }
                }"
                @submit.prevent="startScan"
            >
                @csrf
                
                <div class="space-y-8">
                    <div x-show="error" x-cloak 
                        class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg relative mb-4">
                        <span x-text="error"></span>
                    </div>

                    <div class="space-y-2">
                        <label for="domain" class="block text-base font-medium text-gray-700 dark:text-gray-300">
                            Domain to scan
                        </label>
                        <div class="mt-1 relative rounded-lg shadow-sm">
                            <input type="text" name="domain" id="domain" required
                                class="h-12 block w-full pl-4 pr-12 text-base border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-indigo-500 dark:focus:border-indigo-400 dark:bg-gray-700 dark:text-white transition-colors duration-200"
                                placeholder="example.com or https://example.com"
                                :disabled="scanning"
                            >
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Enter the domain you want to analyze (e.g. example.com)
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label for="wordlist" class="block text-base font-medium text-gray-700 dark:text-gray-300">
                            Scan intensity
                        </label>
                        <select name="wordlist" id="wordlist"
                            class="h-12 block w-full pl-4 pr-10 text-base border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-indigo-500 dark:focus:border-indigo-400 dark:bg-gray-700 dark:text-white transition-colors duration-200"
                            :disabled="scanning">
                            <option value="common">Quick (2-3 minutes)</option>
                            <option value="medium" selected>Full (4-5 minutes)</option>
                        </select>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Choose scan intensity. A full scan takes longer but finds more information.
                        </p>
                    </div>

                    <div x-show="scanning" x-cloak class="space-y-6">
                        <template x-for="(stageInfo, stageName) in stages" :key="stageName">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium text-gray-200 dark:text-gray-300" x-text="stageInfo.name"></span>
                                    <span class="font-bold text-gray-200 dark:text-gray-300" x-text="getStageProgress(stageName) + '%'"></span>
                                </div>
                                <div class="w-full bg-gray-700 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-indigo-600 dark:bg-indigo-500 h-2 rounded-full transition-all duration-500"
                                        :class="{'opacity-50': stage !== stageName && getStageProgress(stageName) === 0}"
                                        :style="{ width: getStageProgress(stageName) + '%' }">
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div class="flex items-center justify-between text-sm text-gray-200 dark:text-gray-300">
                            <span x-text="`Time elapsed: ${formatTime(elapsedTime)}`"></span>
                            <span x-text="`Time remaining: ~${formatTime(timeRemaining)}`"></span>
                        </div>

                        <div class="text-center text-sm text-gray-200 dark:text-gray-300 mt-2">
                            <p>Please do not close this window until the scan is complete.</p>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-4 pt-4">
                        <button type="button"
                            x-show="scanning"
                            @click="cancelScan"
                            class="inline-flex items-center px-4 py-2 border border-red-300 dark:border-red-600 text-sm font-medium rounded-lg text-red-700 dark:text-red-400 bg-white dark:bg-gray-800 hover:bg-red-50 dark:hover:bg-red-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200">
                            Cancel scan
                        </button>

                        <button type="submit"
                            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
                            :disabled="scanning">
                            <template x-if="!scanning">
                                <span>Start scan</span>
                            </template>
                            <template x-if="scanning">
                                <div class="flex items-center">
                                    Scanning
                                    <svg class="animate-spin ml-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </template>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>

@push('scripts')
<script>
    // Ensure SweetAlert2 is loaded
    document.addEventListener('DOMContentLoaded', function() {
        if (!window.Swal) {
            console.error('SweetAlert2 not loaded properly');
        }
    });
</script>
@endpush

@endsection 