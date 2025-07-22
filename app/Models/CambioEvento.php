<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CambioEvento extends Model
{
    protected $table = 'cambio_eventos';

    protected $fillable = [
        'evento_id_origen',
        'evento_id_destino',
        'created_by',
        'approved_by',
        'estado',
        'motivo_cancelacion',
        'aprobado_en',
        'rechazado_en',
        'cancelado_en',
    ];

    protected $casts = [
        'aprobado_en' => 'datetime',
        'rechazado_en' => 'datetime',
        'cancelado_en' => 'datetime',
    ];

    // Relaciones
    public function eventoOrigen(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'evento_id_origen');
    }

    public function eventoDestino(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'evento_id_destino');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes útiles
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeAceptados($query)
    {
        return $query->where('estado', 'aceptado');
    }

    public function scopeRechazados($query)
    {
        return $query->where('estado', 'rechazado');
    }

    public function scopeCancelados($query)
    {
        return $query->where('estado', 'cancelado');
    }
}
