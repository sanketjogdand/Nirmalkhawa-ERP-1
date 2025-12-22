<?php

namespace App\Livewire\CommissionPolicy;

use App\Models\CommissionPolicy;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Commission Policies';
    public ?int $policyId = null;
    public $code;
    public $milk_type = 'CM';
    public $value;
    public $is_active = true;

    public function mount($policy = null): void
    {
        if ($policy) {
            $record = CommissionPolicy::findOrFail($policy);
            $this->authorize('commissionpolicy.update');
            $this->policyId = $record->id;
            $this->fill($record->only(['code', 'milk_type', 'value', 'is_active']));
        } else {
            $this->authorize('commissionpolicy.create');
            $this->code = $this->generateCode();
        }
    }

    protected function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^CP-(CM|BM)-[0-9]{4}-[0-9]{3}$/',
                Rule::unique('commission_policies', 'code')->ignore($this->policyId),
            ],
            'milk_type' => ['required', 'in:CM,BM'],
            'value' => ['required', 'numeric', 'gt:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function save()
    {
        $data = $this->validate();
        $data['is_active'] = (bool) $data['is_active'];
        $data['code'] = strtoupper(trim($data['code']));

        if ($this->policyId) {
            CommissionPolicy::where('id', $this->policyId)->update($data);
            session()->flash('success', 'Commission policy updated.');
        } else {
            $policy = CommissionPolicy::create($data);
            $this->policyId = $policy->id;
            session()->flash('success', 'Commission policy created.');
        }

        return redirect()->route('commission-policies.view');
    }

    public function render()
    {
        return view('livewire.commission-policy.form')
            ->with(['title_name' => $this->title ?? 'Commission Policies']);
    }

    private function generateCode(): string
    {
        $year = now()->format('Y');
        $prefix = 'CP-'.$this->milk_type.'-'.$year;

        $latest = CommissionPolicy::where('milk_type', $this->milk_type)
            ->where('code', 'like', $prefix.'-%')
            ->orderBy('code', 'desc')
            ->value('code');

        $next = 1;
        if ($latest && preg_match('/^CP-(CM|BM)-\d{4}-(\d{3})$/', $latest, $matches)) {
            $next = (int) $matches[2] + 1;
        }

        return sprintf('%s-%03d', $prefix, $next);
    }
}
