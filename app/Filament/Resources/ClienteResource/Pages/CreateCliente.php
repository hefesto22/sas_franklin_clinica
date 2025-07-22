<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Filament\Resources\ClienteResource;
use App\Models\Cliente;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCliente extends CreateRecord
{
    protected static string $resource = ClienteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar')
                ->action(function () {
                    $data = $this->form->getState();

                    $data['created_by'] = Auth::id();
                    $data['updated_by'] = Auth::id();

                    $this->record = Cliente::create($data);

                    Notification::make()
                        ->title('Cliente creado correctamente')
                        ->success()
                        ->send();

                    $this->redirect(ClienteResource::getUrl('index'));
                }),

            Action::make('save_create_another')
                ->label('Guardar y crear otro')
                ->action(function () {
                    $data = $this->form->getState();

                    $data['created_by'] = Auth::id();
                    $data['updated_by'] = Auth::id();

                    Cliente::create($data);

                    Notification::make()
                        ->title('Cliente creado, puedes registrar otro')
                        ->success()
                        ->send();

                    $this->fillForm();
                }),

            Action::make('cancel')
                ->label('Cancelar')
                ->color('gray')
                ->url(ClienteResource::getUrl('index')),
        ];
    }
}
