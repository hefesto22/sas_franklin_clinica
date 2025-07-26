<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Filament\Resources\ClienteResource;
use App\Models\ClienteImagen;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditCliente extends EditRecord
{
    protected static string $resource = ClienteResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        return $data;
    }

    protected function afterSave(): void
    {
        $imagenes = $this->form->getRawState()['imagenes_upload'] ?? [];

        if (!empty($imagenes)) {
            // Elimina imágenes anteriores si quieres que se reemplacen
            ClienteImagen::where('cliente_id', $this->record->id)->delete();

            // Guarda nuevas imágenes
            foreach ($imagenes as $path) {
                ClienteImagen::create([
                    'cliente_id' => $this->record->id,
                    'path' => $path,
                ]);
            }
        }
    }
}
