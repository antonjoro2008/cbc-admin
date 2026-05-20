<?php

namespace App\Filament\Widgets\SchoolAnalytics;

use App\Filament\Widgets\Concerns\ResolvesInstitutionAnalytics;
use Filament\Widgets\Widget;

class SchoolStudentInsightsWidget extends Widget
{
    use ResolvesInstitutionAnalytics;

    protected string $view = 'filament.widgets.school-student-insights';

    protected int | string | array $columnSpan = 'full';

    public ?int $selectedStudentId = null;

    public function mount(): void
    {
        $this->selectedStudentId = $this->defaultStudentId();
    }

    public function updatedInstitutionId(): void
    {
        $this->selectedStudentId = $this->defaultStudentId();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'studentOptions' => $this->studentOptions(),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function studentOptions(): array
    {
        return collect($this->institutionAnalytics()['student_roster'] ?? [])
            ->mapWithKeys(function (array $row): array {
                $id = (int) ($row['student_id'] ?? 0);
                if ($id === 0) {
                    return [];
                }

                $label = $row['name'] ?? 'Learner';
                if (! empty($row['admission_number'])) {
                    $label .= ' ('.$row['admission_number'].')';
                }

                return [$id => $label];
            })
            ->all();
    }

    protected function defaultStudentId(): ?int
    {
        $roster = $this->institutionAnalytics()['student_roster'] ?? [];

        foreach ($roster as $row) {
            if ((int) ($row['completed_attempts'] ?? 0) > 0) {
                return (int) ($row['student_id'] ?? 0) ?: null;
            }
        }

        $first = $roster[0] ?? null;

        return $first ? (int) ($first['student_id'] ?? 0) : null;
    }
}
