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
                    error: null,
                    progress: 0,
                    scanId: null,
                    timer: null,
                    loadingDomain: false,
                    async getRandomDomain() {
                        this.loadingDomain = true;
                        try {
                            const response = await fetch('/random-domain', {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                }
                            });
                            if (!response.ok) throw new Error('Failed to fetch domain');
                            const data = await response.json();
                            document.getElementById('domain').value = data.domain;
                        } catch (e) {
                            console.error('Error fetching random domain:', e);
                            swalConfig.fire({
                                title: 'Error',
                                text: 'Failed to fetch random domain. Please try again.',
                                icon: 'error'
                            });
                        } finally {
                            this.loadingDomain = false;
                        }
                    },
                    async startScan() {
                        try {
                            this.scanning = true;
                            this.error = null;
                            this.progress = 0;
                            
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
                            this.startStatusCheck();
                        } catch (e) {
                            this.error = e.message;
                            this.scanning = false;
                        }
                    },
                    async checkStatus() {
                        if (!this.scanning || !this.scanId) return;
                        
                        try {
                            const response = await fetch(`/scans/${this.scanId}/status`, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });

                            // Si hay un error 504 o cualquier otro error, intentar de nuevo
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }

                            const data = await response.json();
                            this.progress = data.progress;

                            if (data.status === 'completed' || data.status === 'failed' || data.status === 'cancelled') {
                                this.scanning = false;
                                if (data.status === 'failed') {
                                    this.error = data.message || 'Scan failed';
                                }
                                clearInterval(this.timer);
                                if (data.status === 'completed') {
                                    window.open(`/scans/${this.scanId}`, '_blank');
                                }
                            }
                        } catch (e) {
                            console.error('Error checking status:', e);
                            
                            // Esperar antes de reintentar
                            await new Promise(resolve => setTimeout(resolve, 5000));
                        }
                    },
                    startStatusCheck() {
                        this.timer = setInterval(() => {
                            this.checkStatus();
                        }, 1000);
                    },
                    cancelScan() {
                        if (!this.scanId) return;
                        
                        fetch(`/scans/${this.scanId}/cancel`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                            }
                        }).then(response => {
                            if (!response.ok) throw new Error('Failed to cancel scan');
                            this.scanning = false;
                            clearInterval(this.timer);
                        }).catch(e => {
                            this.error = e.message;
                        });
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
                        <div class="mt-1 relative rounded-lg shadow-sm flex space-x-2">
                            <input type="text" name="domain" id="domain" required
                                class="h-12 block w-full pl-4 pr-12 text-base border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-indigo-500 dark:focus:border-indigo-400 dark:bg-gray-700 dark:text-white transition-colors duration-200"
                                placeholder="example.com or https://example.com"
                                :disabled="scanning"
                            >
                            <button type="button"
                                @click="getRandomDomain"
                                :disabled="scanning || loadingDomain"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                                <template x-if="!loadingDomain">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </template>
                                <template x-if="loadingDomain">
                                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </template>
                            </button>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Enter the domain you want to analyze (e.g. example.com) or click the refresh button for a random recent domain
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
                            <option value="medium">Medium (4-5 minutes)</option>
                            <option value="full">Full (15-20 minutes)</option>
                        </select>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Choose scan intensity. A full scan takes longer but finds more information.
                        </p>
                    </div>

                    <div x-show="scanning" x-cloak class="space-y-6">
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