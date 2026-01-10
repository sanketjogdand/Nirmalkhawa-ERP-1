<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="border-radius: 4px; padding:1rem;">
        <h2 class="page-heading">Payment Modes</h2>
        <form wire:submit.prevent="savePaymentMode">
            <div class="form-grid">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" wire:model="pm_name" class="input-field" required/>
                </div>
                @error('pm_name') <div class="muted" style="color:#fecaca">{{ $message }}</div> @enderror
                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn-submit">Save</button>
                </div>
            </div>
        </form>
        <div class="table-wrapper mt-4">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700">Name</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($paymentModes as $pm)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $pm->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700"><button class="btn-danger" wire:click="deletePaymentMode('{{ $pm->name }}')">Delete</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrapper">{{ $paymentModes->links() }}</div>
    </div>




    <div style="border-radius: 4px; padding:1rem;">
        <h2 class="page-heading">Accounts</h2>
        <form wire:submit.prevent="savecompanyAccounts">
            <div class="form-grid">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" wire:model="ca_name" class="input-field"/>
                </div>
                @error('ca_name') <div class="muted" style="color:#fecaca">{{ $message }}</div> @enderror
                <div style="margin-top: 1.5rem;">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-submit">Save</button>
                </div>
            </div>
        </form>
        <div class="table-wrapper mt-4">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700">Name</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($companyAccounts as $ca)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $ca->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700"><button class="btn-danger" wire:click="deletecompanyAccounts('{{ $ca->name }}')">Delete</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrapper">{{ $companyAccounts->links() }}</div>
    </div>




    <div style="border-radius: 4px; padding:1rem;">
        <h2 class="page-heading">Transaction Types</h2>
        <form wire:submit.prevent="savetransactionTypes">
            <div class="form-grid">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" wire:model="transaction_name" class="input-field"/>
                </div>
                @error('transaction_name') <div class="muted" style="color:#fecaca">{{ $message }}</div> @enderror
                <div style="margin-top: 1.5rem;">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-submit">Save</button>
                </div>
            </div>
        </form>
        <div class="table-wrapper mt-4">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700">Name</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($transactionTypes as $tt)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $tt->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700"><button class="btn-danger" wire:click="deletetransactionTypes('{{ $tt->name }}')">Delete</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrapper">{{ $transactionTypes->links() }}</div>
    </div>



    <div style="border-radius: 4px; padding:1rem;">
        <h2 class="page-heading">Units of Measure</h2>
        <form wire:submit.prevent="saveUom">
            <div class="form-grid">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" wire:model="uom_name" class="input-field" required/>
                </div>
                @error('uom_name') <div class="muted" style="color:#fecaca">{{ $message }}</div> @enderror
                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn-submit">Save</button>
                </div>
            </div>
        </form>
        <div class="table-wrapper mt-4">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700">Name</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($uoms as $uom)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $uom->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700"><button class="btn-danger" wire:click="deleteUom('{{ $uom->id }}')">Delete</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrapper">{{ $uoms->links() }}</div>
    </div>


    <div style="border-radius: 4px; padding:1rem;">
        <h2 class="page-heading">States</h2>
        <form wire:submit.prevent="saveState">
            <div class="form-grid">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" wire:model="state_name" class="input-field" required/>
                </div>
                @error('state_name') <div class="muted" style="color:#fecaca">{{ $message }}</div> @enderror
                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn-submit">Save</button>
                </div>
            </div>
        </form>
        <div class="table-wrapper mt-4">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700">Name</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($states as $state)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $state->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700"><button class="btn-danger" wire:click="deleteState('{{ $state->id }}')">Delete</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrapper">{{ $states->links() }}</div>
    </div>


    <div style="border-radius: 4px; padding:1rem;">
        <h2 class="page-heading">Districts</h2>
        <form wire:submit.prevent="saveDistrict">
            <div class="form-grid">
                <div class="form-group">
                    <label>State</label>
                    <select wire:model.live="district_state_id" class="input-field" required>
                        <option value="">Select State</option>
                        @foreach($stateOptions as $state)
                            <option value="{{ $state->id }}">{{ $state->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>District</label>
                    <input type="text" wire:model="district_name" class="input-field" required/>
                </div>
                @error('district_state_id') <div class="muted" style="color:#fecaca">{{ $message }}</div> @enderror
                @error('district_name') <div class="muted" style="color:#fecaca">{{ $message }}</div> @enderror
                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn-submit">Save</button>
                </div>
            </div>
        </form>
        <div class="table-wrapper mt-4">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700">District</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">State</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($districts as $district)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $district->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $district->state_name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700"><button class="btn-danger" wire:click="deleteDistrict('{{ $district->id }}')">Delete</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrapper">{{ $districts->links() }}</div>
    </div>


    <div style="border-radius: 4px; padding:1rem;">
        <h2 class="page-heading">Talukas</h2>
        <form wire:submit.prevent="saveTaluka">
            <div class="form-grid">
                <div class="form-group">
                    <label>State</label>
                    <select wire:model.live="taluka_state_id" class="input-field" required>
                        <option value="">Select State</option>
                        @foreach($stateOptions as $state)
                            <option value="{{ $state->id }}">{{ $state->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>District</label>
                    <select wire:model.live="taluka_district_id" class="input-field" required>
                        <option value="">Select District</option>
                        @foreach($districtOptions as $district)
                            <option value="{{ $district->id }}">{{ $district->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Taluka</label>
                    <input type="text" wire:model="taluka_name" class="input-field" required/>
                </div>
                @error('taluka_state_id') <div class="muted" style="color:#fecaca">{{ $message }}</div> @enderror
                @error('taluka_district_id') <div class="muted" style="color:#fecaca">{{ $message }}</div> @enderror
                @error('taluka_name') <div class="muted" style="color:#fecaca">{{ $message }}</div> @enderror
                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn-submit">Save</button>
                </div>
            </div>
        </form>
        <div class="table-wrapper mt-4">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700">Taluka</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">District</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">State</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($talukas as $taluka)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $taluka->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $taluka->district_name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $taluka->state_name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700"><button class="btn-danger" wire:click="deleteTaluka('{{ $taluka->id }}')">Delete</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrapper">{{ $talukas->links() }}</div>
    </div>


    <div style="border-radius: 4px; padding:1rem;">
        <h2 class="page-heading">Villages</h2>
        <form wire:submit.prevent="saveVillage">
            <div class="form-grid">
                <div class="form-group">
                    <label>State</label>
                    <select wire:model.live="village_state_id" class="input-field" required>
                        <option value="">Select State</option>
                        @foreach($stateOptions as $state)
                            <option value="{{ $state->id }}">{{ $state->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>District</label>
                    <select wire:model.live="village_district_id" class="input-field" required>
                        <option value="">Select District</option>
                        @foreach($villageDistrictOptions as $district)
                            <option value="{{ $district->id }}">{{ $district->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Taluka</label>
                    <select wire:model.live="village_taluka_id" class="input-field" required>
                        <option value="">Select Taluka</option>
                        @foreach($villageTalukaOptions as $taluka)
                            <option value="{{ $taluka->id }}">{{ $taluka->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Village</label>
                    <input type="text" wire:model="village_name" class="input-field" required/>
                </div>
                @error('village_state_id') <div class="muted" style="color:#fecaca">{{ $message }}</div> @enderror
                @error('village_district_id') <div class="muted" style="color:#fecaca">{{ $message }}</div> @enderror
                @error('village_taluka_id') <div class="muted" style="color:#fecaca">{{ $message }}</div> @enderror
                @error('village_name') <div class="muted" style="color:#fecaca">{{ $message }}</div> @enderror
                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn-submit">Save</button>
                </div>
            </div>
        </form>
        <div class="table-wrapper mt-4">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700">Village</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Taluka</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">District</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">State</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($villages as $village)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $village->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $village->taluka_name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $village->district_name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $village->state_name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700"><button class="btn-danger" wire:click="deleteVillage('{{ $village->id }}')">Delete</button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrapper">{{ $villages->links() }}</div>
    </div>
</div>
