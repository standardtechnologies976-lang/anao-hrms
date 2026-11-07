<?php

use App\Models\Barangay;
use App\Models\User;
use App\Support\Features;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Flux\Flux;

new
#[Layout('layouts::app')]
#[Title('User')]
class extends Component
{
    public ?int $editingId = null;
    public bool $isSelf = false;

    public string $name = '';
    public string $email = '';
    public string $position = '';
    public string $role = '';
    public $barangay_id = '';
    public bool $is_admin = false;
    public array $permissions = [];
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(?User $user = null): void
    {
        if ($user && $user->exists) {
            $this->editingId = $user->id;
            $this->isSelf = $user->id === auth()->id();
            $this->name = $user->name;
            $this->email = $user->email;
            $this->position = $user->position ?? '';
            $this->role = $user->role ?? '';
            $this->barangay_id = $user->barangay_id ?? '';
            $this->is_admin = (bool) $user->is_admin;
            $this->permissions = $user->permissions ?? [];
        }
    }

    public function barangays()
    {
        return Barangay::orderBy('name')->get();
    }

    public function save()
    {
        $data = $this->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($this->editingId)],
            'position' => 'nullable|string|max:255',
            'role'     => 'nullable|string|max:100',
            'barangay_id' => 'nullable|exists:barangays,id',
            'is_admin' => 'boolean',
            'permissions' => 'array',
            'permissions.*' => 'string',
            'password' => $this->editingId ? 'nullable|string|min:8|confirmed' : 'required|string|min:8|confirmed',
        ]);

        // Guard: cannot remove your own admin rights (avoid lockout)
        if ($this->isSelf && ! $this->is_admin) {
            $this->addError('is_admin', 'You cannot remove your own administrator access.');
            return;
        }

        $payload = [
            'name'        => $data['name'],
            'email'       => $data['email'],
            'position'    => $data['position'] ?: null,
            'role'        => $data['role'] ?: null,
            'barangay_id' => $data['barangay_id'] ?: null,
            'is_admin'    => $data['is_admin'],
            'permissions' => $data['is_admin'] ? [] : array_values($this->permissions),
        ];
        if (! empty($data['password'])) {
            $payload['password'] = bcrypt($data['password']);
        }

        User::updateOrCreate(['id' => $this->editingId], $payload);

        Flux::toast(text: $this->editingId ? 'User updated.' : 'User created.', variant: 'success');
        return $this->redirect(route('users.index'), navigate: true);
    }
};
?>

<div class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-2">
    <div class="flex items-center gap-3">
        <flux:button icon="arrow-left" variant="ghost" :href="route('users.index')" wire:navigate />
        <flux:heading size="xl">{{ $editingId ? 'Edit User' : 'Add User' }}</flux:heading>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:card class="space-y-4">
            <flux:heading size="lg">Account</flux:heading>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="name" label="Full name" required />
                <flux:input wire:model="email" label="Email" type="email" required />
                <flux:input wire:model="position" label="Position" placeholder="Nurse, Physician..." />
                <flux:input wire:model="role" label="Role" placeholder="nurse, physician, receptionist..." />
                <flux:select wire:model="barangay_id" label="Assigned barangay (for BHW)" placeholder="None">
                    <flux:select.option value="">None</flux:select.option>
                    @foreach ($this->barangays() as $b)
                        <flux:select.option :value="$b->id">{{ $b->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="password" label="{{ $editingId ? 'New password (leave blank to keep)' : 'Password' }}" type="password" viewable />
                <flux:input wire:model="password_confirmation" label="Confirm password" type="password" viewable />
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <flux:heading size="lg">Access</flux:heading>
            <flux:checkbox wire:model.live="is_admin" label="Administrator (full access to all modules)" />
            @error('is_admin') <flux:text color="red">{{ $message }}</flux:text> @enderror

            @unless ($is_admin)
                <flux:separator text="Module permissions" />
                @foreach (\App\Support\Features::grouped() as $group => $features)
                    <div>
                        <flux:text class="mb-2 font-medium">{{ $group }}</flux:text>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @foreach ($features as $key => $meta)
                                <flux:checkbox wire:model="permissions" value="{{ $key }}" label="{{ $meta['label'] }}" />
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endunless
        </flux:card>

        <div class="flex justify-end gap-2">
            <flux:button variant="ghost" :href="route('users.index')" wire:navigate>Cancel</flux:button>
            <flux:button type="submit" variant="primary">{{ $editingId ? 'Update User' : 'Create User' }}</flux:button>
        </div>
    </form>
</div>
