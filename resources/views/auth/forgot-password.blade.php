<x-layouts.guest :title="'Reset password — '.config('app.name')">
    <h2 class="text-xl font-semibold">Reset your password</h2>
    <p class="mt-1 text-sm leading-6 text-slate-600">Enter your account email and we will send a password reset link.</p>

    @if (session('status'))
        <x-alert type="success" class="mt-5">{{ session('status') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 flex flex-col gap-5">
        @csrf
        <x-form-input label="Email address" name="email" type="email" autocomplete="email" required autofocus />
        <x-button class="w-full">Email reset link</x-button>
    </form>

    <a href="{{ route('login') }}" class="mt-5 block text-center text-sm font-medium text-slate-700 underline-offset-4 hover:underline">Back to sign in</a>
</x-layouts.guest>
