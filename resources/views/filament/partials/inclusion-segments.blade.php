@php
    $gr = $gender_reporting ?? [];
@endphp

<x-filament::section
    :heading="$heading ?? 'Gender & inclusion'"
    :description="$description ?? null"
    icon="heroicon-o-chart-pie"
    icon-color="info"
>
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-3 rounded-xl border border-gray-200 p-4 dark:border-white/10">
            <p class="text-sm font-medium text-gray-950 dark:text-white">Reporting coverage</p>
            <p class="text-3xl font-semibold tabular-nums">{{ number_format($gr['reporting_rate_percent'] ?? 0, 1) }}%</p>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">With gender recorded</dt>
                    <dd class="font-medium tabular-nums">{{ $gr['learners_with_gender'] ?? 0 }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-gray-400">Not recorded</dt>
                    <dd class="font-medium tabular-nums">{{ $gr['learners_without_gender'] ?? 0 }}</dd>
                </div>
            </dl>
        </div>

        <div class="lg:col-span-2">
            <p class="mb-3 text-sm font-medium text-gray-950 dark:text-white">Learners by gender</p>
            @if (empty($cohort_rows))
                <p class="text-sm text-gray-500">No roster data.</p>
            @else
                <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                    <table class="w-full table-auto divide-y divide-gray-200 text-sm dark:divide-white/10">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-500">Category</th>
                                <th class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wide text-gray-500">Learners</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-white/10 dark:bg-gray-900">
                            @foreach ($cohort_rows as $row)
                                <tr>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-2">
                                            <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $row['color'] ?? '#94A3B8' }}"></span>
                                            {{ $row['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-end tabular-nums font-medium">{{ $row['count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if (! empty($performance_rows))
        <div class="mt-6">
            <p class="mb-3 text-sm font-medium text-gray-950 dark:text-white">Average outcome by gender segment</p>
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                <table class="w-full table-auto divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wide text-gray-500">Segment</th>
                            <th class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wide text-gray-500">Avg %</th>
                            <th class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wide text-gray-500">Attempts</th>
                            <th class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wide text-gray-500">Learners</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-white/10 dark:bg-gray-900">
                        @foreach ($performance_rows as $row)
                            <tr>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-2">
                                        <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $row['color'] ?? '#94A3B8' }}"></span>
                                        {{ $row['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-end tabular-nums font-medium">{{ number_format($row['average_percent'], 1) }}%</td>
                                <td class="px-4 py-3 text-end tabular-nums">{{ $row['attempts'] }}</td>
                                <td class="px-4 py-3 text-end tabular-nums">{{ $row['learners'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-filament::section>
