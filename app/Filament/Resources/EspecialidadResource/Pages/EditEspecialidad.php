<?php

namespace App\Filament\Resources\EspecialidadResource\Pages;

use App\Filament\Resources\EspecialidadResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditEspecialidad extends EditRecord
{
    protected static string $resource = EspecialidadResource::class;

protected function mutateFormDataBeforeSave(array $data): array
{
    $data['updated_by'] = Auth::id();
    return $data;
}
}
