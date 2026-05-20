<x-filament-widgets::widget>
    <div class="grid gap-6 lg:grid-cols-2">
        <x-filament::section
            icon="heroicon-o-star"
            icon-color="success"
            heading="Platform competency strengths"
            description="Highest-performing category tags across all marked answers."
        >
            @if (empty($strengths))
                <p class="text-sm text-gray-500">No marked competency data yet.</p>
            @else
                <ul class="space-y-3 text-sm">
                    @foreach ($strengths as $item)
                        <li class="rounded-lg border border-success-200 bg-success-50/50 p-3 dark:border-success-500/20 dark:bg-success-500/5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-medium text-gray-950 dark:text-white">{{ $item['label'] }}</span>
                                <span class="tabular-nums font-semibold text-success-700 dark:text-success-400">{{ $item['average_percent'] }}%</span>
                            </div>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $item['competency_level'] ?? '' }}</p>
                            <p class="mt-1 text-gray-600 dark:text-gray-300">{{ $item['insight'] ?? '' }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>

        <x-filament::section
            icon="heroicon-o-exclamation-triangle"
            icon-color="warning"
            heading="Platform areas needing focus"
            description="Lowest-performing competency tags — candidates for platform-wide intervention."
        >
            @if (empty($weaknesses))
                <p class="text-sm text-gray-500">No marked competency data yet.</p>
            @else
                <ul class="space-y-3 text-sm">
                    @foreach ($weaknesses as $item)
                        <li class="rounded-lg border border-warning-200 bg-warning-50/50 p-3 dark:border-warning-500/20 dark:bg-warning-500/5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-medium text-gray-950 dark:text-white">{{ $item['label'] }}</span>
                                <span class="tabular-nums font-semibold text-warning-700 dark:text-warning-400">{{ $item['average_percent'] }}%</span>
                            </div>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $item['competency_level'] ?? '' }}</p>
                            <p class="mt-1 text-gray-600 dark:text-gray-300">{{ $item['recommended_action'] ?? $item['insight'] ?? '' }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
