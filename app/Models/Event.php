<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Event extends Model
{
    use HasFactory;

    protected $table = 'events';

    protected $fillable = [
        'cliente_id',
        'especialidad_id',
        'servicio_id',
        'consultorio_id',
        'telefono',          // 👈 Asegúrate que esto esté aquí
        'estado',
        'start_at',
        'end_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [

    ];

    // Relaciones
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function consultorio(): BelongsTo
    {
        return $this->belongsTo(Consultorio::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

        public function especialidades(): BelongsToMany
    {
        return $this->belongsToMany(Especialidad::class, 'event_especialidad');
    }

    public function servicios(): BelongsToMany
    {
        return $this->belongsToMany(Servicio::class, 'event_servicio');
    }
    
}
