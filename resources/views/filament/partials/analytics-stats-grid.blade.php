@props(['stats' => []])

@if ($stats !== [])
    <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($stats as $stat)
            <div class="rounded-lg bg-gray-50 px-4 py-3 dark:bg-white/5">
                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</dt>
                <dd class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-gray-950 dark:text-white">{{ $stat['value'] }}</dd>
            </div>
        @endforeach
    </dl>
@endif
