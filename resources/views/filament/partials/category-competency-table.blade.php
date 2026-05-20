@props([
    'heading',
    'description' => null,
    'records' => [],
    'sort' => 'desc',
])

@include('filament.partials.analytics-data-table', [
    'heading' => $heading,
    'description' => $description,
    'icon' => $sort === 'desc' ? 'heroicon-o-star' : 'heroicon-o-exclamation-triangle',
    'iconColor' => $sort === 'desc' ? 'success' : 'warning',
    'empty' => 'No marked competency data yet.',
    'columns' => [
        ['key' => 'label', 'label' => 'Competency area', 'emphasis' => true],
        [
            'key' => 'average_percent',
            'label' => 'Avg score',
            'align' => 'end',
            'format' => fn ($value) => number_format((float) $value, 1).'%',
        ],
        ['key' => 'competency_level', 'label' => 'CBE level'],
        [
            'key' => 'questions_answered',
            'label' => 'Marked answers',
            'align' => 'end',
        ],
    ],
    'rows' => $records,
])
