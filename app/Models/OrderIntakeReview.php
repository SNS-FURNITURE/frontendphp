<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderIntakeReview extends Model
{
    protected $table = 'order_intake_reviews';

    protected $fillable = [
        'order_intake_id',
        'action',
        'from_status',
        'to_status',
        'comment',
        'actor_user_id',
        'actor_role',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function intake(): BelongsTo
    {
        return $this->belongsTo(OrderIntake::class, 'order_intake_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
