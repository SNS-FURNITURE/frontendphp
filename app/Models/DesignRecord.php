<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DesignRecord extends Model
{
    public function getTable(): string
    {
        return Schema::hasTable('design_records') ? 'design_records' : 'designs';
    }

    protected $fillable = [
        'title',
        'kind',
        'status',
        'designer_id',
        'deal_id',
        'design_source',
        'notes',
        'file_url',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function designer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'designer_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        $row = $this->attributesToArray();
        $row['id'] = (int) $this->id;
        $row['designer_name'] = $this->designer?->full_name;
        $row['deal_title'] = null;

        if ($this->deal_id && Schema::hasTable('deals')) {
            $deal = DB::table('deals')->where('id', $this->deal_id)->first();
            $row['deal_title'] = $deal->title ?? null;
        }

        return $row;
    }
}
