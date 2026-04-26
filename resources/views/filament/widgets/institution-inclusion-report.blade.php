@php
    $gr = $gender_reporting ?? [];
@endphp

<x-filament-widgets::widget>
    <x-filament::section
        icon="heroicon-o-chart-pie"
        icon-color="info"
        heading="Inclusion & equity snapshot"
        description="Roster counts by recorded gender, reporting coverage, and average outcomes where there are completed attempts (CBC / CBE reporting view)."
    >
        <div class="grid gap-6 lg:grid-cols-3">
            <div
                class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
            >
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Gender reporting coverage
                </p>
                <p class="mt-2 text-3xl font-semibold tabular-nums text-gray-950 dark:text-white">
                    {{ number_format($gr['reporting_rate_percent'] ?? 0, 1) }}%
                </p>
                <dl class="mt-4 space-y-2 text-sm text-gray-600 dark:text-gray-300">
                    <div class="flex justify-between gap-2">
                        <dt>With gender recorded</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $gr['learners_with_gender'] ?? 0 }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt>Not recorded</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $gr['learners_without_gender'] ?? 0 }}</dd>
                    </div>
                </dl>
            </div>

            <div
                class="rounded-xl bg-white p-0 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 lg:col-span-2"
            >
                <div class="border-b border-gray-100 px-4 py-3 dark:border-white/10">
                    <p class="text-sm font-semibold text-gray-950 dark:text-white">Learners on roster by recorded gender</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Counts reflect your current learner accounts.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-white/5 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-2 font-medium">Category</th>
                                <th class="px-4 py-2 font-medium text-end">Learners</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @forelse ($cohort_rows ?? [] as $row)
                                <tr class="text-gray-700 dark:text-gray-200">
                                    <td class="px-4 py-2.5">{{ $row['label'] }}</td>
                                    <td class="px-4 py-2.5 text-end tabular-nums font-medium text-gray-950 dark:text-white">
                                        {{ $row['count'] }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-6 text-center text-gray-500">No roster data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if (! empty($performance_rows))
            <div
                class="mt-6 rounded-xl bg-white p-0 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
            >
                <div class="border-b border-gray-100 px-4 py-3 dark:border-white/10">
                    <p class="text-sm font-semibold text-gray-950 dark:text-white">Average outcome by segment</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Based on completed attempts only; percentages are score as a share of total marks.
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-white/5 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-2 font-medium">Segment</th>
                                <th class="px-4 py-2 font-medium text-end">Avg %</th>
                                <th class="px-4 py-2 font-medium text-end">Attempts</th>
                                <th class="px-4 py-2 font-medium text-end">Learners</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @foreach ($performance_rows as $row)
                                <tr class="text-gray-700 dark:text-gray-200">
                                    <td class="px-4 py-2.5">{{ $row['label'] }}</td>
                                    <td class="px-4 py-2.5 text-end tabular-nums font-medium text-gray-950 dark:text-white">
                                        {{ number_format($row['average_percent'], 1) }}%
                                    </td>
                                    <td class="px-4 py-2.5 text-end tabular-nums">{{ $row['attempts'] }}</td>
                                    <td class="px-4 py-2.5 text-end tabular-nums">{{ $row['learners'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if (! empty($notes))
            <ul class="mt-6 list-disc space-y-1 pl-5 text-xs text-gray-500 dark:text-gray-400">
                @foreach ($notes as $note)
                    <li>{{ $note }}</li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
