<?php

namespace App\Livewire\SettlementTemplate;

use App\Models\SettlementPeriodTemplate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class View extends Component
{
    use AuthorizesRequests;

    public $title = 'Settlement Period Templates';
    public $templates;
    public $form = [
        'id' => null,
        'name' => '',
        'start_day' => '',
        'end_day' => '',
        'end_of_month' => false,
        'is_active' => true,
    ];

    public function mount(): void
    {
        $this->authorize('settlementtemplate.manage');
        $this->loadTemplates();
    }

    public function render()
    {
        return view('livewire.settlement-template.view', [
            'templates' => $this->templates,
        ])->with(['title_name' => $this->title ?? 'Settlement Period Templates']);
    }

    public function edit(int $id): void
    {
        $record = SettlementPeriodTemplate::findOrFail($id);
        $this->authorize('settlementtemplate.manage');

        $this->form = [
            'id' => $record->id,
            'name' => $record->name,
            'start_day' => $record->start_day,
            'end_day' => $record->end_day,
            'end_of_month' => (bool) $record->end_of_month,
            'is_active' => (bool) $record->is_active,
        ];
    }

    public function resetForm(): void
    {
        $this->form = [
            'id' => null,
            'name' => '',
            'start_day' => '',
            'end_day' => '',
            'end_of_month' => false,
            'is_active' => true,
        ];
    }

    public function save(): void
    {
        $this->authorize('settlementtemplate.manage');

        $data = $this->validate([
            'form.name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('settlement_period_templates', 'name')->ignore($this->form['id']),
            ],
            'form.start_day' => ['required', 'integer', 'between:1,31'],
            'form.end_of_month' => ['boolean'],
            'form.end_day' => [
                'nullable',
                'integer',
                'between:1,31',
                function ($attribute, $value, $fail) {
                    if (! $this->form['end_of_month'] && ($value === null || $value === '')) {
                        $fail('End day is required unless end of month is selected.');
                    }

                    if (! $this->form['end_of_month'] && $value !== null && $value < $this->form['start_day']) {
                        $fail('End day must be greater than or equal to start day.');
                    }
                },
            ],
            'form.is_active' => ['boolean'],
        ], [], [
            'form.name' => 'Name',
            'form.start_day' => 'Start day',
            'form.end_day' => 'End day',
        ]);

        $payload = [
            'name' => $data['form']['name'],
            'start_day' => $data['form']['start_day'],
            'end_day' => $this->form['end_of_month'] ? null : $data['form']['end_day'],
            'end_of_month' => (bool) $data['form']['end_of_month'],
            'is_active' => (bool) $data['form']['is_active'],
        ];

        SettlementPeriodTemplate::updateOrCreate(
            ['id' => $this->form['id']],
            $payload
        );

        session()->flash('success', 'Template saved.');
        $this->resetForm();
        $this->loadTemplates();
    }

    private function loadTemplates(): void
    {
        $this->templates = SettlementPeriodTemplate::orderBy('start_day')->get();
    }
}
