<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuplicateGroupItem extends Model
{
    protected $fillable = [
        'duplicate_group_id',
        'client_id',
        'is_master',
    ];

    protected $casts = [
        'is_master' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(DuplicateGroup::class, 'duplicate_group_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
