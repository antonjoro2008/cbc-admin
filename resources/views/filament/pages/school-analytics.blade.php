<x-filament-panels::page>
    <div class="mb-6">
        <label for="institution-select" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
            Select school / institution
        </label>
        <select
            id="institution-select"
            wire:model.live="institutionId"
            class="block w-full max-w-md rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900 dark:text-white"
        >
            @foreach ($institutions as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            View the full institution dashboard for any school — same analytics an institution admin sees.
        </p>
    </div>

    @php
        $a = $analytics ?? [];
        $summary = $a['summary'] ?? [];
        $insights = $a['insights'] ?? [];
        $institution = $a['institution'] ?? null;
    @endphp

    @if (empty($a) || ! $institutionId)
        <x-filament::section>
            <p class="text-sm text-gray-500">No institutions registered yet.</p>
        </x-filament::section>
    @else
        <x-filament::section
            icon="heroicon-o-building-office-2"
            :heading="$institution['name'] ?? 'Institution'"
            description="Institution-scoped analytics — learners, classrooms, gender equity, competency areas, and outcomes."
            class="mb-6"
        />

        {{-- Summary stats --}}
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Learners', $summary['learners'] ?? 0],
                ['Teachers', $summary['teachers'] ?? 0],
                ['Classrooms', $summary['classrooms'] ?? 0],
                ['Avg score', ($insights['average_percent'] ?? 0).'%'],
                ['CBE level', $insights['average_level'] ?? '—'],
                ['Trending up', ($insights['learners_improving_percent'] ?? 0).'%'],
                ['Attempts (30d)', $summary['completed_attempts_last_30_days'] ?? 0],
                ['Guardian email', ($summary['guardian_email_coverage_percent'] ?? 0).'%'],
            ] as [$label, $value])
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-semibold tabular-nums text-gray-950 dark:text-white">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        {{-- Gender inclusion --}}
        @if (! empty($inclusionView))
            @include('filament.partials.inclusion-segments', array_merge($inclusionView, [
                'heading' => 'Gender & inclusion — '.($institution['name'] ?? 'School'),
                'description' => 'Roster and outcome segmentation for this school.',
            ]))
        @endif

        {{-- Classroom breakdown --}}
        @if (! empty($a['classroom_breakdown']))
            <div class="my-6">
                <x-filament::section heading="Classrooms" description="Performance comparison across classes at this school.">
                    <div class="overflow-x-auto rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-white/5">
                                <tr>
                                    <th class="px-4 py-2">Class</th>
                                    <th class="px-4 py-2">Grade</th>
                                    <th class="px-4 py-2 text-end">Students</th>
                                    <th class="px-4 py-2 text-end">Attempts</th>
                                    <th class="px-4 py-2 text-end">Avg %</th>
                                    <th class="px-4 py-2">CBE level</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                                @foreach ($a['classroom_breakdown'] as $row)
                                    <tr>
                                        <td class="px-4 py-2.5 font-medium">{{ $row['classroom_name'] }}</td>
                                        <td class="px-4 py-2.5">{{ $row['grade_level'] ?? '—' }}</td>
                                        <td class="px-4 py-2.5 text-end tabular-nums">{{ $row['student_count'] }}</td>
                                        <td class="px-4 py-2.5 text-end tabular-nums">{{ $row['completed_attempts'] }}</td>
                                        <td class="px-4 py-2.5 text-end tabular-nums font-medium">{{ number_format($row['average_percent'], 1) }}%</td>
                                        <td class="px-4 py-2.5 text-xs">{{ $row['competency_level'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            </div>
        @endif

        {{-- Category / competency areas --}}
        <div class="my-6 grid gap-6 lg:grid-cols-2">
            @if (! empty($a['class_strengths']))
                <x-filament::section heading="Competency strengths" icon="heroicon-o-star" icon-color="success">
                    <ul class="space-y-3 text-sm">
                        @foreach ($a['class_strengths'] as $item)
                            <li class="rounded-lg border border-success-200 bg-success-50/50 p-3 dark:border-success-500/20 dark:bg-success-500/5">
                                <p class="font-medium text-gray-950 dark:text-white">{{ $item['label'] }} — {{ $item['average_percent'] }}%</p>
                                <p class="mt-1 text-gray-600 dark:text-gray-300">{{ $item['insight'] ?? '' }}</p>
                            </li>
                        @endforeach
                    </ul>
                </x-filament::section>
            @endif
            @if (! empty($a['class_weaknesses']))
                <x-filament::section heading="Areas for improvement" icon="heroicon-o-exclamation-triangle" icon-color="warning">
                    <ul class="space-y-3 text-sm">
                        @foreach ($a['class_weaknesses'] as $item)
                            <li class="rounded-lg border border-warning-200 bg-warning-50/50 p-3 dark:border-warning-500/20 dark:bg-warning-500/5">
                                <p class="font-medium text-gray-950 dark:text-white">{{ $item['label'] }} — {{ $item['average_percent'] }}%</p>
                                <p class="mt-1 text-gray-600 dark:text-gray-300">{{ $item['recommended_action'] ?? $item['insight'] ?? '' }}</p>
                            </li>
                        @endforeach
                    </ul>
                </x-filament::section>
            @endif
        </div>

        {{-- Students --}}
        <div class="my-6 grid gap-6 lg:grid-cols-2">
            @include('filament.widgets.platform-students-table', [
                'heading' => 'Top performers',
                'description' => 'At this school.',
                'students' => $a['top_performers'] ?? [],
                'variant' => 'top',
                'bare' => true,
            ])
            @include('filament.widgets.platform-students-table', [
                'heading' => 'Learners needing support',
                'description' => 'Below 50% at this school.',
                'students' => $a['learners_needing_support'] ?? [],
                'variant' => 'support',
                'bare' => true,
            ])
        </div>

        {{-- Inactive learners --}}
        @if (! empty($a['inactive_learners']))
            <div class="my-6">
                <x-filament::section heading="Inactive learners (30 days)" icon="heroicon-o-user-minus" icon-color="danger">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="text-xs uppercase text-gray-500">
                                <tr>
                                    <th class="px-3 py-2">Name</th>
                                    <th class="px-3 py-2">Admission #</th>
                                    <th class="px-3 py-2">Grade</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                                @foreach ($a['inactive_learners'] as $learner)
                                    <tr>
                                        <td class="px-3 py-2">{{ $learner['name'] }}</td>
                                        <td class="px-3 py-2">{{ $learner['admission_number'] ?? '—' }}</td>
                                        <td class="px-3 py-2">{{ $learner['grade_level'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            </div>
        @endif

        {{-- Action items --}}
        @if (! empty($a['action_items']))
            @include('filament.widgets.action-items', [
                'heading' => 'Recommended actions — '.$institution['name'],
                'action_items' => $a['action_items'],
                'bare' => true,
            ])
        @endif
    @endif
</x-filament-panels::page>
