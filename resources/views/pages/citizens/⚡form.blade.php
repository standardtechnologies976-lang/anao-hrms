<?php

use App\Models\Barangay;
use App\Models\Citizen;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new
#[Layout('layouts::app')]
#[Title('Citizen Record')]
class extends Component
{
    public ?int $editingId = null;
    public ?string $citizenNo = null;

    public string $last_name = '';
    public string $first_name = '';
    public string $middle_name = '';
    public string $suffix = '';
    public string $sex = '';
    public string $birth_date = '';
    public string $birth_place = '';
    public string $civil_status = '';
    public string $contact_no = '';
    public $barangay_id = '';
    public string $purok = '';
    public string $philhealth_no = '';
    public string $philsys_no = '';
    public string $occupation = '';
    public string $blood_type = '';
    public string $religion = '';
    public string $indigenous_group = '';
    public bool $is_pwd = false;
    public bool $is_senior = false;
    public bool $is_4ps = false;
    public bool $is_solo_parent = false;
    public bool $is_pregnant = false;
    public bool $deceased = false;
    public string $date_of_death = '';
    public string $remarks = '';

    public function mount(?Citizen $citizen = null): void
    {
        if ($citizen && $citizen->exists) {
            $this->editingId    = $citizen->id;
            $this->citizenNo    = $citizen->citizen_no;
            $this->last_name    = $citizen->last_name;
            $this->first_name   = $citizen->first_name;
            $this->middle_name  = $citizen->middle_name ?? '';
            $this->suffix       = $citizen->suffix ?? '';
            $this->sex          = $citizen->sex ?? '';
            $this->birth_date   = $citizen->birth_date?->format('Y-m-d') ?? '';
            $this->birth_place  = $citizen->birth_place ?? '';
            $this->civil_status = $citizen->civil_status ?? '';
            $this->contact_no   = $citizen->contact_no ?? '';
            $this->barangay_id  = $citizen->barangay_id ?? '';
            $this->purok        = $citizen->purok ?? '';
            $this->philhealth_no = $citizen->philhealth_no ?? '';
            $this->philsys_no   = $citizen->philsys_no ?? '';
            $this->occupation   = $citizen->occupation ?? '';
            $this->blood_type   = $citizen->blood_type ?? '';
            $this->religion     = $citizen->religion ?? '';
            $this->indigenous_group = $citizen->indigenous_group ?? '';
            $this->is_pwd       = (bool) $citizen->is_pwd;
            $this->is_senior    = (bool) $citizen->is_senior;
            $this->is_4ps       = (bool) $citizen->is_4ps;
            $this->is_solo_parent = (bool) $citizen->is_solo_parent;
            $this->is_pregnant  = (bool) $citizen->is_pregnant;
            $this->deceased     = (bool) $citizen->deceased;
            $this->date_of_death = $citizen->date_of_death?->format('Y-m-d') ?? '';
            $this->remarks      = $citizen->remarks ?? '';
        }
    }

    #[Computed]
    public function barangayList()
    {
        return Barangay::orderBy('name')->get();
    }

    public function save()
    {
        $data = $this->validate([
            'last_name'        => 'required|string|max:255',
            'first_name'       => 'required|string|max:255',
            'middle_name'      => 'nullable|string|max:255',
            'suffix'           => 'nullable|string|max:50',
            'sex'              => 'nullable|in:male,female',
            'birth_date'       => 'nullable|date',
            'birth_place'      => 'nullable|string|max:255',
            'civil_status'     => 'nullable|string|max:50',
            'contact_no'       => 'nullable|string|max:50',
            'barangay_id'      => 'nullable|exists:barangays,id',
            'purok'            => 'nullable|string|max:255',
            'philhealth_no'    => 'nullable|string|max:50',
            'philsys_no'       => 'nullable|string|max:50',
            'occupation'       => 'nullable|string|max:255',
            'blood_type'       => 'nullable|string|max:10',
            'religion'         => 'nullable|string|max:255',
            'indigenous_group' => 'nullable|string|max:255',
            'is_pwd'           => 'boolean',
            'is_senior'        => 'boolean',
            'is_4ps'           => 'boolean',
            'is_solo_parent'   => 'boolean',
            'is_pregnant'      => 'boolean',
            'deceased'         => 'boolean',
            'date_of_death'    => 'nullable|date',
            'remarks'          => 'nullable|string',
        ]);

        $data['barangay_id'] = $data['barangay_id'] ?: null;
        $data['birth_date'] = $data['birth_date'] ?: null;
        $data['date_of_death'] = $data['date_of_death'] ?: null;

        Citizen::updateOrCreate(['id' => $this->editingId], $data);

        Flux::toast(text: $this->editingId ? 'Citizen record updated.' : 'Citizen registered.', variant: 'success');

        return $this->redirect(route('citizens.index'), navigate: true);
    }
};
?>

<div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-2">
    <div class="flex items-center gap-3">
        <flux:button icon="arrow-left" variant="ghost" :href="route('citizens.index')" wire:navigate />
        <div>
            <flux:heading size="xl">{{ $editingId ? 'Edit Citizen Record' : 'Register Citizen' }}</flux:heading>
            <flux:text class="mt-1">{{ $editingId ? $citizenNo : 'A citizen number is generated automatically on save.' }}</flux:text>
        </div>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        {{-- Personal information --}}
        <flux:card class="space-y-4">
            <flux:heading size="lg">Personal Information</flux:heading>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <flux:input wire:model="last_name" label="Last name" required />
                <flux:input wire:model="first_name" label="First name" required />
                <flux:input wire:model="middle_name" label="Middle name" />
                <flux:input wire:model="suffix" label="Suffix" placeholder="Jr., III" />
                <flux:select wire:model="sex" label="Sex" placeholder="Select...">
                    <flux:select.option value="male">Male</flux:select.option>
                    <flux:select.option value="female">Female</flux:select.option>
                </flux:select>
                <flux:input wire:model="birth_date" label="Birth date" type="date" />
                <flux:input wire:model="birth_place" label="Birth place" />
                <flux:select wire:model="civil_status" label="Civil status" placeholder="Select...">
                    <flux:select.option value="Single">Single</flux:select.option>
                    <flux:select.option value="Married">Married</flux:select.option>
                    <flux:select.option value="Widowed">Widowed</flux:select.option>
                    <flux:select.option value="Separated">Separated</flux:select.option>
                    <flux:select.option value="Divorced">Divorced</flux:select.option>
                </flux:select>
                <flux:input wire:model="contact_no" label="Contact no" placeholder="09xx" />
            </div>
        </flux:card>

        {{-- Address --}}
        <flux:card class="space-y-4">
            <flux:heading size="lg">Address</flux:heading>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:select wire:model="barangay_id" label="Barangay" placeholder="Select barangay...">
                    @foreach ($this->barangayList as $b)
                        <flux:select.option :value="$b->id">{{ $b->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="purok" label="Purok / Sitio / Street" />
            </div>
        </flux:card>

        {{-- Government IDs & profile --}}
        <flux:card class="space-y-4">
            <flux:heading size="lg">Government IDs & Profile</flux:heading>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <flux:input wire:model="philhealth_no" label="PhilHealth no" />
                <flux:input wire:model="philsys_no" label="PhilSys / National ID" />
                <flux:input wire:model="occupation" label="Occupation" />
                <flux:input wire:model="blood_type" label="Blood type" placeholder="O+, A-" />
                <flux:input wire:model="religion" label="Religion" />
                <flux:input wire:model="indigenous_group" label="Indigenous group" />
            </div>
        </flux:card>

        {{-- Classification --}}
        <flux:card class="space-y-4">
            <flux:heading size="lg">Classification & Status</flux:heading>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                <flux:checkbox wire:model="is_senior" label="Senior Citizen" />
                <flux:checkbox wire:model="is_pwd" label="PWD" />
                <flux:checkbox wire:model="is_4ps" label="4Ps Member" />
                <flux:checkbox wire:model="is_solo_parent" label="Solo Parent" />
                <flux:checkbox wire:model="is_pregnant" label="Pregnant" />
                <flux:checkbox wire:model="deceased" label="Deceased" />
            </div>
            @if ($deceased)
                <flux:input wire:model="date_of_death" label="Date of death" type="date" class="max-w-xs" />
            @endif
            <flux:textarea wire:model="remarks" label="Remarks" rows="2" />
        </flux:card>

        <div class="flex justify-end gap-2">
            <flux:button variant="ghost" :href="route('citizens.index')" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">{{ $editingId ? 'Update Record' : 'Register Citizen' }}</flux:button>
        </div>
    </form>
</div>
