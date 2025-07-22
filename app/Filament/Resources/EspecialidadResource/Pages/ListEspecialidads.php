<?php

namespace App\Filament\Resources\EspecialidadResource\Pages;

use App\Filament\Resources\EspecialidadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEspecialidads extends ListRecords
{
    protected static string $resource = EspecialidadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
