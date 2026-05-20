<x-filament-widgets::widget>
    <x-filament::section
        icon="heroicon-o-building-office-2"
        icon-color="info"
        heading="All schools & learner groups"
        description="Every institution plus individual learners — students, attempts, average CBE outcome, and gender data coverage."
    >
        <div class="overflow-x-auto rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-white/5 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-2 font-medium">School / group</th>
                        <th class="px-4 py-2 font-medium text-end">Students</th>
                        <th class="px-4 py-2 font-medium text-end">Attempts</th>
                        <th class="px-4 py-2 font-medium text-end">Avg %</th>
                        <th class="px-4 py-2 font-medium">CBE level</th>
                        <th class="px-4 py-2 font-medium text-end">Gender data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($institutions as $row)
                        <tr class="text-gray-700 dark:text-gray-200">
                            <td class="px-4 py-2.5 font-medium text-gray-950 dark:text-white">{{ $row['name'] }}</td>
                            <td class="px-4 py-2.5 text-end tabular-nums">{{ $row['student_count'] }}</td>
                            <td class="px-4 py-2.5 text-end tabular-nums">{{ $row['completed_attempts'] }}</td>
                            <td class="px-4 py-2.5 text-end tabular-nums font-medium">{{ number_format($row['average_percent'], 1) }}%</td>
                            <td class="px-4 py-2.5">{{ $row['competency_level'] }}</td>
                            <td class="px-4 py-2.5 text-end tabular-nums">{{ number_format($row['gender_reporting_percent'], 1) }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">No institutions registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
