@extends('layouts.app')

@push('scripts')
    <script>
        function scanPage(userIp) {
            return {
                scanning: false,
                error: null,
                progress: 0,
                scanId: null,
                timer: null,
                loadingDomain: false,
                statusMessage: '',
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
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to fetch random domain. Please try again.',
                            icon: 'error'
                        });
                    } finally {
                        this.loadingDomain = false;
                    }
                },
                async startScan() {
                    const accepted = await showDisclaimer(userIp);
                    if (!accepted) return;
                    try {
                        this.scanning = true;
                        this.error = null;
                        this.progress = 0;
                        this.statusMessage = '';
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
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        const data = await response.json();
                        this.progress = data.progress;
                        this.statusMessage = data.message || '';
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
            };
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const swalConfig = Swal.mixin({
            customClass: {
                popup: 'swal2-bp-popup',
                title: 'swal2-bp-title',
                htmlContainer: 'swal2-bp-html',
                confirmButton: 'swal2-bp-confirm',
                cancelButton: 'swal2-bp-cancel'
            }
        });
        async function showDisclaimer(userIp) {
            const { isConfirmed } = await swalConfig.fire({
                title: '⚠️ Legal Disclaimer',
                html: `
                    <div class="text-white">
                        <p class="mb-3">
                            This tool is intended for <strong>authorized security testing</strong> only. You must have <span class="text-yellow-300 font-semibold">explicit permission</span> to scan the target domain.
                        </p>
                        <p class="mb-3">
                            Unauthorized scanning may be illegal and could result in <strong>civil and criminal penalties</strong>.
                        </p>
                        <p class="mb-3">
                            By continuing, you confirm that you are authorized and accept all responsibility for your actions.
                        </p>
                        <p class="text-sm mt-4 font-semibold text-red-400">
                            Your IP: <span class="bg-red-200 text-red-800 px-2 py-1 rounded">${userIp}</span> will be logged.
                        </p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'I Accept and Continue',
                cancelButtonText: 'Cancel',
                allowOutsideClick: false,
                allowEscapeKey: false,
                focusCancel: true
            });
            return isConfirmed;
        }
    </script>
@endpush

@section('content')
    <div x-data="scanPage('{{ $userIp }}')">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                <div class="p-8">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">New Security Scan</h2>

                    <form id="scanForm" @submit.prevent="startScan">
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
                                        placeholder="example.com or https://example.com" :disabled="scanning">
                                    <button type="button" @click="getRandomDomain" :disabled="scanning || loadingDomain"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                                        <template x-if="!loadingDomain">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </template>
                                        <template x-if="loadingDomain">
                                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg"
                                                fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                                    stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                        </template>
                                    </button>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Enter the domain you want to analyze (e.g. example.com) or click the refresh button for
                                    a random recent domain
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

                            <div class="flex justify-end space-x-4 pt-4">
                                <button type="submit"
                                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
                                    :disabled="scanning">
                                    Start scan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Overlay global para loading, cubre todo -->
        <div x-show="scanning" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-80"
            style="pointer-events: all;">
            <div
                class="max-w-md w-full bg-white dark:bg-gray-900 rounded-lg shadow-lg p-8 flex flex-col items-center text-center">
                <svg class="animate-spin h-10 w-10 text-indigo-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                    </circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2"
                    x-text="statusMessage || 'We\'re waiting for this website to finish loading'"></h3>
                <p class="text-gray-700 dark:text-gray-300 mb-2">Depending on our load, this might take a minute.</p>
                <p class="text-gray-700 dark:text-gray-300 mb-4">You will automatically be redirected to the result, you do
                    not have to refresh this page!</p>
                <p class="text-xs text-red-500 dark:text-red-400 font-semibold">Do not close this tab or you will lose the
                    process.</p>
            </div>
        </div>
        <div
            x-effect="scanning ? document.querySelector('nav').classList.add('bg-black','bg-opacity-90','!shadow-none') : document.querySelector('nav').classList.remove('bg-black','bg-opacity-90','!shadow-none')">
        </div>
    </div>

    <style>
        [x-cloak] {
            display: none !important;
        }

        .swal2-bp-popup {
            background: #1e293b !important;
            color: #fff !important;
            border-radius: 1rem !important;
            box-shadow: 0 10px 40px #000a !important;
        }
        .swal2-bp-title {
            color: #fff !important;
            font-weight: 600 !important;
        }
        .swal2-bp-html {
            color: #fff !important;
        }
        .swal2-bp-confirm {
            background: #6366f1 !important;
            color: #fff !important;
            border: none !important;
            box-shadow: none !important;
        }
        .swal2-bp-cancel {
            background: #334155 !important;
            color: #fff !important;
            border: none !important;
            box-shadow: none !important;
        }
    </style>
@endsection
