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
                Once students are linked to this institution, analytics for classrooms, gender equity, competency areas, and individual outcomes will appear here.
            </p>
        </x-filament::section>
    @else
        <x-filament::section
            icon="heroicon-o-building-office-2"
            :heading="$institution['name']"
            description="Institution-scoped analytics — learners, classrooms, gender equity, competency areas, and student outcomes."
            class="mb-6"
        />

        @include('filament.partials.analytics-stats-table', [
            'stats' => [
                ['label' => 'Learners', 'value' => $summary['learners'] ?? 0],
                ['label' => 'Teachers', 'value' => $summary['teachers'] ?? 0],
                ['label' => 'Classrooms', 'value' => $summary['classrooms'] ?? 0],
                ['label' => 'Average score', 'value' => number_format($insights['average_percent'] ?? 0, 1).'%'],
                ['label' => 'CBE level', 'value' => $insights['average_level'] ?? '—'],
                ['label' => 'Learners trending up', 'value' => ($insights['learners_improving_percent'] ?? 0).'%'],
                ['label' => 'Completed attempts (30 days)', 'value' => $summary['completed_attempts_last_30_days'] ?? 0],
                ['label' => 'Guardian email coverage', 'value' => number_format($summary['guardian_email_coverage_percent'] ?? 0, 1).'%'],
            ],
        ])

        @if (! empty($inclusionView))
            <div class="my-6">
                @include('filament.partials.school-inclusion-section', array_merge($inclusionView, [
                    'heading' => 'Gender & inclusion — '.$institution['name'],
                    'description' => 'Roster and outcome segmentation for this school.',
                ]))
            </div>
        @endif

        @if (! empty($a['classroom_breakdown']))
            <div class="my-6">
                @include('filament.partials.analytics-data-table', [
                    'heading' => 'Classrooms',
                    'description' => 'Performance comparison across classes at this school.',
                    'icon' => 'heroicon-o-rectangle-group',
                    'iconColor' => 'info',
                    'columns' => [
                        ['key' => 'classroom_name', 'label' => 'Class', 'emphasis' => true],
                        ['key' => 'grade_level', 'label' => 'Grade'],
                        ['key' => 'student_count', 'label' => 'Students', 'align' => 'end'],
                        ['key' => 'completed_attempts', 'label' => 'Attempts', 'align' => 'end'],
                        [
                            'key' => 'average_percent',
                            'label' => 'Avg %',
                            'align' => 'end',
                            'format' => fn ($value) => number_format((float) $value, 1).'%',
                        ],
                        ['key' => 'competency_level', 'label' => 'CBE level'],
                    ],
                    'rows' => $a['classroom_breakdown'],
                ])
            </div>
        @endif

        <div class="my-6 grid gap-6 lg:grid-cols-2">
            @if (! empty($a['class_strengths']))
                @include('filament.partials.category-competency-table', [
                    'heading' => 'Competency strengths',
                    'description' => 'Highest-scoring category tags from marked answers.',
                    'records' => $a['class_strengths'],
                    'sort' => 'desc',
                ])
            @endif
            @if (! empty($a['class_weaknesses']))
                @include('filament.partials.category-competency-table', [
                    'heading' => 'Areas for improvement',
                    'description' => 'Lowest-scoring competency tags at this school.',
                    'records' => $a['class_weaknesses'],
                    'sort' => 'asc',
                ])
            @endif
        </div>

        {{-- Full student roster — all learners at this school --}}
        @if (! empty($a['student_roster']))
            <div class="my-6">
                @include('filament.partials.analytics-data-table', [
                    'heading' => 'All students — performance summary',
                    'description' => 'Every learner at this school with assessment outcomes where available.',
                    'icon' => 'heroicon-o-academic-cap',
                    'iconColor' => 'primary',
                    'empty' => 'No students registered at this school.',
                    'columns' => [
                        ['key' => 'name', 'label' => 'Student', 'emphasis' => true],
                        ['key' => 'admission_number', 'label' => 'Admission #'],
                        ['key' => 'grade_level', 'label' => 'Grade'],
                        ['key' => 'classroom_name', 'label' => 'Class'],
                        ['key' => 'gender', 'label' => 'Gender'],
                        ['key' => 'completed_attempts', 'label' => 'Attempts', 'align' => 'end'],
                        [
                            'key' => 'average_percent',
                            'label' => 'Avg %',
                            'align' => 'end',
                            'format' => fn ($value) => $value !== null ? number_format((float) $value, 1).'%' : '—',
                        ],
                        ['key' => 'competency_level', 'label' => 'CBE level'],
                    ],
                    'rows' => $a['student_roster'],
                ])
            </div>
        @endif

        <div class="my-6 grid gap-6 lg:grid-cols-2">
            @include('filament.widgets.platform-students-table', [
                'heading' => 'Top performers',
                'description' => 'Highest average scores at this school.',
                'students' => $a['top_performers'] ?? [],
                'variant' => 'top',
                'bare' => true,
                'hide_school' => true,
            ])
            @include('filament.widgets.platform-students-table', [
                'heading' => 'Learners needing support',
                'description' => 'Below 50% average at this school.',
                'students' => $a['learners_needing_support'] ?? [],
                'variant' => 'support',
                'bare' => true,
                'hide_school' => true,
            ])
        </div>

        @if (! empty($a['inactive_learners']))
            <div class="my-6">
                @include('filament.partials.analytics-data-table', [
                    'heading' => 'Inactive learners (30 days)',
                    'description' => 'Students with no completed attempts in the last 30 days.',
                    'icon' => 'heroicon-o-user-minus',
                    'iconColor' => 'danger',
                    'columns' => [
                        ['key' => 'name', 'label' => 'Name', 'emphasis' => true],
                        ['key' => 'admission_number', 'label' => 'Admission #'],
                        ['key' => 'grade_level', 'label' => 'Grade'],
                    ],
                    'rows' => $a['inactive_learners'],
                ])
            </div>
        @endif

        @if (! empty($a['action_items']))
            @include('filament.widgets.action-items', [
                'heading' => 'Recommended actions — '.$institution['name'],
                'action_items' => $a['action_items'],
                'bare' => true,
            ])
        @endif
    @endif
</x-filament-panels::page>
