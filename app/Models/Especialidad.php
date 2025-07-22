<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Especialidad extends Model
{
    use HasFactory;
    protected $table = 'especialidades';

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
        'created_by',
        'updated_by',   
    ];

    // Relación: una especialidad tiene muchos servicios
    public function servicios()
    {
        return $this->hasMany(Servicio::class);
    }

    // Usuario que creó la especialidad
    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Usuario que actualizó la especialidad
    public function actualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
