<x-layouts.admin title="Dashboard">
    <div class="flex flex-col gap-2">
        <p class="text-sm font-medium text-slate-500">Overview</p>
        <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Dashboard</h1>
        <p class="text-sm text-slate-600">The administration foundation is ready. HR modules will be introduced in later phases.</p>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach (['Total Employees', 'Present Today', 'On Leave', 'Pending Leave Requests'] as $metric)
            <x-card :title="$metric">
                <p class="text-2xl font-semibold text-slate-400">—</p>
                <p class="mt-1 text-xs text-slate-500">Available when this module is implemented</p>
            </x-card>
        @endforeach
    </div>

    <x-empty-state
        class="mt-6"
        title="No HR modules enabled yet"
        description="Phase 0 establishes authentication, authorization, layout, storage, and testing. Employee and HR workflows have intentionally not been started."
    />
</x-layouts.admin>
