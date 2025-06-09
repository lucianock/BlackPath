<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ session('theme', 'dark') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ __('messages.app_name') }}</title>

    <!-- Favicon and App Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
    
    <!-- PWA Meta Tags -->
    <meta name="application-name" content="BlackPath">
    <meta name="apple-mobile-web-app-title" content="BlackPath">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="theme-color" content="#1f2937">
    
    <!-- Android Chrome Icons -->
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/android-chrome-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('images/android-chrome-512x512.png') }}">
    
    <!-- Web App Manifest -->
    <link rel="manifest" href="{{ asset('images/site.webmanifest') }}">

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('scripts')
    <style>
        @keyframes neonFlicker {
            0%, 18%, 22%, 25%, 53%, 57%, 100% {
                text-shadow:
                    0 0 7px #6366f1,
                    0 0 10px #6366f1,
                    0 0 14px #6366f1,
                    0 0 22px #6366f180,
                    0 0 30px #6366f150;
                opacity: 0.95;
            }
            20%, 21%, 23%, 24%, 55%, 56% {
                text-shadow: none;
                opacity: 0.3;
            }
        }
        @keyframes neonNoise {
            0%, 100% { opacity: 0.99; }
            50% { opacity: 0.94; }
        }
        .neon-text {
            color: #fff;
            animation: 
                neonFlicker 3s infinite alternate-reverse,
                neonNoise 0.11s infinite;
            position: relative;
            letter-spacing: 0.15em;
        }
        .neon-text::before {
            content: 'BlackPath';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            filter: blur(0.007em);
            opacity: 0.5;
        }
        .neon-text::after {
            content: 'BlackPath';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            filter: blur(0.01em);
            opacity: 0.3;
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">
    <div class="min-h-screen">
        <nav class="bg-white dark:bg-gray-800 shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <a href="{{ route('scans.index') }}" class="flex items-center">
                                <span class="text-2xl font-mono font-bold neon-text" style="text-shadow: 0 0 4px rgba(99, 102, 241, 0.2);">BlackPath</span>
                            </a>
                        </div>
                        <div class="hidden sm:ml-6 sm:flex sm:items-center sm:space-x-8">
                            <a href="{{ route('scans.index') }}" class="inline-flex items-center px-4 pt-1 border-b-2 {{ request()->routeIs('scans.index') ? 'border-indigo-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-300' }}">
                                {{ __('messages.scans') }}
                            </a>
                            <a href="{{ route('scans.create') }}" class="inline-flex items-center px-4 pt-1 border-b-2 {{ request()->routeIs('scans.create') ? 'border-indigo-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-300' }}">
                                {{ __('messages.new_scan') }}
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Theme Toggle -->
                        <div class="relative">
                            <form action="{{ route('preferences.theme') }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="theme" value="{{ session('theme', 'dark') === 'dark' ? 'light' : 'dark' }}">
                                <button type="submit" class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                                    @if(session('theme', 'dark') === 'dark')
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                    @else
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                        </svg>
                                    @endif
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <main class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                @if (session('success'))
                    <div class="mb-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html> 