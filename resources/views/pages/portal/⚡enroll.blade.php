<?php

use App\Models\Barangay;
use App\Models\Citizen;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;

new
#[Layout('layouts::portal')]
#[Title('Register - Citizen Portal')]
class extends Component
{
    public string $last_name = '';
    public string $first_name = '';
    public string $middle_name = '';
    public string $suffix = '';
    public string $sex = '';
    public string $birth_date = '';
    public $barangay_id = '';
    public string $purok = '';
    public string $contact_no = '';
    public string $portal_email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(): void
    {
        if (Auth::guard('citizen')->check()) {
            $this->redirect(route('portal.dashboard'), navigate: true);
        }
    }

    #[Computed]
    public function barangayList()
    {
        return Barangay::orderBy('name')->get();
    }

    public function register()
    {
        $data = $this->validate([
            'last_name'    => 'required|string|max:255',
            'first_name'   => 'required|string|max:255',
            'middle_name'  => 'nullable|string|max:255',
            'suffix'       => 'nullable|string|max:50',
            'sex'          => 'nullable|in:male,female',
            'birth_date'   => 'required|date',
            'barangay_id'  => 'required|exists:barangays,id',
            'purok'        => 'nullable|string|max:255',
            'contact_no'   => 'nullable|string|max:50',
            'portal_email' => 'required|email|unique:citizens,portal_email',
            'password'     => 'required|string|min:8|confirmed',
        ]);

        $citizen = Citizen::create([
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?: null,
            'suffix' => $data['suffix'] ?: null,
            'sex' => $data['sex'] ?: null,
            'birth_date' => $data['birth_date'],
            'barangay_id' => $data['barangay_id'],
            'purok' => $data['purok'] ?: null,
            'contact_no' => $data['contact_no'] ?: null,
            'portal_email' => $data['portal_email'],
            'password' => $data['password'],
            'portal_enrolled_at' => now(),
            'verification_status' => 'pending',
            'self_registered' => true,
        ]);

        Auth::guard('citizen')->login($citizen);
        return $this->redirect(route('portal.dashboard'), navigate: true);
    }
};
?>

<div class="mx-auto max-w-lg">
    <flux:card class="space-y-5">
        <div>
            <flux:heading size="xl">Create your account</flux:heading>
            <flux:text class="mt-1">Register with your own details. After signing in, submit a valid government ID for a barangay health worker to verify.</flux:text>
        </div>

        <form wire:submit="register" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="last_name" label="Last name" required />
                <flux:input wire:model="first_name" label="First name" required />
                <flux:input wire:model="middle_name" label="Middle name" />
                <flux:input wire:model="suffix" label="Suffix" placeholder="Jr., III" />
                <flux:select wire:model="sex" label="Sex" placeholder="Select...">
                    <flux:select.option value="male">Male</flux:select.option>
                    <flux:select.option value="female">Female</flux:select.option>
                </flux:select>
                <flux:input wire:model="birth_date" label="Birth date" type="date" required />
                <flux:select wire:model="barangay_id" label="Barangay" placeholder="Select barangay..." required>
                    @foreach ($this->barangayList as $b)
                        <flux:select.option :value="$b->id">{{ $b->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="purok" label="Purok / Sitio" />
                <flux:input wire:model="contact_no" label="Contact no" placeholder="09xx" />
                <flux:input wire:model="portal_email" label="Email (your login)" type="email" required />
                <flux:input wire:model="password" label="Password" type="password" viewable />
                <flux:input wire:model="password_confirmation" label="Confirm password" type="password" viewable />
            </div>
            <flux:button type="submit" variant="primary" class="w-full">Register</flux:button>
        </form>

        <div class="text-center">
            <flux:text>Already have an account?</flux:text>
            <flux:link :href="route('portal.login')" wire:navigate>Log in</flux:link>
        </div>
    </flux:card>
</div>
