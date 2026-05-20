<x-filament-widgets::widget>
    <x-filament::section
        heading="Individual learner performance"
        description="Select a student to view score trend and performance by subject."
        icon="heroicon-o-academic-cap"
        icon-color="primary"
    >
        @if ($studentOptions === [])
            <p class="text-sm text-gray-500 dark:text-gray-400">No learners at this school yet.</p>
        @else
            <div class="max-w-md">
                <label for="school-student-select" class="fi-fo-field-wrp-label text-sm font-medium text-gray-950 dark:text-white">
                    Learner
                </label>
                <select
                    id="school-student-select"
                    wire:model.live="selectedStudentId"
                    class="fi-select-input mt-1 block w-full rounded-lg border-gray-300 bg-white text-gray-950 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                >
                    @foreach ($studentOptions as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @if ($selectedStudentId)
                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    @livewire(
                        \App\Filament\Widgets\Analytics\StudentScoreTrendChartWidget::class,
                        ['studentId' => $selectedStudentId],
                        key('school-student-trend-'.$selectedStudentId)
                    )
                    @livewire(
                        \App\Filament\Widgets\Analytics\StudentSubjectPerformanceChartWidget::class,
                        ['studentId' => $selectedStudentId],
                        key('school-student-subject-'.$selectedStudentId)
                    )
                </div>
            @endif
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
