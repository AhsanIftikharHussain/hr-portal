@props(['title', 'description' => null])

<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-950">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 text-sm text-slate-600">{{ $description }}</p>
        @endif
    </div>
    @if (trim($actions ?? ''))
        <div class="flex flex-wrap gap-2">{{ $actions }}</div>
    @endif
</div>
