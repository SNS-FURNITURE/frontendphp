<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model
{
    protected $table = 'workspaces';

    public const UPDATED_AT = null;

    protected $fillable = ['name', 'visibility'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function boards(): HasMany
    {
        return $this->hasMany(Board::class, 'workspace_id');
    }
}
