<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ClienteImagenRelationManager extends RelationManager
{
    protected static string $relationship = 'imagenes';

    protected static ?string $title = 'Imágenes';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('path')
                ->label('Imagen')
                ->image()
                ->directory('clientes')
                ->visibility('public')
                ->preserveFilenames()
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->label('Imagen')
                    ->disk('public')
                    ->height('80px')
                    ->width('80px')
                    ->extraImgAttributes([
                        'class' => 'rounded-xl object-cover shadow-md',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Agregar imagen'),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-magnifying-glass-plus')
                    ->color('gray')
                    ->modalHeading('Vista previa')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(function ($record) {
                        $src = Storage::url($record->path);
                        return new HtmlString(
                            '<div class="p-2">
                            <img src="' . $src . '" alt="Vista previa" class="max-w-full h-auto rounded-xl shadow-lg mx-auto">
                        </div>'
                        );
                    }),
                Tables\Actions\DeleteAction::make()->label('Borrar')->color('danger'),
            ]);
    }
}
