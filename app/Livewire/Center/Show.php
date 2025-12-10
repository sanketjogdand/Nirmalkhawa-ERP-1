<?php

namespace App\Livewire\Center;

use App\Models\Center;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public $title = 'Centers';
    public Center $center;

    public function mount(Center $center): void
    {
        $this->authorize('center.view');
        $this->center = $center->load(['village.taluka.district.state']);
    }

    public function render()
    {
        return view('livewire.center.show')
            ->with(['title_name' => $this->title ?? 'KCB Industries Pvt. Ltd.']);
    }
}
