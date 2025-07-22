<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['start_at'] = \Carbon\Carbon::parse($data['start_date'] . ' ' . $data['start_time']);
        $data['end_at'] = isset($data['end_time'])
            ? Carbon::parse($data['start_date'] . ' ' . $data['end_time'])
            : Carbon::parse($data['start_date'] . ' ' . $data['start_time'])->addMinutes(20);

        $data['created_by'] = Auth::user()->id;
        $data['updated_by'] = Auth::user()->id;

        // 👇 Asegúrate que esto exista si no lo tienes aún
        $data['telefono'] = $data['telefono'] ?? null;

        return $data;
    }
    protected function afterCreate(): void
    {
        $this->record->especialidades()->sync($this->data['especialidades'] ?? []);
        $this->record->servicios()->sync($this->data['servicios'] ?? []);
    }
    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'start_date' => request('start_date'),
        ]);
    }
}
