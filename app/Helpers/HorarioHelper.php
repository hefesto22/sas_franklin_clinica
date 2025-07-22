<?php

namespace App\Helpers;

use App\Models\Event;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class HorarioHelper
{
    public static function getHorasDisponibles($fecha, $consultorioId)
    {
        if (!$fecha || !$consultorioId) return [];

        $inicio = Carbon::createFromTimeString('08:00');
        $fin = Carbon::createFromTimeString('17:00');
        $intervalo = 20;

        $horas = collect();
        $periodo = CarbonPeriod::create($inicio, "{$intervalo} minutes", $fin);

        foreach ($periodo as $hora) {
            $fechaHora = Carbon::parse($fecha . ' ' . $hora->format('H:i:s'));

            $ocupado = Event::where('consultorio_id', $consultorioId)
                ->where('start_at', '<=', $fechaHora)
                ->where('end_at', '>', $fechaHora)
                ->exists();

            if (!$ocupado) {
                $horas->put($hora->format('H:i'), $hora->format('h:i A'));
            }
        }

        return $horas->toArray();
    }
}
