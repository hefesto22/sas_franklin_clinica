<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Models\Event;
use App\Models\Cliente;
use App\Models\Especialidad;
use App\Models\Servicio;
use App\Models\Consultorio;
use App\Helpers\HorarioHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Collection;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Eventos';
    protected static ?string $navigationGroup = 'Gestión de Calendario';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('cliente_id')
                ->label('Cliente')
                ->searchable()
                ->getSearchResultsUsing(fn(string $search) => Cliente::query()
                    ->where('nombre', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%")
                    ->limit(5)
                    ->pluck('nombre', 'id'))
                ->getOptionLabelUsing(fn($value): ?string => Cliente::find($value)?->nombre)
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    $cliente = \App\Models\Cliente::find($state);
                    if ($cliente) {
                        $set('telefono', $cliente->telefono);
                    }
                })
                ->rule(function () {
                    return function (string $attribute, $value, $fail) {
                        $proximoEvento = \App\Models\Event::where('cliente_id', $value)
                            ->whereBetween('start_at', [now(), now()->addDays(25)])
                            ->orderBy('start_at', 'asc')
                            ->first();

                        if ($proximoEvento) {
                            $fail('Este cliente ya tiene una cita agendada el ' . \Carbon\Carbon::parse($proximoEvento->start_at)->format('d/m/Y h:i A'));
                        }
                    };
                }),

            Forms\Components\TextInput::make('telefono')
                ->label('Teléfono')
                ->required()
                ->tel()
                ->disabled()
                ->dehydrated(true), // 👈 esto es lo importante



            Forms\Components\Select::make('especialidades')
                ->label('Especialidades')
                ->multiple()
                ->searchable()
                ->options(Especialidad::pluck('nombre', 'id'))
                ->required()
                ->preload()
                ->reactive()
                ->relationship('especialidades', 'nombre'),

            // Campo de servicios dependiente de especialidades
            Forms\Components\Select::make('servicios')
                ->label('Servicios')
                ->multiple()
                ->searchable()
                ->required()
                ->preload()
                ->options(function (callable $get): Collection {
                    $especialidades = $get('especialidades') ?? [];
                    return Servicio::when(
                        !empty($especialidades),
                        fn($query) =>
                        $query->whereIn('especialidad_id', $especialidades)
                    )->pluck('nombre', 'id');
                })
                ->relationship('servicios', 'nombre')
                ->disabled(fn(callable $get) => empty($get('especialidades')))
                ->reactive(),

            Forms\Components\Select::make('consultorio_id')
                ->label('Consultorio')
                ->options(\App\Models\Consultorio::pluck('nombre', 'id'))
                ->searchable()
                ->required()
                ->reactive(),


            Forms\Components\DatePicker::make('start_date')
                ->label('Fecha')
                ->required()
                ->reactive(),

            Forms\Components\Select::make('start_time')
                ->label('Hora disponible')
                ->required()
                ->options(fn(callable $get) => HorarioHelper::getHorasDisponibles(
                    $get('start_date'),
                    $get('consultorio_id')
                ))
                ->disabled(fn(callable $get) => !$get('start_date') || !$get('consultorio_id'))
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state) {
                        $hora = \Carbon\Carbon::createFromFormat('H:i', $state);
                        $nuevaHora = $hora->addMinutes(20)->format('H:i');
                        $set('end_time', $nuevaHora);
                    }
                }),

            Forms\Components\TimePicker::make('end_time')
                ->label('Hora de finalización')
                ->required()
                ->disabled()
                ->seconds(false)
                ->step(20),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('cliente.nombre')
                ->label('Cliente')
                ->searchable()
                ->sortable(),

            TextColumn::make('especialidades.nombre')
                ->label('Especialidades')
                ->badge()
                ->limitList(3)
                ->sortable(),

            TextColumn::make('servicios.nombre')
                ->label('Servicios')
                ->badge()
                ->limitList(3)
                ->sortable(),


            TextColumn::make('consultorio.nombre')
                ->label('Consultorio')
                ->searchable()
                ->sortable(),

            TextColumn::make('start_at')
                ->label('Inicio')
                ->dateTime()
                ->sortable(),

            TextColumn::make('end_at')
                ->label('Fin')
                ->dateTime()
                ->sortable(),
        ])
            ->filters([])
            ->actions([
                EditAction::make(),

                Action::make('contactar')
                    ->label('Contactar')
                    ->icon('heroicon-o-phone')
                    ->color('success')
                    ->url(fn($record) => 'https://wa.me/504' . preg_replace('/\D/', '', $record->telefono ?? $record->cliente?->telefono))
                    ->openUrlInNewTab()
                    ->visible(fn($record) => filled($record->telefono ?? $record->cliente?->telefono)),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
