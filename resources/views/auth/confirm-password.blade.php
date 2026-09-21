<x-layouts.guest :title="'Confirm password — '.config('app.name')">
    <h2 class="text-xl font-semibold">Confirm your password</h2>
    <p class="mt-1 text-sm leading-6 text-slate-600">For your security, confirm your password before continuing.</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 flex flex-col gap-5">
        @csrf
        <x-form-input label="Password" name="password" type="password" autocomplete="current-password" required autofocus />
        <x-button class="w-full">Confirm password</x-button>
    </form>
</x-layouts.guest>
