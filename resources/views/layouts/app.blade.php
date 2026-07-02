<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ADR Manager</title>
    @livewireStyles
</head>
<body class="bg-gray-50 text-gray-900">
    <main class="mx-auto max-w-3xl p-6">
        @if (session('adr-status'))
            <div class="mb-4 rounded bg-green-100 px-3 py-2 text-sm text-green-800">{{ session('adr-status') }}</div>
        @endif

        {{ $slot }}
    </main>
    @livewireScripts
</body>
</html>
