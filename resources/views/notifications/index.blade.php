<x-layouts.admin title="Notifications">
    <div class="flex flex-col gap-6">
        <x-page-header title="Notifications" description="Your notification history is retained here until it is marked as read.">
            @if ($unreadNotificationCount > 0)
                <x-slot:actions>
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <x-button>Mark all as read</x-button>
                    </form>
                </x-slot:actions>
            @endif
        </x-page-header>

        <x-card class="overflow-hidden p-0">
            @forelse ($notifications as $notification)
                <article @class([
                    'border-b border-slate-200 px-5 py-5 last:border-0',
                    'border-l-4 border-l-portal-900 bg-slate-50' => $notification->unread(),
                ])>
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-semibold text-slate-950">{{ $notification->data['title'] ?? 'Notification' }}</h2>
                                @if ($notification->unread())
                                    <span class="rounded-full bg-portal-900 px-2 py-0.5 text-xs font-semibold text-white">Unread</span>
                                @endif
                            </div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $notification->data['message'] ?? '' }}</p>
                            <p class="mt-2 text-xs text-slate-400">{{ $notification->created_at->format('d M Y, H:i') }} · {{ $notification->created_at->diffForHumans() }}</p>
                        </div>

                        <div class="flex shrink-0 items-center gap-3">
                            @if (! empty($notification->data['url']))
                                <a href="{{ $notification->data['url'] }}" class="text-sm font-semibold text-portal-900 hover:underline">View</a>
                            @endif
                            @if ($notification->unread())
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    <button type="submit" class="text-sm font-semibold text-slate-600 hover:text-slate-950">Mark as read</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <x-empty-state title="No notifications" description="New leave decisions and relevant HR reminders will appear here." class="m-5" />
            @endforelse

            @if ($notifications->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">{{ $notifications->links() }}</div>
            @endif
        </x-card>
    </div>
</x-layouts.admin>
