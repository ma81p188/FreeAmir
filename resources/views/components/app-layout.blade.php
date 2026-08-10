@props([
    'title' => config('app.name'),
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->isLocale('fa') ? 'rtl' : 'ltr' }}" data-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf_token" content="{{ csrf_token() }}" />
    <script>
        try {
            document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') === 'dark' ? 'dark' : 'light');
        } catch (error) {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    </script>
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body x-data class="min-h-screen bg-base-200 text-base-content">

    <x-header />

    {{-- Mobile drawer backdrop --}}
    <div
        x-show="$store.sidebar.open"
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-on:click="$store.sidebar.close()"
        class="fixed inset-0 z-40 bg-black/50 lg:hidden"
        aria-hidden="true"
    ></div>

    {{-- Mobile drawer panel (slides from right) --}}
    <aside
        id="mobile-drawer"
        x-show="$store.sidebar.open"
        x-transition:enter="transition-transform ease-in-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition-transform ease-in-out duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        x-on:keydown.escape.window="$store.sidebar.close()"
        class="fixed inset-y-0 right-0 z-50 flex w-72 max-w-[85vw] flex-col overflow-y-auto bg-base-100 shadow-xl lg:hidden"
        role="dialog"
        aria-modal="true"
        aria-label="{{ __('Main navigation') }}"
    >
        {{-- Drawer header --}}
        <div class="flex items-center justify-between border-b border-base-content/10 px-4 py-3">
            <a href="/" class="flex items-center gap-2" x-on:click="$store.sidebar.close()">
                <img src="/images/logo.png" alt="Logo" class="h-8 w-8 object-contain">
                <span class="text-sm font-bold text-base-content">{{ config('app.name') }}</span>
            </a>
            <button
                x-on:click="$store.sidebar.close()"
                class="btn btn-ghost btn-square btn-sm"
                aria-label="{{ __('بستن منو') }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Drawer navigation --}}
        <nav class="flex-1 overflow-y-auto px-2 py-2" aria-label="{{ __('Main navigation') }}">
            <ul class="app-main-menu menu w-full" data-main-menu>
                <x-menu />
            </ul>
        </nav>

        {{-- Drawer footer: user info --}}
        <div class="border-t border-base-content/10 px-4 py-3">
            <div class="flex items-center gap-2">
                <div class="avatar placeholder">
                    <div class="bg-base-content/10 text-base-content rounded-full w-8">
                        <span class="text-xs">{{ substr(Auth::user()->name, 0, 1) }}</span>
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium">{{ Auth::user()->name }}</p>
                    <p class="truncate text-xs text-base-content/60">
                        {{ cookie('active-company-id') ? config('active-company-name') : __('No company') }}
                    </p>
                </div>
            </div>
        </div>
    </aside>

    <main class="min-[1430px]:w-[1430px] mx-auto mt-5">
        {{ $slot }}
    </main>

    <footer class="mt-8 text-center text-xs opacity-60 pb-4">
        {{ __('Integrated Accounting and Human Resources System') }} {{ __('Version :version', ['version' => config('app.version')]) }}
    </footer>

    @stack('scripts')

    @stack('footer')
</body>

</html>
