<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EspecialidadResource\Pages;
use App\Filament\Resources\EspecialidadResource\Pages\ListEspecialidads;
use App\Filament\Resources\EspecialidadResource\Pages\CreateEspecialidad;
use App\Filament\Resources\EspecialidadResource\Pages\EditEspecialidad;
use App\Filament\Resources\EspecialidadResource\Pages\ViewEspecialidad;
use App\Models\Especialidad;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;

class EspecialidadResource extends Resource
{
    protected static ?string $model = Especialidad::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'Especialidades';
    protected static ?string $pluralModelLabel = 'Especialidades';
    protected static ?string $navigationGroup = 'Especialidades y Servicios';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('descripcion')
                    ->rows(3)
                    ->maxLength(65535),
                Forms\Components\Toggle::make('estado')
                    ->label('Activo')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('descripcion')->limit(40),
                Tables\Columns\IconColumn::make('estado')
                    ->boolean()
                    ->label('Activo'),
                Tables\Columns\TextColumn::make('creador.name')->label('Creado por'),
                Tables\Columns\TextColumn::make('actualizador.name')->label('Actualizado por'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Fecha de creación'),
                Tables\Columns\TextColumn::make('updated_at')->since()->label('Última actualización'),

            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),

                ]),
            ])

            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEspecialidads::route('/'),
            'create' => CreateEspecialidad::route('/create'),
            'edit' => EditEspecialidad::route('/{record}/edit'),
            'view' => ViewEspecialidad::route('/{record}'),
        ];
    }
}
