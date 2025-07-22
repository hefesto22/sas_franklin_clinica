<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'estado',
        // 'descripcion', // ← si decides agregar descripción al rol
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    /**
     * Relación: Usuarios que tienen este rol.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
