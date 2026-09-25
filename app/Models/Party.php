<?php

namespace App\Models;

use App\Support\CustomerIdentity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'customer_id');
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    protected static function booted(): void
    {
        static::saving(function (Party $party): void {
            if ($party->party_type !== 'customer') {
                $party->name_key = null;
                $party->address_key = null;

                return;
            }

            $keys = CustomerIdentity::keys(
                (string) ($party->name ?? ''),
                $party->address,
            );

            $party->name_key = $keys['name_key'];
            $party->address_key = $keys['address_key'];
        });
    }
}
