@props([
    'caption' => null,
    'columns' => [],
    'rows' => [],
    'empty' => 'No data yet.',
])

@if ($caption)
    <h4 class="mb-2 text-sm font-semibold text-gray-950 dark:text-white">{{ $caption }}</h4>
@endif

<div @class([
    'overflow-x-auto rounded-lg ring-1 ring-gray-950/5 dark:ring-white/10',
    'mt-4' => $caption,
])>
    <table class="w-full text-left text-sm">
        <thead class="bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
            <tr>
                @foreach ($columns as $column)
                    <th @class([
                        'px-3 py-2',
                        'text-end' => ($column['align'] ?? 'start') === 'end',
                    ])>{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
            @forelse ($rows as $row)
                <tr class="text-gray-700 dark:text-gray-200">
                    @foreach ($columns as $column)
                        @php
                            $value = data_get($row, $column['key']);
                            if (isset($column['format']) && is_callable($column['format'])) {
                                $value = $column['format']($value, $row);
                            }
                        @endphp
                        <td @class([
                            'px-3 py-2',
                            'font-medium text-gray-950 dark:text-white' => $column['emphasis'] ?? false,
                            'text-end tabular-nums' => ($column['align'] ?? 'start') === 'end',
                        ])>{!! $value ?? '—' !!}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                        {{ $empty }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
