<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Party extends Model
{
    protected $table = 'parties';

    protected $fillable = [
        'party_type',
        'name',
        'company_name',
        'phone',
        'email',
        'address',
        'notes',
        'approval_status',
        'created_by',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
