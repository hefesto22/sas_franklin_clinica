<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialPaciente extends Model
{
    protected $table = 'historial_paciente';

    protected $fillable = [
        'paciente_id',
        'evento_id',
        'created_by',
        'accion',
        'descripcion',
    ];

    /**
     * Relación con el paciente (cliente).
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'paciente_id');
    }

    /**
     * Relación con el evento (cita).
     */
    public function evento(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Relación con el usuario que creó el historial.
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
