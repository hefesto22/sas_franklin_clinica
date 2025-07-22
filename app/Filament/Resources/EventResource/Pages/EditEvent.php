<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['start_at'] = \Carbon\Carbon::parse($data['start_date'] . ' ' . $data['start_time']);
        $data['end_at'] = \Carbon\Carbon::parse($data['start_date'] . ' ' . $data['end_time']);
        $data['updated_by'] = Auth::user()->id;

        if (isset($data['telefono'])) {
            $data['telefono'] = $data['telefono'];
        }

        return $data;
    }


    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['start_date'] = Carbon::parse($data['start_at'])->format('Y-m-d');
        $data['start_time'] = Carbon::parse($data['start_at'])->format('H:i');
        $data['end_time'] = Carbon::parse($data['end_at'])->format('H:i');

        // ✅ Asegurarse de incluir el teléfono para edición
        $data['telefono'] = $data['telefono'] ?? null;

        return $data;
    }
    protected function afterSave(): void
    {
        $this->record->especialidades()->sync($this->data['especialidades'] ?? []);
        $this->record->servicios()->sync($this->data['servicios'] ?? []);
    }
}
