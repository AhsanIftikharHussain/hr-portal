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
                    <a href="{{ auth()->user()->can('access-admin') ? route('admin.dashboard') : (auth()->user()->can('access-manager') ? route('manager.dashboard') : (auth()->user()->can('access-employee') ? route('employee.dashboard') : route('profile.edit'))) }}" class="flex items-center gap-3 font-semibold">
                        <span class="flex size-9 items-center justify-center rounded-lg bg-portal-900 text-sm text-white">CP</span>
                        <span>{{ config('app.name') }}</span>
                    </a>
                    <button type="button" class="rounded-md p-2 text-slate-500 hover:bg-slate-100 lg:hidden" @click="mobileNavigationOpen = false" aria-label="Close navigation">✕</button>
                </div>

                <nav class="flex flex-1 flex-col gap-1 overflow-y-auto p-4" aria-label="Primary navigation">
                    @can('access-admin')
                        <x-nav-item :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">Dashboard</x-nav-item>
                        <x-nav-item :href="route('admin.employees.index')" :active="request()->routeIs('admin.employees.*')">Employees</x-nav-item>
                        <x-nav-item :href="route('admin.attendance.index')" :active="request()->routeIs('admin.attendance.*')">Attendance</x-nav-item>
                        <x-nav-item :href="route('admin.leave-requests.index')" :active="request()->routeIs('admin.leave-requests.*')">Leave Management</x-nav-item>
                        <x-nav-item :href="route('admin.contracts.index')" :active="request()->routeIs('admin.contracts.*', 'admin.employee-documents.*')">Contracts &amp; Documents</x-nav-item>
                        <x-nav-item :href="route('admin.policies.index')" :active="request()->routeIs('admin.policies.*', 'admin.policy-categories.*')">Office Policies</x-nav-item>
                        <x-nav-item :href="route('admin.holidays.calendar')" :active="request()->routeIs('admin.holidays.*')">Holiday Calendar</x-nav-item>
                        <div class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Settings</div>
                        @can('viewAny', App\Models\User::class)
                            <x-nav-item :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">User Management</x-nav-item>
                        @endcan
                        <x-nav-item :href="route('admin.departments.index')" :active="request()->routeIs('admin.departments.*')">Departments</x-nav-item>
                        <x-nav-item :href="route('admin.job-titles.index')" :active="request()->routeIs('admin.job-titles.*')">Designations</x-nav-item>
                        <x-nav-item :href="route('admin.leave-types.index')" :active="request()->routeIs('admin.leave-types.*')">Leave Types</x-nav-item>
                    @elsecan('access-manager')
                        <x-nav-item :href="route('manager.dashboard')" :active="request()->routeIs('manager.dashboard')">Dashboard</x-nav-item>
                        <x-nav-item :href="route('manager.attendance.index')" :active="request()->routeIs('manager.attendance.*')">Department Attendance</x-nav-item>
                        <x-nav-item :href="route('manager.leave-requests.index')" :active="request()->routeIs('manager.leave-requests.*')">Department Leave</x-nav-item>
                    @elsecan('access-employee')
                        <x-nav-item :href="route('employee.dashboard')" :active="request()->routeIs('employee.dashboard')">Dashboard</x-nav-item>
                        <x-nav-item :href="route('employee.profile.show')" :active="request()->routeIs('employee.profile.*')">My Profile</x-nav-item>
                        <x-nav-item :href="route('employee.leave-requests.index')" :active="request()->routeIs('employee.leave-requests.*')">My Leave</x-nav-item>
                        <x-nav-item :href="route('employee.attendance.index')" :active="request()->routeIs('employee.attendance.*')">My Attendance</x-nav-item>
                        <x-nav-item :href="route('employee.policies.index')" :active="request()->routeIs('employee.policies.*')">Policies</x-nav-item>
                        <x-nav-item :href="route('employee.holidays.index')" :active="request()->routeIs('employee.holidays.*')">Holidays</x-nav-item>
                    @endcan
                    <div class="mt-4 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Account</div>
                    <x-nav-item :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">Notifications <span class="ml-1 text-xs">{{ $unreadNotificationCount > 0 ? '('.$unreadNotificationCount.')' : '' }}</span></x-nav-item>
                    <x-nav-item :href="route('profile.edit')" :active="request()->routeIs('profile.*')">My Account &amp; Password</x-nav-item>
                </nav>

                <div class="border-t border-slate-200 p-4 text-xs text-slate-500">{{ auth()->user()->can('access-admin') ? 'HR Administration' : (auth()->user()->can('access-manager') ? 'Department Management' : (auth()->user()->can('access-employee') ? 'Employee Self-Service' : 'My Account')) }}</div>
            </aside>

            <div class="min-w-0 flex-1">
                <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
                    <button type="button" class="rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden" @click="mobileNavigationOpen = true" aria-label="Open navigation">☰</button>
                    <div class="hidden text-sm text-slate-500 sm:block">{{ auth()->user()->can('access-admin') ? 'HR Administration' : (auth()->user()->can('access-manager') ? 'Department Management' : (auth()->user()->can('access-employee') ? 'Employee Self-Service' : 'My Account')) }}</div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('notifications.index') }}" class="relative flex size-10 items-center justify-center rounded-lg text-lg text-slate-600 hover:bg-slate-100" aria-label="Notifications{{ $unreadNotificationCount > 0 ? ', '.$unreadNotificationCount.' unread' : '' }}">
                            <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.9 18a3 3 0 0 1-5.8 0m9.4-2H5.5c1.2-1.3 1.8-3.1 1.8-5V9a4.7 4.7 0 0 1 9.4 0v2c0 1.9.6 3.7 1.8 5Z" />
                            </svg>
                            @if ($unreadNotificationCount > 0)
                                <span class="absolute right-0 top-0 flex min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold leading-5 text-white">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
                            @endif
                        </a>

                        <div class="relative">
                            <button type="button" class="flex items-center gap-3 rounded-lg px-3 py-2 text-left hover:bg-slate-100" @click="userMenuOpen = !userMenuOpen" @click.outside="userMenuOpen = false" :aria-expanded="userMenuOpen">
                                <span class="flex size-8 items-center justify-center rounded-full bg-slate-200 text-sm font-semibold text-slate-700">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                                <span class="hidden sm:block">
                                    <span class="block text-sm font-medium">{{ auth()->user()->name }}</span>
                                    <span class="block text-xs text-slate-500">{{ auth()->user()->email }}</span>
                                </span>
                            </button>

                            <div x-cloak x-show="userMenuOpen" x-transition class="absolute right-0 mt-2 w-48 rounded-lg border border-slate-200 bg-white p-1 shadow-lg">
                                <a href="{{ route('profile.edit') }}" class="block rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">My Profile</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full rounded-md px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100">Sign out</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="p-4 sm:p-6 lg:p-8">
                    <div class="mx-auto max-w-7xl">
                        @if (session('success'))
                            <x-alert type="success" class="mb-6">{{ session('success') }}</x-alert>
                        @elseif (session('status'))
                            <x-alert type="success" class="mb-6">{{ session('status') }}</x-alert>
                        @endif
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
