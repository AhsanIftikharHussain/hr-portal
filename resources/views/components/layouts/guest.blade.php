<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-portal-50 font-sans text-slate-900 antialiased">
        <main class="flex min-h-screen items-center justify-center px-4 py-12 sm:px-6">
            <div class="w-full max-w-md">
                <div class="mb-8 text-center">
                    <div class="mx-auto flex size-12 items-center justify-center rounded-xl bg-portal-900 text-lg font-semibold text-white">CP</div>
                    <h1 class="mt-4 text-2xl font-semibold tracking-tight">{{ config('app.name') }}</h1>
                    <p class="mt-2 text-sm text-slate-600">Private internal access</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </body>
</html>
