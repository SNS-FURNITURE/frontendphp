<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteInstallationJob extends Model
{
    protected $table = 'site_installation_jobs';

    public const STATUSES = ['not_assigned', 'assigned', 'in_progress', 'finished', 'cancelled'];

    protected $fillable = [
        'order_id',
        'delivery_id',
        'customer_name',
        'customer_phone',
        'site_address',
        'status',
        'assigned_laborer_id',
        'scheduled_start_date',
        'estimated_duration_days',
        'actual_completion_date',
        'agreed_total_payment',
        'advance_payment_amount',
        'balance_payment_amount',
        'funding_request_id',
        'setup_notes',
        'client_sign_off_notes',
    ];

    protected function casts(): array
    {
        return [
            'estimated_duration_days' => 'integer',
            'agreed_total_payment' => 'decimal:2',
            'advance_payment_amount' => 'decimal:2',
            'balance_payment_amount' => 'decimal:2',
            'scheduled_start_date' => 'date',
            'actual_completion_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function laborer(): BelongsTo
    {
        return $this->belongsTo(ExternalLaborer::class, 'assigned_laborer_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => (int) $this->id,
            'order_id' => $this->order_id,
            'delivery_id' => $this->delivery_id,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'site_address' => $this->site_address,
            'status' => $this->status,
            'assigned_laborer_id' => $this->assigned_laborer_id,
            'scheduled_start_date' => $this->scheduled_start_date,
            'estimated_duration_days' => $this->estimated_duration_days,
            'actual_completion_date' => $this->actual_completion_date,
            'agreed_total_payment' => $this->agreed_total_payment,
            'advance_payment_amount' => $this->advance_payment_amount,
            'balance_payment_amount' => $this->balance_payment_amount,
            'funding_request_id' => $this->funding_request_id,
            'setup_notes' => $this->setup_notes,
            'client_sign_off_notes' => $this->client_sign_off_notes,
            'laborer_name' => $this->laborer?->full_name,
            'laborer_phone' => $this->laborer?->phone,
            'laborer_daily_rate' => $this->laborer?->daily_rate,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
