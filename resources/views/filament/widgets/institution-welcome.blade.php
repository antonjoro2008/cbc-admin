<x-filament-widgets::widget>
    <x-filament::section
        icon="heroicon-o-sparkles"
        icon-color="primary"
        :heading="$welcome_heading"
        description="This dashboard summarises learner engagement, assessment outcomes, and inclusion-related reporting for your institution. Figures update as learners complete assessments on the platform."
    >
        @unless ($has_institution_scope)
            <div
                class="rounded-lg bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-100 dark:ring-amber-500/30"
            >
                <p class="font-medium">Account setup incomplete</p>
                <p class="mt-1 text-amber-800/90 dark:text-amber-100/80">
                    Your user is not linked to an institution record, so analytics cannot be loaded. Please contact support so your account can be associated with the correct organisation.
                </p>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">At a glance</p>
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">
                        Headline metrics below cover your full learner roster, not a single class.
                    </p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">CBE descriptors</p>
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">
                        Overall averages use Kenya CBC wording (BE, AE, ME, EE) based on percentage of total marks.
                    </p>
                </div>
                <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Inclusion</p>
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">
                        Gender and guardian coverage help you see reporting readiness before deep-diving segment outcomes.
                    </p>
                </div>
            </div>
        @endunless
    </x-filament::section>
</x-filament-widgets::widget>
