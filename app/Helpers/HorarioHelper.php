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

        // Normaliza la fecha a 'Y-m-d' (sin hora)
        $soloFecha = Carbon::parse($fecha)->toDateString();

        // IDs especiales con cupo de 9 por hora
        $consultoriosConCupo = [1, 2];

        if (in_array($consultorioId, $consultoriosConCupo)) {
            // Lógica de cupo por hora (9 pacientes máximo por hora)
            $inicio = Carbon::createFromTimeString('09:00');
            $fin = Carbon::createFromTimeString('17:00');
            $intervalo = 60; // 1 hora

            $horas = collect();
            $periodo = CarbonPeriod::create($inicio, "{$intervalo} minutes", (clone $fin)->subHour()); // hasta 16:00

            foreach ($periodo as $hora) {
                // Construye fecha-hora sin concatenar strings
                $fechaHoraInicio = Carbon::parse($soloFecha)->setTimeFromTimeString($hora->format('H:i:s'));
                $fechaHoraFin    = (clone $fechaHoraInicio)->addHour();

                $eventosEnHora = Event::where('consultorio_id', $consultorioId)
                    ->whereBetween('start_at', [$fechaHoraInicio, $fechaHoraFin])
                    ->count();

                if ($eventosEnHora < 9) {
                    $horas->put(
                        $hora->format('H:i'),
                        $hora->format('h:i A') . ' (cupos: ' . (9 - $eventosEnHora) . ')'
                    );
                }
            }

            return $horas->toArray();
        }

        // Lógica normal para otros consultorios (bloques de 30 minutos, una sola cita)
        $inicio = Carbon::createFromTimeString('08:00');
        $fin = Carbon::createFromTimeString('17:00');
        $intervalo = 30;

        $horas = collect();
        $periodo = CarbonPeriod::create($inicio, "{$intervalo} minutes", $fin);

        foreach ($periodo as $hora) {
            $fechaHora = Carbon::parse($soloFecha)->setTimeFromTimeString($hora->format('H:i:s'));

            $ocupado = Event::where('consultorio_id', $consultorioId)
                ->where('start_at', '<=', $fechaHora)
                ->where('end_at', '>',  $fechaHora)
                ->exists();

            if (!$ocupado) {
                $horas->put($hora->format('H:i'), $hora->format('h:i A'));
            }
        }

        return $horas->toArray();
    }
}
