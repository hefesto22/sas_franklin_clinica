<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Servicio extends Model
{
    use HasFactory;

    protected $fillable = [
        'especialidad_id',
        'nombre',
        'descripcion',
        'precio',
        'precio_promocional',
        'estado',
        'created_by',
        'updated_by',
    ];

    // Relación: un servicio pertenece a una especialidad
    public function especialidad()
    {
        return $this->belongsTo(Especialidad::class);
    }

    // Usuario que creó el servicio
    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Usuario que actualizó el servicio
    public function actualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
