<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClienteActividad extends Model
{
    use HasFactory;

    protected $table = 'cliente_actividades';

    protected $fillable = [
        'cliente_id',
        'fecha',
        'actividad',
        'pago',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
