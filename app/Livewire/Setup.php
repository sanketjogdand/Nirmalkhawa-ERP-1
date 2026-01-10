<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Pagination\LengthAwarePaginator;

class Setup extends Component
{
    use WithPagination;
    public $title = "Setup";

    public $pm_name = '';
    public $pm_edit_id = null;

    public $uom_name = '';
    public $uom_edit_id = null;

    public $ca_name = '';
    public $ca_edit_id = null;

    public $transaction_name = '';
    public $transaction_edit_id = null;

    public $state_name = '';
    public $district_name = '';
    public $district_state_id = null;
    public $taluka_name = '';
    public $taluka_state_id = null;
    public $taluka_district_id = null;
    public $village_name = '';
    public $village_state_id = null;
    public $village_district_id = null;
    public $village_taluka_id = null;

    // ------- UOMs -------
    public function saveUom(): void
    {
        $this->validate([
            'uom_name' => 'required|string|max:50|unique:uoms,name',
        ]);

        DB::table('uoms')->insert([
            'name' => trim($this->uom_name),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->dispatch('toast', type: 'success', message: 'UOM added.');
        $this->resetUom();
    }

    public function deleteUom(int $id): void
    {
        DB::table('uoms')->where('id', $id)->delete();
        $this->dispatch('toast', type: 'success', message: 'UOM deleted.');
        $this->resetUom();
    }

    private function resetUom(): void
    {
        $this->uom_name = '';
        $this->uom_edit_id = null;
    }

    // ------- Payment Modes -------
    public function savePaymentMode()
    {
        $this->validate([
            'pm_name' => 'required|string|unique:payment_modes,name',
        ]);

        DB::table('payment_modes')->insert([
            'name' => $this->pm_name,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Payment Mode added.');
        $this->resetPaymentMode();
    }

    public function deletePaymentMode($name)
    {
        DB::table('payment_modes')->where('name', $name)->delete();
        $this->dispatch('toast', type: 'success', message: 'Payment Mode deleted.');
        $this->resetPaymentMode();
    }

    private function resetPaymentMode()
    {
        $this->pm_name = '';
    }


    // ------- Company Accounts -------
    public function savecompanyAccounts()
    {
        $this->validate([
            'ca_name' => 'required|string|unique:company_accounts,name',
        ]);

        DB::table('company_accounts')->insert([
            'name' => $this->ca_name,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Payment Mode added.');
        $this->resetcompanyAccounts();
    }

    public function deletecompanyAccounts($name)
    {
        DB::table('company_accounts')->where('name', $name)->delete();
        $this->dispatch('toast', type: 'success', message: 'Payment Mode deleted.');
        $this->resetcompanyAccounts();
    }

    private function resetcompanyAccounts()
    {
        $this->ca_name = '';
    }

    // ------- Transactions Type -------
    public function savetransactionTypes()
    {
        $this->validate([
            'transaction_name' => 'required|string|unique:transaction_types,name',
        ]);

        DB::table('transaction_types')->insert([
            'name' => $this->transaction_name,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Transaction Type added.');
        $this->resettransactionType();
    }

    public function deletetransactionTypes($name)
    {
        DB::table('transaction_types')->where('name', $name)->delete();
        $this->dispatch('toast', type: 'success', message: 'Transaction Type deleted.');
        $this->resettransactionType();
    }

    private function resettransactionType()
    {
        $this->transaction_name = '';
    }

    // ------- Locations: States -------
    public function saveState()
    {
        $this->validate([
            'state_name' => 'required|string|max:100|unique:states,name',
        ]);

        DB::table('states')->insert([
            'name' => $this->state_name,
        ]);

        $this->dispatch('toast', type: 'success', message: 'State added.');
        $this->resetState();
    }

    public function deleteState($id)
    {
        DB::table('states')->where('id', $id)->delete();
        $this->dispatch('toast', type: 'success', message: 'State deleted.');
        $this->resetState();
    }

    private function resetState()
    {
        $this->state_name = '';
    }

    // ------- Locations: Districts -------
    public function saveDistrict()
    {
        $this->validate([
            'district_state_id' => 'required|exists:states,id',
            'district_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('districts', 'name')->where(fn ($query) => $query->where('state_id', $this->district_state_id)),
            ],
        ]);

        DB::table('districts')->insert([
            'name' => $this->district_name,
            'state_id' => $this->district_state_id,
        ]);

        $this->dispatch('toast', type: 'success', message: 'District added.');
        $this->resetDistrict();
    }

    public function deleteDistrict($id)
    {
        DB::table('districts')->where('id', $id)->delete();
        $this->dispatch('toast', type: 'success', message: 'District deleted.');
        $this->resetDistrict();
    }

    public function resetDistrict()
    {
        $this->district_name = '';
        $this->district_state_id = null;
    }

    // ------- Locations: Talukas -------
    public function saveTaluka()
    {
        $this->validate([
            'taluka_state_id' => 'required|exists:states,id',
            'taluka_district_id' => 'required|exists:districts,id',
            'taluka_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('talukas', 'name')->where(fn ($query) => $query->where('district_id', $this->taluka_district_id)),
            ],
        ]);

        $districtBelongsToState = DB::table('districts')
            ->where('id', $this->taluka_district_id)
            ->where('state_id', $this->taluka_state_id)
            ->exists();

        if (! $districtBelongsToState) {
            $this->addError('taluka_district_id', 'Selected district does not belong to the chosen state.');
            return;
        }

        DB::table('talukas')->insert([
            'name' => $this->taluka_name,
            'district_id' => $this->taluka_district_id,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Taluka added.');
        $this->resetTaluka();
    }

    public function deleteTaluka($id)
    {
        DB::table('talukas')->where('id', $id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Taluka deleted.');
        $this->resetTaluka();
    }

    public function resetTaluka()
    {
        $this->taluka_name = '';
        $this->taluka_state_id = null;
        $this->taluka_district_id = null;
    }

    public function updatedTalukaStateId()
    {
        $this->taluka_district_id = null;
    }

    // ------- Locations: Villages -------
    public function saveVillage()
    {
        $this->validate([
            'village_state_id' => 'required|exists:states,id',
            'village_district_id' => 'required|exists:districts,id',
            'village_taluka_id' => 'required|exists:talukas,id',
            'village_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('villages', 'name')->where(fn ($query) => $query->where('taluka_id', $this->village_taluka_id)),
            ],
        ]);

        $districtBelongsToState = DB::table('districts')
            ->where('id', $this->village_district_id)
            ->where('state_id', $this->village_state_id)
            ->exists();

        $talukaBelongsToDistrict = DB::table('talukas')
            ->where('id', $this->village_taluka_id)
            ->where('district_id', $this->village_district_id)
            ->exists();

        if (! $districtBelongsToState || ! $talukaBelongsToDistrict) {
            $this->addError('village_taluka_id', 'Selected location path is invalid for the chosen state and district.');
            return;
        }

        DB::table('villages')->insert([
            'name' => $this->village_name,
            'taluka_id' => $this->village_taluka_id,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Village added.');
        $this->resetVillage();
    }

    public function deleteVillage($id)
    {
        DB::table('villages')->where('id', $id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Village deleted.');
        $this->resetVillage();
    }

    public function resetVillage()
    {
        $this->village_name = '';
        $this->village_state_id = null;
        $this->village_district_id = null;
        $this->village_taluka_id = null;
    }

    public function updatedVillageStateId()
    {
        $this->village_district_id = null;
        $this->village_taluka_id = null;
    }

    public function updatedVillageDistrictId()
    {
        $this->village_taluka_id = null;
    }

    public function render()
    {
        $paymentModes = DB::table('payment_modes')->orderBy('name')->paginate(5, pageName: 'pm');
        $companyAccounts = DB::table('company_accounts')->orderBy('name')->paginate(5, pageName: 'ca');
        $transactionTypes = DB::table('transaction_types')->orderBy('name')->paginate(5, pageName: 'tt');

        $uoms = DB::table('uoms')->orderBy('name')->paginate(10, pageName: 'uom');
        $states = DB::table('states')->orderBy('name')->paginate(10, pageName: 'state');
        $districtQuery = DB::table('districts')
            ->join('states', 'districts.state_id', '=', 'states.id')
            ->select('districts.*', 'states.name as state_name')
            ->orderBy('states.name')
            ->orderBy('districts.name');

        if ($this->district_state_id) {
            $districtQuery->where('districts.state_id', $this->district_state_id);
        }

        $districts = $this->district_state_id
            ? $districtQuery->paginate(10, pageName: 'district')
            : $this->emptyPaginator('district');

        $talukaQuery = DB::table('talukas')
            ->join('districts', 'talukas.district_id', '=', 'districts.id')
            ->join('states', 'districts.state_id', '=', 'states.id')
            ->select('talukas.*', 'districts.name as district_name', 'states.name as state_name')
            ->orderBy('states.name')
            ->orderBy('districts.name')
            ->orderBy('talukas.name');

        if ($this->taluka_state_id && $this->taluka_district_id) {
            $talukaQuery->where('districts.state_id', $this->taluka_state_id)
                ->where('talukas.district_id', $this->taluka_district_id);
        }

        $talukas = ($this->taluka_state_id && $this->taluka_district_id)
            ? $talukaQuery->paginate(10, pageName: 'taluka')
            : $this->emptyPaginator('taluka');

        $villageQuery = DB::table('villages')
            ->join('talukas', 'villages.taluka_id', '=', 'talukas.id')
            ->join('districts', 'talukas.district_id', '=', 'districts.id')
            ->join('states', 'districts.state_id', '=', 'states.id')
            ->select('villages.*', 'talukas.name as taluka_name', 'districts.name as district_name', 'states.name as state_name')
            ->orderBy('states.name')
            ->orderBy('districts.name')
            ->orderBy('talukas.name')
            ->orderBy('villages.name');

        if ($this->village_state_id && $this->village_district_id && $this->village_taluka_id) {
            $villageQuery->where('districts.state_id', $this->village_state_id)
                ->where('talukas.district_id', $this->village_district_id)
                ->where('villages.taluka_id', $this->village_taluka_id);
        }

        $villages = ($this->village_state_id && $this->village_district_id && $this->village_taluka_id)
            ? $villageQuery->paginate(10, pageName: 'village')
            : $this->emptyPaginator('village');

        $stateOptions = DB::table('states')->orderBy('name')->get();
        $districtOptions = $this->taluka_state_id
            ? DB::table('districts')->where('state_id', $this->taluka_state_id)->orderBy('name')->get()
            : [];
        $villageDistrictOptions = $this->village_state_id
            ? DB::table('districts')->where('state_id', $this->village_state_id)->orderBy('name')->get()
            : [];
        $villageTalukaOptions = $this->village_district_id
            ? DB::table('talukas')->where('district_id', $this->village_district_id)->orderBy('name')->get()
            : [];

        return view('livewire.setup',  compact(
            'paymentModes',
            'companyAccounts',
            'transactionTypes',
            'states',
            'districts',
            'talukas',
            'villages',
            'stateOptions',
            'districtOptions',
            'villageDistrictOptions',
            'villageTalukaOptions',
            'uoms'
        ))->with(['title_name' => $this->title ?? "KCB Industries Pvt. Ltd."]);
    }

    private function emptyPaginator(string $pageName): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, 10, 1, ['pageName' => $pageName]);
    }
}
