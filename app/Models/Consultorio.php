<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consultorio extends Model
{
    protected $fillable = [
        'nombre',
        'created_by',
        'updated_by',
    ];

    // Relación con usuario creador
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relación con usuario actualizador
    public function actualizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Relación con pacientes por hora
    public function pacientesPorHora(): HasMany
    {
        return $this->hasMany(PacientesPorHora::class);
    }

    // Total de pacientes por día (sumando todos los bloques por hora)
    public function getTotalPacientesPorDiaAttribute(): int
    {
        return $this->pacientesPorHora()->sum('cantidad');
    }

    // app/Models/Consultorio.php

    public function events()
    {
        return $this->hasMany(\App\Models\Event::class);
    }

    // Si deseas acceder con $consultorio->total_pacientes_por_dia
    protected $appends = ['total_pacientes_por_dia'];
}
