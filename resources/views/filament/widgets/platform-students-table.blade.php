@if (empty($bare))
<x-filament-widgets::widget>
@endif
    <x-filament::section
        :heading="$heading"
        :description="$description"
        icon="{{ ($variant ?? 'top') === 'support' ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-star' }}"
        :icon-color="($variant ?? 'top') === 'support' ? 'danger' : 'success'"
    >
        <div class="overflow-x-auto rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-white/5 dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-2 font-medium">Student</th>
                        <th class="px-3 py-2 font-medium">School</th>
                        <th class="px-3 py-2 font-medium">Gender</th>
                        @if (($variant ?? 'top') === 'support')
                            <th class="px-3 py-2 font-medium">Grade</th>
                        @endif
                        <th class="px-3 py-2 font-medium text-end">Avg %</th>
                        <th class="px-3 py-2 font-medium">Level</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                    @forelse ($students as $student)
                        <tr class="text-gray-700 dark:text-gray-200">
                            <td class="px-3 py-2 font-medium text-gray-950 dark:text-white">{{ $student['name'] }}</td>
                            <td class="px-3 py-2">{{ $student['institution_name'] ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $student['gender'] ?? '—' }}</td>
                            @if (($variant ?? 'top') === 'support')
                                <td class="px-3 py-2">{{ $student['grade_level'] ?? '—' }}</td>
                            @endif
                            <td class="px-3 py-2 text-end tabular-nums font-medium {{ ($variant ?? 'top') === 'support' ? 'text-danger-600 dark:text-danger-400' : '' }}">
                                {{ number_format($student['average_percent'], 1) }}%
                            </td>
                            <td class="px-3 py-2 text-xs">{{ $student['competency_level'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($variant ?? 'top') === 'support' ? 6 : 5 }}" class="px-3 py-6 text-center text-gray-500">
                                No data yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
@if (empty($bare))
</x-filament-widgets::widget>
@endif
