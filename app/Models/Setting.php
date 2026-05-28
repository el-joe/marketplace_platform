<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    use HasUuids;

    // Settings table has only updated_at, no created_at
    const CREATED_AT = null;

    protected $fillable = [
        'key',
        'value',
        'category',
        'description',
        'is_encrypted',
        'is_public',
        'updated_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    /**
     * Decode the JSON value column to a native PHP type.
     */
    public function getTypedValue(): mixed
    {
        if ($this->is_encrypted) {
            return null; // never expose encrypted values
        }
        return json_decode($this->value, true);
    }

    /**
     * Returns: 'bool' | 'number' | 'array' | 'string'
     */
    public function getValueType(): string
    {
        $v = json_decode($this->value);
        if (is_bool($v))
            return 'bool';
        if (is_int($v) || is_float($v))
            return 'number';
        if (is_array($v) || is_object($v))
            return 'array';
        return 'string';
    }

    public function updatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by_admin_id');
    }
}
