<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServicioResource\Pages;
use App\Models\Servicio;
use App\Models\Especialidad;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;

class ServicioResource extends Resource
{
    protected static ?string $model = Servicio::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationGroup = 'Especialidades y Servicios';
    protected static ?string $navigationLabel = 'Servicios';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('especialidad_id')
                    ->label('Especialidad')
                    ->relationship('especialidad', 'nombre', fn($query) => $query->where('estado', true)->orderBy('nombre'))
                    ->searchable()
                    ->required(),

                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('descripcion')
                    ->rows(3)
                    ->maxLength(65535),

                Forms\Components\TextInput::make('precio')
                    ->numeric(),

                Forms\Components\TextInput::make('precio_promocional')
                    ->numeric(),

                Forms\Components\Toggle::make('estado')
                    ->label('Activo')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('especialidad.nombre')->label('Especialidad'),
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('precio')->money('HNL'),
                Tables\Columns\IconColumn::make('estado')->boolean()->label('Activo'),
                Tables\Columns\TextColumn::make('created_at')->since()->label('Creado'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('especialidad_id')
                    ->label('Especialidad')
                    ->relationship('especialidad', 'nombre'),
                Tables\Filters\TernaryFilter::make('estado')->label('Activo'),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->button()
                    ->label('Acciones'),
            ])
            ->defaultSort('created_at', 'desc');
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServicios::route('/'),
            'create' => Pages\CreateServicio::route('/create'),
            'edit' => Pages\EditServicio::route('/{record}/edit'),
        ];
    }
}
