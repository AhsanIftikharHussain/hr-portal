<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ isset($title) ? $title.' — '.config('app.name') : config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased" x-data="{ mobileNavigationOpen: false, userMenuOpen: false }">
        <div class="min-h-screen lg:flex">
            <div x-cloak x-show="mobileNavigationOpen" class="fixed inset-0 z-40 bg-slate-950/40 lg:hidden" @click="mobileNavigationOpen = false"></div>

            <aside
                class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200 bg-white transition-transform lg:static lg:translate-x-0"
                :class="mobileNavigationOpen ? 'translate-x-0' : '-translate-x-full'"
            >
                <div class="flex h-16 items-center justify-between border-b border-slate-200 px-5">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 font-semibold">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-portal-900 text-sm text-white">AH</span>
                        <span>HR Portal</span>
                    </a>
                    <button type="button" class="rounded-md p-2 text-slate-500 hover:bg-slate-100 lg:hidden" @click="mobileNavigationOpen = false" aria-label="Close navigation">✕</button>
                </div>

                <nav class="flex flex-1 flex-col gap-1 overflow-y-auto p-4" aria-label="Primary navigation">
                    <x-nav-item :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">Dashboard</x-nav-item>
                    <x-nav-item :href="route('admin.employees.index')" :active="request()->routeIs('admin.employees.*')">Employees</x-nav-item>
                    <x-nav-item :href="route('admin.attendance.index')" :active="request()->routeIs('admin.attendance.*')">Attendance</x-nav-item>
                    <x-nav-item :href="route('admin.leave-requests.index')" :active="request()->routeIs('admin.leave-requests.*')">Leave Management</x-nav-item>
                    <x-nav-item :href="route('admin.contracts.index')" :active="request()->routeIs('admin.contracts.*', 'admin.employee-documents.*')">Contracts &amp; Documents</x-nav-item>
                    <x-nav-item :href="route('admin.policies.index')" :active="request()->routeIs('admin.policies.*', 'admin.policy-categories.*')">Office Policies</x-nav-item>
                    <x-nav-item :href="route('admin.holidays.calendar')" :active="request()->routeIs('admin.holidays.*')">Holiday Calendar</x-nav-item>
                    <div class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Settings</div>
                    <x-nav-item :href="route('admin.departments.index')" :active="request()->routeIs('admin.departments.*')">Departments</x-nav-item>
                    <x-nav-item :href="route('admin.job-titles.index')" :active="request()->routeIs('admin.job-titles.*')">Designations</x-nav-item>
                    <x-nav-item :href="route('admin.leave-types.index')" :active="request()->routeIs('admin.leave-types.*')">Leave Types</x-nav-item>
                </nav>

                <div class="border-t border-slate-200 p-4 text-xs text-slate-500">HR Administration</div>
            </aside>

            <div class="min-w-0 flex-1">
                <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
                    <button type="button" class="rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden" @click="mobileNavigationOpen = true" aria-label="Open navigation">☰</button>
                    <div class="hidden text-sm text-slate-500 sm:block">HR Administration</div>

                    <div class="relative">
                        <button type="button" class="flex items-center gap-3 rounded-lg px-3 py-2 text-left hover:bg-slate-100" @click="userMenuOpen = !userMenuOpen" @click.outside="userMenuOpen = false" :aria-expanded="userMenuOpen">
                            <span class="flex size-8 items-center justify-center rounded-full bg-slate-200 text-sm font-semibold text-slate-700">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                            <span class="hidden sm:block">
                                <span class="block text-sm font-medium">{{ auth()->user()->name }}</span>
                                <span class="block text-xs text-slate-500">{{ auth()->user()->email }}</span>
                            </span>
                        </button>

                        <div x-cloak x-show="userMenuOpen" x-transition class="absolute right-0 mt-2 w-48 rounded-lg border border-slate-200 bg-white p-1 shadow-lg">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full rounded-md px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">Sign out</button>
                            </form>
                        </div>
                    </div>
                </header>

                <main class="p-4 sm:p-6 lg:p-8">
                    <div class="mx-auto max-w-7xl">
                        @if (session('status'))
                            <x-alert type="success" class="mb-6">{{ session('status') }}</x-alert>
                        @endif
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
