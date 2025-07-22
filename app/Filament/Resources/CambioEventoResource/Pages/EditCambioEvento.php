<?php

namespace App\Filament\Resources\CambioEventoResource\Pages;

use App\Filament\Resources\CambioEventoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCambioEvento extends EditRecord
{
    protected static string $resource = CambioEventoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // No necesitas override de beforeSave o afterSave porque
    // el observer maneja todo automáticamente al actualizar estado a 'aceptado'.
}
