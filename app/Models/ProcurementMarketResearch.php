<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementMarketResearch extends Model
{
    protected $table = 'procurement_market_research';

    public const STATUSES = ['draft', 'submitted', 'manager_approved', 'manager_rejected'];

    protected $fillable = [
        'requisition_id',
        'item_name',
        'category',
        'specifications',
        'quantity',
        'unit_of_measure',
        'supplier_options_json',
        'selected_supplier_name',
        'selected_unit_price',
        'selected_total_price',
        'research_notes',
        'quality_grade',
        'status',
        'conducted_by_user_id',
        'approved_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'selected_unit_price' => 'decimal:2',
            'selected_total_price' => 'decimal:2',
            'supplier_options_json' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function conductedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conducted_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => (int) $this->id,
            'requisition_id' => $this->requisition_id,
            'item_name' => $this->item_name,
            'category' => $this->category,
            'specifications' => $this->specifications,
            'quantity' => $this->quantity,
            'unit_of_measure' => $this->unit_of_measure,
            'supplier_options_json' => $this->supplier_options_json,
            'selected_supplier_name' => $this->selected_supplier_name,
            'selected_unit_price' => $this->selected_unit_price,
            'selected_total_price' => $this->selected_total_price,
            'research_notes' => $this->research_notes,
            'quality_grade' => $this->quality_grade,
            'status' => $this->status,
            'conducted_by_user_id' => $this->conducted_by_user_id,
            'approved_by_user_id' => $this->approved_by_user_id,
            'conducted_by_name' => $this->conductedBy?->full_name,
            'approved_by_name' => $this->approvedBy?->full_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
