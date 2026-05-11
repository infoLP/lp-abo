<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomFieldDefinition extends Model
{
    protected $fillable = [
        'name', 'slug', 'label', 'type', 'options', 'group',
        'required', 'is_active', 'sort_order', 'description', 'default_value',
    ];

    protected function casts(): array
    {
        return [
            'options'   => 'array',
            'required'  => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public static function getActive()
    {
        return static::where('is_active', true)
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get();
    }

    public static function getGrouped()
    {
        return static::getActive()->groupBy('group');
    }
}
