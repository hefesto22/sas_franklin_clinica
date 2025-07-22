<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CambioEventoResource\Pages;
use App\Models\CambioEvento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CambioEventoResource extends Resource
{
    protected static ?string $model = CambioEvento::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Citas';
    protected static ?string $modelLabel = 'Cambio de Evento';
    protected static ?string $pluralModelLabel = 'Solicitudes de Cambio';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('estado')
                ->label('Estado de solicitud')
                ->options([
                    'pendiente' => 'Pendiente',
                    'aceptado' => 'Aceptado',
                    'rechazado' => 'Rechazado',
                    'cancelado' => 'Cancelado',
                ])
                ->required(),

            Forms\Components\Textarea::make('motivo_cancelacion')
                ->label('Motivo de cancelación')
                ->visible(fn($get) => $get('estado') === 'cancelado'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('eventoOrigen.cliente.nombre')
                ->label('Paciente Original')
                ->searchable(),

            Tables\Columns\TextColumn::make('eventoDestino.cliente.nombre')
                ->label('Paciente Alternativo')
                ->searchable(),

            Tables\Columns\TextColumn::make('estado')
                ->label('Estado')
                ->badge()
                ->color(fn(string $state): string => match ($state) {
                    'pendiente' => 'warning',
                    'aceptado' => 'success',
                    'rechazado', 'cancelado' => 'danger',
                    default => 'gray',
                }),

            Tables\Columns\TextColumn::make('creador.name')
                ->label('Solicitado por')
                ->searchable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Fecha')
                ->dateTime('d/m/Y h:i A'),
        ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('contactar')
                    ->label('Contactar')
                    ->icon('heroicon-o-phone')
                    ->color('success')
                    ->url(fn(CambioEvento $record) =>
                    filled($record->eventoDestino?->cliente?->telefono)
                        ? 'https://wa.me/504' . preg_replace('/\D/', '', $record->eventoDestino->cliente->telefono)
                        : '#', true)
                    ->openUrlInNewTab()
                    ->visible(fn(CambioEvento $record) => filled($record->eventoDestino?->cliente?->telefono)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCambioEventos::route('/'),
            'create' => Pages\CreateCambioEvento::route('/create'),
            'edit' => Pages\EditCambioEvento::route('/{record}/edit'),
        ];
    }
}
