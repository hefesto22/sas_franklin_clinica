<?php

namespace App\Filament\Resources\EspecialidadResource\Pages;

use App\Filament\Resources\EspecialidadResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateEspecialidad extends CreateRecord
{
    protected static string $resource = EspecialidadResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        return $data;
    }
}
