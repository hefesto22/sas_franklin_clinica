<?php

namespace App\Filament\Resources\CambioEventoResource\Pages;

use App\Filament\Resources\CambioEventoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCambioEventos extends ListRecords
{
    protected static string $resource = CambioEventoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
