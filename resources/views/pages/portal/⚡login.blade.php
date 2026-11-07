<?php

use App\Models\Citizen;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Layout('layouts::portal')]
#[Title('Citizen Portal Login')]
class extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function mount(): void
    {
        if (Auth::guard('citizen')->check()) {
            $this->redirect(route('portal.dashboard'), navigate: true);
        }
    }

    public function login()
    {
        $this->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $citizen = Citizen::where('portal_email', trim($this->email))->whereNotNull('password')->first();

        if (! $citizen || ! Hash::check($this->password, $citizen->password)) {
            $this->addError('email', 'Invalid email or password.');
            return;
        }

        Auth::guard('citizen')->login($citizen, $this->remember);
        return $this->redirect(route('portal.dashboard'), navigate: true);
    }
};
?>

<div class="mx-auto max-w-md">
    <flux:card class="space-y-5">
        <div class="text-center">
            <flux:heading size="xl">Citizen Portal</flux:heading>
            <flux:text class="mt-1">Access your health records and appointments</flux:text>
        </div>

        <form wire:submit="login" class="space-y-4">
            <flux:input wire:model="email" label="Email" type="email" />
            <flux:input wire:model="password" label="Password" type="password" viewable />
            <flux:checkbox wire:model="remember" label="Remember me" />
            @error('email') <flux:text color="red">{{ $message }}</flux:text> @enderror
            <flux:button type="submit" variant="primary" class="w-full">Log in</flux:button>
        </form>

        <flux:separator text="or" />
        <div class="text-center">
            <flux:text>New resident?</flux:text>
            <flux:link :href="route('portal.enroll')" wire:navigate>Create an account</flux:link>
        </div>
    </flux:card>
</div>
