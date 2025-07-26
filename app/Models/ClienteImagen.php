<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClienteImagen extends Model
{
    use HasFactory;

    protected $table = 'cliente_imagenes';

    protected $fillable = [
        'cliente_id',
        'path',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
