@props([
    'heading',
    'description' => null,
    'icon' => 'heroicon-o-table-cells',
    'iconColor' => 'gray',
    'columns' => [],
    'rows' => [],
    'empty' => 'No data yet.',
])

<x-filament::section
    :heading="$heading"
    :description="$description"
    :icon="$icon"
    :icon-color="$iconColor"
>
    <div class="overflow-x-auto rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                <tr>
                    @foreach ($columns as $column)
                        <th @class([
                            'px-4 py-3 font-medium',
                            'text-end' => ($column['align'] ?? 'start') === 'end',
                            'text-start' => ($column['align'] ?? 'start') !== 'end',
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
                                'px-4 py-3',
                                'font-medium text-gray-950 dark:text-white' => $column['emphasis'] ?? false,
                                'text-end tabular-nums' => ($column['align'] ?? 'start') === 'end',
                            ])>{!! $value ?? '—' !!}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            {{ $empty }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament::section>
