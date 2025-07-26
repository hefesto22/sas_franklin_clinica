<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'dni',
        'telefono',
        'edad',
        'fecha_nacimiento',
        'genero',
        'direccion',
        'ocupacion',
        'motivo_consulta',
        'antecedentes',
        'alergias',
        'estado',
        'created_by',
        'updated_by',
    ];

    // Relación con el usuario que creó el cliente
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relación con el usuario que actualizó por última vez
    public function actualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Relación con eventos (citas agendadas)
    public function eventos(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    // Relación con imágenes
    public function imagenes(): HasMany
    {
        return $this->hasMany(ClienteImagen::class);
    }

    // Relación con actividades
    public function actividades(): HasMany
    {
        return $this->hasMany(ClienteActividad::class);
    }
}
