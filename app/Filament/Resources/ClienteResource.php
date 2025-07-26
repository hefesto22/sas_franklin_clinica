<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Filament\Resources\ClienteResource\RelationManagers\ClienteActividadRelationManager;
use App\Filament\Resources\ClienteResource\RelationManagers\ClienteImagenRelationManager;
use App\Models\Cliente;
use App\Models\HistorialPaciente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\Action;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Gestión de Clientes';
    protected static ?string $label = 'Cliente';
    protected static ?string $pluralLabel = 'Clientes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nombre')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('dni')
                ->required()
                ->maxLength(50)
                ->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('edad')
                ->numeric()
                ->minValue(0)
                ->maxValue(120)
                ->nullable(),

            Forms\Components\DatePicker::make('fecha_nacimiento')
                ->label('Fecha de nacimiento')
                ->nullable(),

            Forms\Components\Select::make('genero')
                ->options([
                    'masculino' => 'Masculino',
                    'femenino' => 'Femenino',
                    'otro' => 'Otro',
                ])
                ->nullable(),

            Forms\Components\TextInput::make('telefono')
                ->maxLength(30)
                ->nullable(),

            Forms\Components\TextInput::make('direccion')
                ->maxLength(255)
                ->nullable(),

            Forms\Components\TextInput::make('ocupacion')
                ->maxLength(255)
                ->nullable(),

            Forms\Components\Textarea::make('motivo_consulta')
                ->label('Motivo de consulta')
                ->rows(2)
                ->nullable(),

            Forms\Components\Textarea::make('antecedentes')
                ->rows(2)
                ->nullable(),

            Forms\Components\Textarea::make('alergias')
                ->rows(2)
                ->nullable(),

            Forms\Components\Select::make('estado')
                ->required()
                ->options([
                    'activo' => 'Activo',
                    'inactivo' => 'Inactivo',
                ])
                ->default('activo'),

            Forms\Components\FileUpload::make('imagenes_upload')
                ->label('Imágenes del cliente')
                ->multiple()
                ->image()
                ->directory('clientes')
                ->preserveFilenames()
                ->visibility('public')
                ->maxFiles(7)
                ->columnSpanFull()
                ->helperText('Puedes subir hasta 7 imágenes (opcional)')
                ->dehydrated(false), // <- evita que se guarde en la tabla principal


        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('dni')->searchable(),
                Tables\Columns\TextColumn::make('telefono')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->color(fn(string $state) => $state === 'activo' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('createdBy.name')->label('Creado por')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Registrado')->date()->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    Action::make('verHistorial')
                        ->label('Ver historial')
                        ->icon('heroicon-o-clock')
                        ->modalHeading('Historial del paciente')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Cerrar')
                        ->modalContent(fn(Cliente $record) => view('filament.modals.historial-cliente', [
                            'historial' => HistorialPaciente::where('paciente_id', $record->id)
                                ->latest()
                                ->take(10)
                                ->get(),
                        ])),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            ClienteActividadRelationManager::class,
            ClienteImagenRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}
