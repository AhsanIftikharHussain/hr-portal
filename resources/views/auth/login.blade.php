<x-layouts.guest :title="'Sign in — '.config('app.name')">
    <h2 class="text-xl font-semibold">Sign in</h2>
    <p class="mt-1 text-sm text-slate-600">Use your internally provisioned account.</p>

    @if (session('status'))
        <x-alert type="success" class="mt-5">{{ session('status') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-6 flex flex-col gap-5">
        @csrf
        <x-form-input label="Email address" name="email" type="email" autocomplete="email" required autofocus />
        <x-form-input label="Password" name="password" type="password" autocomplete="current-password" required />

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" class="rounded border-slate-300 text-slate-800 focus:ring-slate-500">
            Remember me
        </label>

        <x-button class="w-full">Sign in</x-button>
    </form>

    @if (Route::has('password.request'))
        <a href="{{ route('password.request') }}" class="mt-5 block text-center text-sm font-medium text-slate-700 underline-offset-4 hover:underline">Forgot your password?</a>
    @endif
</x-layouts.guest>
