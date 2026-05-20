@props(['stats' => []])

<x-filament::section
    heading="School overview"
    description="Headline counts and outcomes for the selected institution."
    icon="heroicon-o-chart-bar"
    icon-color="primary"
>
    @if ($stats === [])
        <p class="text-sm text-gray-500 dark:text-gray-400">No summary data available.</p>
    @else
        <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">Metric</th>
                        <th class="px-4 py-3 text-end font-medium">Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white dark:divide-white/10 dark:bg-gray-900">
                    @foreach ($stats as $stat)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $stat['label'] }}</td>
                            <td class="px-4 py-3 text-end tabular-nums text-gray-700 dark:text-gray-200">{{ $stat['value'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament::section>
