<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicine extends Model
{
    protected $fillable = ['name', 'generic_name', 'form', 'strength', 'unit', 'reorder_level', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function currentStock(): int
    {
        $in  = (int) $this->movements()->where('type', 'in')->sum('quantity');
        $out = (int) $this->movements()->where('type', 'out')->sum('quantity');
        $adj = (int) $this->movements()->where('type', 'adjustment')->sum('quantity');
        return $in - $out + $adj;
    }

    public function needsReorder(): bool
    {
        return $this->reorder_level > 0 && $this->currentStock() <= $this->reorder_level;
    }

    public function label(): string
    {
        return trim("{$this->name} {$this->strength} {$this->form}");
    }
}
