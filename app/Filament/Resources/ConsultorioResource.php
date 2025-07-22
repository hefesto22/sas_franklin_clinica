<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsultorioResource\Pages;
use App\Models\Consultorio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ConsultorioResource extends Resource
{
    protected static ?string $model = Consultorio::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static ?string $navigationLabel = 'Consultorios';
    protected static ?string $pluralModelLabel = 'Consultorios';
    protected static ?string $modelLabel = 'Consultorio';
    protected static ?string $slug = 'consultorios';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->label('Nombre del consultorio')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('pacientes_por_hora')
                    ->label('Cantidad de pacientes por hora')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(50)
                    ->required()
                    ->default(6),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_pacientes_por_dia')
                    ->label('Pacientes por día')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('creador.name')
                    ->label('Creado por')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsultorios::route('/'),
            'create' => Pages\CreateConsultorio::route('/create'),
            'edit' => Pages\EditConsultorio::route('/{record}/edit'),
        ];
    }
}
