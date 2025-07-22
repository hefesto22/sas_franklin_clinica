<?php

namespace App\Observers;

use App\Models\CambioEvento;
use App\Models\HistorialPaciente;
use Illuminate\Support\Facades\Auth;

class CambioEventoObserver
{
    public function updated(CambioEvento $cambio)
    {
        if ($cambio->isDirty('estado')) {
            // Aceptado: hacer el intercambio
            if ($cambio->estado === 'aceptado') {
                $eventoA = $cambio->eventoOrigen;
                $eventoB = $cambio->eventoDestino;

                // Intercambio de fechas
                $tempStart = $eventoA->start_at;
                $tempEnd = $eventoA->end_at;

                $eventoA->start_at = $eventoB->start_at;
                $eventoA->end_at = $eventoB->end_at;
                $eventoA->estado = 'Reagendado';
                $eventoA->save();

                $eventoB->start_at = $tempStart;
                $eventoB->end_at = $tempEnd;
                $eventoB->estado = 'Confirmado';
                $eventoB->save();

                // Registrar historial del paciente original
                HistorialPaciente::create([
                    'paciente_id' => $eventoB->cliente_id,
                    'evento_id' => $eventoB->id,
                    'accion' => 'Reagendado',
                    'descripcion' => 'La cita fue reagendada mediante intercambio con el paciente: ' . $eventoA->cliente->nombre,
                    'created_by' => Auth::user()?->id,
                ]);

                // Eliminar la solicitud
                $cambio->delete();
            }

            // Rechazado: ambos vuelven a estado 'Pendiente'
            elseif ($cambio->estado === 'rechazado') {
                $eventoA = $cambio->eventoOrigen;
                $eventoB = $cambio->eventoDestino;

                $eventoA->estado = 'Pendiente';
                $eventoA->save();

                $eventoB->estado = 'Pendiente';
                $eventoB->save();

                // Eliminar la solicitud
                $cambio->delete();
            }
        }
    }
}
