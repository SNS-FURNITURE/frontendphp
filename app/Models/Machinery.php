<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Machinery extends Model
{
    protected $table = 'machinery';

    public $timestamps = false;

    public const STATUSES = ['operational', 'under_maintenance', 'idle'];

    protected $fillable = [
        'machine_code',
        'name',
        'category',
        'workshop_location',
        'existing_qty',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'existing_qty' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => (int) $this->id,
            'machine_code' => $this->machine_code,
            'name' => $this->name,
            'category' => $this->category,
            'workshop_location' => $this->workshop_location,
            'existing_qty' => (int) $this->existing_qty,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
