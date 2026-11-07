<?php

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Flux\Flux;

new
#[Layout('layouts::app')]
#[Title('Users & Access')]
class extends Component
{
    use WithPagination;

    public string $search = '';

    #[Computed]
    public function users()
    {
        return User::query()
            ->when($this->search, fn ($q) => $q->where(fn ($w) =>
                $w->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
            ->orderBy('name')
            ->paginate(15);
    }

    public function updatedSearch(): void { $this->resetPage(); }

    public function delete(int $id): void
    {
        if ($id === auth()->id()) {
            Flux::toast(text: 'You cannot delete your own account.', variant: 'danger');
            return;
        }
        User::findOrFail($id)->delete();
        Flux::toast(text: 'User removed.', variant: 'success');
    }
};
?>

<div class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-4 p-2">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Users & Access</flux:heading>
            <flux:text class="mt-1">Staff accounts, roles, and module permissions</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('users.create')" wire:navigate>Add User</flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search name or email..." icon="magnifying-glass" class="max-w-md" />

    <flux:table :paginate="$this->users">
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Email</flux:table.column>
            <flux:table.column>Position</flux:table.column>
            <flux:table.column>Role</flux:table.column>
            <flux:table.column>Access</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->users as $u)
                <flux:table.row :key="$u->id">
                    <flux:table.cell variant="strong">{{ $u->name }}</flux:table.cell>
                    <flux:table.cell>{{ $u->email }}</flux:table.cell>
                    <flux:table.cell>{{ $u->position ?: '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $u->role ?: '-' }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($u->isAdmin())
                            <flux:badge color="purple" size="sm">Administrator</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">{{ count($u->permissions ?? []) }} modules</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" icon="pencil" :href="route('users.edit', $u)" wire:navigate />
                        @if ($u->id !== auth()->id())
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $u->id }})" wire:confirm="Remove this user?" />
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center">No users.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>
