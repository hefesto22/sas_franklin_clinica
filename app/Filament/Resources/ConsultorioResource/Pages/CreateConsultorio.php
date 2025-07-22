<?php

// app/Filament/Resources/ConsultorioResource/Pages/CreateConsultorio.php

namespace App\Filament\Resources\ConsultorioResource\Pages;

use App\Filament\Resources\ConsultorioResource;
use App\Models\Consultorio;
use App\Models\PacientesPorHora;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class CreateConsultorio extends CreateRecord
{
    protected static string $resource = ConsultorioResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        return $data;
    }

    protected function afterCreate(): void
    {
        $consultorio = $this->record;
        $cantidad = $this->data['pacientes_por_hora'];

        $horas = collect(range(8, 17))->map(function ($hora) {
            return Carbon::createFromTime($hora)->format('H:i:s');
        });

        foreach ($horas as $hora) {
            PacientesPorHora::create([
                'consultorio_id' => $consultorio->id,
                'hora' => $hora,
                'cantidad' => $cantidad,
                'created_by' => Auth::id(),
            ]);
        }
    }
}
