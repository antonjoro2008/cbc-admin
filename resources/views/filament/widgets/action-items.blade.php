@php
    $priorityColors = [
        'high' => 'danger',
        'medium' => 'warning',
        'low' => 'gray',
    ];
@endphp

@if (empty($bare))
    <x-filament-widgets::widget>
@else
    <div>
@endif
        <x-filament::section
            icon="heroicon-o-light-bulb"
            icon-color="warning"
            :heading="$heading ?? 'Action items'"
            description="Data-driven recommendations based on current platform metrics."
        >
            @if (empty($action_items))
                <p class="text-sm text-gray-500 dark:text-gray-400">No action items at this time.</p>
            @else
                <ul class="space-y-3">
                    @foreach ($action_items as $item)
                        <li
                            class="rounded-lg border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <p class="font-medium text-gray-950 dark:text-white">{{ $item['title'] }}</p>
                                <x-filament::badge :color="$priorityColors[$item['priority'] ?? 'low'] ?? 'gray'">
                                    {{ ucfirst($item['priority'] ?? 'low') }}
                                </x-filament::badge>
                            </div>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $item['description'] }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>
@if (empty($bare))
    </x-filament-widgets::widget>
@else
    </div>
@endif
