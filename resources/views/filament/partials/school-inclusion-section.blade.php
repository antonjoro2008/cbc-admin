@php
    $gr = $gender_reporting ?? [];
@endphp

@include('filament.partials.analytics-data-table', [
    'heading' => $heading ?? 'Gender & inclusion — reporting coverage',
    'description' => $description ?? 'Gender data completeness for this school\'s learner roster.',
    'icon' => 'heroicon-o-identification',
    'iconColor' => 'info',
    'empty' => 'No learners on roster.',
    'columns' => [
        ['key' => 'label', 'label' => 'Metric', 'emphasis' => true],
        ['key' => 'value', 'label' => 'Value', 'align' => 'end'],
    ],
    'rows' => [
        ['label' => 'Gender reporting rate', 'value' => number_format($gr['reporting_rate_percent'] ?? 0, 1).'%'],
        ['label' => 'Learners with gender recorded', 'value' => $gr['learners_with_gender'] ?? 0],
        ['label' => 'Gender not recorded', 'value' => $gr['learners_without_gender'] ?? 0],
    ],
])

<div class="mt-6">
    @include('filament.partials.analytics-data-table', [
        'heading' => 'Learners by gender',
        'description' => 'Current roster segmentation.',
        'icon' => 'heroicon-o-user-group',
        'iconColor' => 'success',
        'empty' => 'No roster data for this school.',
        'columns' => [
            ['key' => 'label', 'label' => 'Gender segment', 'emphasis' => true],
            ['key' => 'count', 'label' => 'Learners', 'align' => 'end'],
        ],
        'rows' => $cohort_rows ?? [],
    ])
</div>

@if (! empty($performance_rows))
    <div class="mt-6">
        @include('filament.partials.analytics-data-table', [
            'heading' => 'Average outcome by gender segment',
            'description' => 'Based on completed, marked assessment attempts.',
            'icon' => 'heroicon-o-chart-bar',
            'iconColor' => 'warning',
            'empty' => 'No completed attempts yet.',
            'columns' => [
                ['key' => 'label', 'label' => 'Segment', 'emphasis' => true],
                [
                    'key' => 'average_percent',
                    'label' => 'Avg %',
                    'align' => 'end',
                    'format' => fn ($value) => number_format((float) $value, 1).'%',
                ],
                ['key' => 'attempts', 'label' => 'Attempts', 'align' => 'end'],
                ['key' => 'learners', 'label' => 'Learners', 'align' => 'end'],
            ],
            'rows' => $performance_rows,
        ])
    </div>
@endif
