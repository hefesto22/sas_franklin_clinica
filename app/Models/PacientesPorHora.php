<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PacientesPorHora extends Model
{
    protected $table = 'pacientes_por_hora'; // 👈 Nombre correcto de la tabla

    protected $fillable = [
        'consultorio_id',
        'hora',
        'cantidad',
        'created_by',
        'updated_by',
    ];

    public function consultorio(): BelongsTo
    {
        return $this->belongsTo(Consultorio::class);
    }
}
