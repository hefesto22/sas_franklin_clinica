<?php

namespace App\Filament\Resources\ClienteResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;

class ClienteActividadRelationManager extends RelationManager
{
    protected static string $relationship = 'actividades';

    protected static ?string $title = 'Actividades';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('fecha')->required()->label('Fecha'),
            Forms\Components\TextInput::make('actividad')->required()->label('Descripción'),
            Forms\Components\TextInput::make('pago')->numeric()->prefix('L.')->label('Pago')->nullable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
                Tables\Columns\TextColumn::make('fecha')->date()->sortable(),
                Tables\Columns\TextColumn::make('actividad')->limit(30)->searchable(),
                Tables\Columns\TextColumn::make('pago')->money('HNL')->sortable(),
            ])
            ->defaultSort('fecha', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Agregar actividad'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
