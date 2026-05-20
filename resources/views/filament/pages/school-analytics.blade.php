<x-filament-panels::page>
    <div class="mb-6 max-w-xl">
        {{ $this->institutionSelect }}
    </div>

    @php
        $a = $analytics ?? [];
        $summary = $a['summary'] ?? [];
        $insights = $a['insights'] ?? [];
        $institution = $a['institution'] ?? null;
        $learnerCount = (int) ($summary['learners'] ?? 0);
        $gr = $inclusionView['gender_reporting'] ?? [];
        $inactiveIds = collect($a['inactive_learners'] ?? [])->pluck('student_id')->flip();
        $studentRows = collect($a['student_roster'] ?? [])->map(function (array $row) use ($inactiveIds): array {
            $attempts = (int) ($row['completed_attempts'] ?? 0);
            $avg = $row['average_percent'] ?? null;

            if ($inactiveIds->has($row['student_id'] ?? null)) {
                $status = 'Inactive (30d)';
            } elseif ($attempts === 0) {
                $status = 'No attempts yet';
            } elseif ($avg !== null && (float) $avg < 50) {
                $status = 'Needs support';
            } else {
                $status = 'On track';
            }

            return array_merge($row, ['status' => $status]);
        })->all();
        $percentCol = fn ($value) => $value !== null ? number_format((float) $value, 1).'%' : '—';
        $competencyRows = collect($a['category_breakdown'] ?? [])->sortByDesc('average_percent')->values()->all();
    @endphp

    @if (! $institutionId || ! $institution)
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">No institutions registered yet.</p>
        </x-filament::section>
    @elseif ($learnerCount === 0)
        <x-filament::section
            icon="heroicon-o-building-office-2"
            :heading="$institution['name']"
            description="This school has no learners on the platform yet."
        >
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Once students are linked to this institution, analytics will appear here.
            </p>
        </x-filament::section>
    @else
        <p class="mb-6 text-lg font-semibold text-gray-950 dark:text-white">{{ $institution['name'] }}</p>

        {{-- Overview: compact stats + optional classrooms --}}
        <x-filament::section
            heading="Overview"
            icon="heroicon-o-chart-bar"
            icon-color="primary"
            class="mb-6"
        >
            @include('filament.partials.analytics-stats-grid', [
                'stats' => [
                    ['label' => 'Learners', 'value' => $summary['learners'] ?? 0],
                    ['label' => 'Teachers', 'value' => $summary['teachers'] ?? 0],
                    ['label' => 'Classrooms', 'value' => $summary['classrooms'] ?? 0],
                    ['label' => 'Average score', 'value' => number_format($insights['average_percent'] ?? 0, 1).'%'],
                    ['label' => 'CBE level', 'value' => $insights['average_level'] ?? '—'],
                    ['label' => 'Trending up', 'value' => ($insights['learners_improving_percent'] ?? 0).'%'],
                    ['label' => 'Attempts (30d)', 'value' => $summary['completed_attempts_last_30_days'] ?? 0],
                    ['label' => 'Guardian email', 'value' => number_format($summary['guardian_email_coverage_percent'] ?? 0, 1).'%'],
                ],
            ])

            @if (! empty($a['classroom_breakdown']))
                @include('filament.partials.analytics-table', [
                    'caption' => 'Classrooms',
                    'columns' => [
                        ['key' => 'classroom_name', 'label' => 'Class', 'emphasis' => true],
                        ['key' => 'grade_level', 'label' => 'Grade'],
                        ['key' => 'student_count', 'label' => 'Students', 'align' => 'end'],
                        ['key' => 'completed_attempts', 'label' => 'Attempts', 'align' => 'end'],
                        ['key' => 'average_percent', 'label' => 'Avg %', 'align' => 'end', 'format' => $percentCol],
                        ['key' => 'competency_level', 'label' => 'CBE'],
                    ],
                    'rows' => $a['classroom_breakdown'],
                    'empty' => 'No classrooms.',
                ])
            @endif
        </x-filament::section>

        {{-- Gender & inclusion: one section, stat pills + up to two tables --}}
        @if (! empty($inclusionView))
            <x-filament::section
                heading="Gender & inclusion"
                description="Roster segmentation and outcomes by gender (marked attempts only)."
                icon="heroicon-o-chart-pie"
                icon-color="info"
                class="mb-6"
            >
                <dl class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg bg-gray-50 px-4 py-3 dark:bg-white/5">
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Gender reporting</dt>
                        <dd class="mt-1 text-lg font-semibold tabular-nums">{{ number_format($gr['reporting_rate_percent'] ?? 0, 1) }}%</dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 px-4 py-3 dark:bg-white/5">
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Recorded</dt>
                        <dd class="mt-1 text-lg font-semibold tabular-nums">{{ $gr['learners_with_gender'] ?? 0 }}</dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 px-4 py-3 dark:bg-white/5">
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Not recorded</dt>
                        <dd class="mt-1 text-lg font-semibold tabular-nums">{{ $gr['learners_without_gender'] ?? 0 }}</dd>
                    </div>
                </dl>

                @if (! empty($inclusionView['cohort_rows']))
                    @include('filament.partials.analytics-table', [
                        'caption' => 'Roster by gender',
                        'columns' => [
                            ['key' => 'label', 'label' => 'Segment', 'emphasis' => true],
                            ['key' => 'count', 'label' => 'Learners', 'align' => 'end'],
                        ],
                        'rows' => $inclusionView['cohort_rows'],
                    ])
                @endif

                @if (! empty($inclusionView['performance_rows']))
                    @include('filament.partials.analytics-table', [
                        'caption' => 'Outcomes by gender',
                        'columns' => [
                            ['key' => 'label', 'label' => 'Segment', 'emphasis' => true],
                            ['key' => 'average_percent', 'label' => 'Avg %', 'align' => 'end', 'format' => $percentCol],
                            ['key' => 'attempts', 'label' => 'Attempts', 'align' => 'end'],
                            ['key' => 'learners', 'label' => 'Learners', 'align' => 'end'],
                        ],
                        'rows' => $inclusionView['performance_rows'],
                    ])
                @endif
            </x-filament::section>
        @endif

        {{-- Single competency table (avoids duplicate strengths / weaknesses blocks) --}}
        @if (! empty($competencyRows))
            <x-filament::section
                heading="Competency areas"
                description="Average scores from marked answers, grouped by question category tag."
                icon="heroicon-o-tag"
                icon-color="warning"
                class="mb-6"
            >
                @include('filament.partials.analytics-table', [
                    'columns' => [
                        ['key' => 'label', 'label' => 'Area', 'emphasis' => true],
                        ['key' => 'average_percent', 'label' => 'Avg %', 'align' => 'end', 'format' => $percentCol],
                        ['key' => 'competency_level', 'label' => 'CBE'],
                        ['key' => 'questions_answered', 'label' => 'Answers', 'align' => 'end'],
                    ],
                    'rows' => $competencyRows,
                    'empty' => 'No marked competency data yet.',
                ])
            </x-filament::section>
        @endif

        @if (! empty($a['assessment_usage']))
            <x-filament::section
                heading="Assessment usage"
                description="How assessments are used at this school."
                icon="heroicon-o-clipboard-document-list"
                icon-color="info"
                class="mb-6"
            >
                @include('filament.partials.analytics-table', [
                    'columns' => [
                        ['key' => 'assessment_name', 'label' => 'Assessment', 'emphasis' => true],
                        ['key' => 'total_attempts', 'label' => 'Attempts', 'align' => 'end'],
                        ['key' => 'completed_attempts', 'label' => 'Completed', 'align' => 'end'],
                        ['key' => 'average_score_percent', 'label' => 'Avg %', 'align' => 'end', 'format' => $percentCol],
                    ],
                    'rows' => $a['assessment_usage'],
                    'empty' => 'No assessment activity yet.',
                ])
            </x-filament::section>
        @endif

        {{-- One learners table: roster + status (replaces top/support/inactive duplicates) --}}
        <x-filament::section
            heading="Learners"
            description="All students at this school — sort by score or filter by status in the table."
            icon="heroicon-o-academic-cap"
            icon-color="success"
            class="mb-6"
        >
            @include('filament.partials.analytics-table', [
                'columns' => [
                    ['key' => 'name', 'label' => 'Student', 'emphasis' => true],
                    ['key' => 'admission_number', 'label' => 'Admission #'],
                    ['key' => 'grade_level', 'label' => 'Grade'],
                    ['key' => 'classroom_name', 'label' => 'Class'],
                    ['key' => 'gender', 'label' => 'Gender'],
                    ['key' => 'status', 'label' => 'Status'],
                    ['key' => 'completed_attempts', 'label' => 'Attempts', 'align' => 'end'],
                    [
                        'key' => 'average_percent',
                        'label' => 'Avg %',
                        'align' => 'end',
                        'format' => fn ($value, $row) => $value !== null
                            ? ($row['status'] === 'Needs support'
                                ? '<span class="font-medium text-danger-600 dark:text-danger-400">'.number_format((float) $value, 1).'%</span>'
                                : number_format((float) $value, 1).'%')
                            : '—',
                    ],
                    ['key' => 'competency_level', 'label' => 'CBE'],
                ],
                'rows' => $studentRows,
                'empty' => 'No students at this school.',
            ])
        </x-filament::section>

        @if (! empty($a['action_items']))
            <div class="mb-6">
                @include('filament.widgets.action-items', [
                    'heading' => 'Recommended actions',
                    'action_items' => $a['action_items'],
                    'bare' => true,
                ])
            </div>
        @endif
    @endif
</x-filament-panels::page>
