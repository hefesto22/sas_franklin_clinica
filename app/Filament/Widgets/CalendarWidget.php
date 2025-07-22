<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Filament\Resources\EventResource;
use App\Models\Event;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms;
use Filament\Actions\Action;
use Illuminate\Support\Carbon;
use App\Models\CambioEvento;
use Illuminate\Support\Facades\Auth;
use App\Helpers\HorarioHelper;
use Saade\FilamentFullCalendar\Actions;
use Saade\FilamentFullCalendar\Actions\CreateAction;

class CalendarWidget extends FullCalendarWidget
{
    public Model | string | null $model = Event::class;

    protected function headerActions(): array
    {
        return [
            \Filament\Actions\Action::make('crear')
                ->label('Crear evento')
                ->url(route('filament.admin.resources.events.create'))
                ->icon('heroicon-o-plus')
        ];
    }



    public function fetchEvents(array $fetchInfo): array
    {
        return Event::with(['cliente', 'consultorio']) // eager load relaciones
            ->where('start_at', '>=', $fetchInfo['start'])
            ->where('end_at', '<=', $fetchInfo['end'])
            ->get()
            ->map(
                fn(Event $event) => [
                    'id'    => $event->id,
                    'title' => $event->cliente->nombre . ' - ' . $event->consultorio->nombre,
                    'start' => $event->start_at,
                    'end'   => $event->end_at,
                    'color' => match ($event->estado) {
                        'Confirmado' => 'green',
                        'Reagendado' => 'blue',
                        'Reagendando' => 'orange',
                        'Se Presentó' => 'teal',
                        default => 'gray',
                    },
                ]
            )
            ->all();
    }


    public function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('cliente_id')
                ->label('Paciente')
                ->relationship('cliente', 'nombre')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\Actions::make([
                \Filament\Forms\Components\Actions\Action::make('contactar')
                    ->label('Contactar por WhatsApp')
                    ->color('success')
                    ->icon('heroicon-o-phone')
                    ->url(function ($record) {
                        $telefono = preg_replace('/\D/', '', $record->telefono ?? $record->cliente?->telefono);
                        if (!filled($telefono)) {
                            return '#';
                        }

                        $nombre = $record->cliente?->nombre ?? '';
                        $fecha = optional(Carbon::parse($record?->start_at)->locale('es'))->translatedFormat('l d/m/Y h:i A');

                        $mensaje = "Hola " . ($nombre ? "*{$nombre}*" : "") . ", le saludamos desde la clínica.\n\n" .
                            "¿Podrá asistir a su cita programada el día {$fecha}?\n\n" .
                            "Por favor confirme su asistencia respondiendo:\n" .
                            "✅ Sí\n❌ No\n🔁 Reagendar\n\n" .
                            "Agradecemos su pronta respuesta.";

                        return "https://wa.me/504{$telefono}?text=" . urlencode($mensaje);
                    }, true)
                    ->openUrlInNewTab()
                    ->visible(fn($record) => filled($record?->telefono ?? $record?->cliente?->telefono)),
            ])->columnSpanFull()->hiddenLabel(),

            Forms\Components\Select::make('consultorio_id')
                ->label('Consultorio')
                ->relationship('consultorio', 'nombre')
                ->required()
                ->searchable()
                ->preload()
                ->reactive(), // <--- ESTE ES CLAVE

            Forms\Components\Select::make('especialidades')
                ->label('Especialidades')
                ->multiple()
                ->searchable()
                ->preload()
                ->relationship('especialidades', 'nombre')
                ->required()
                ->reactive(),

            Forms\Components\Select::make('servicios')
                ->label('Servicios')
                ->multiple()
                ->searchable()
                ->preload()
                ->options(fn(callable $get) => \App\Models\Servicio::whereIn('especialidad_id', $get('especialidades') ?? [])
                    ->pluck('nombre', 'id'))
                ->required()
                ->reactive()
                ->disabled(fn(callable $get) => empty($get('especialidades')))
                ->afterStateHydrated(function ($component, $state, $record) {
                    $component->state(
                        $record?->servicios()->pluck('servicios.id')->toArray()
                    );
                }),

            Forms\Components\Actions::make([
                \Filament\Forms\Components\Actions\Action::make('confirmado')
                    ->label('Confirmar')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn($record) => $record && $record->estado !== 'Confirmado')
                    ->action(function ($record) {
                        $record->estado = 'Confirmado';
                        $record->save();
                    }),

                \Filament\Forms\Components\Actions\Action::make('se_presento')
                    ->label('Se Presentó')
                    ->color('primary')
                    ->icon('heroicon-o-user')
                    ->visible(fn($record) => $record && $record->estado === 'Confirmado')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $nombreServicio = optional($record->servicio)->nombre ?? 'Servicio no especificado';

                        // Guardar historial
                        \App\Models\HistorialPaciente::create([
                            'paciente_id' => $record->cliente_id,
                            'evento_id' => $record->id,
                            'accion' => 'asistió',
                            'descripcion' => "El paciente asistió a su cita. Servicio realizado: $nombreServicio.",
                            'created_by' => \Illuminate\Support\Facades\Auth::id() ?? 1,
                        ]);

                        // Eliminar evento
                        $record->delete();

                        // Notificación
                        \Filament\Notifications\Notification::make()
                            ->title('Asistencia registrada')
                            ->body("El paciente fue marcado como presente y se eliminó la cita actual.")
                            ->success()
                            ->send();

                        // Actualizar calendario en tiempo real
                        $this->dispatch('refreshCalendar');
                    }),

                //aca iniciamos no se presento

                \Filament\Forms\Components\Actions\Action::make('no_se_presento')
                    ->label('No se presentó')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn($record) => $record?->estado === 'Confirmado')
                    ->action(function ($record) {
                        $horaDeseada = \Carbon\Carbon::parse($record->start_at)->format('H:i:s');
                        $consultorioId = $record->consultorio_id;

                        $proximaFecha = \Carbon\Carbon::parse($record->start_at)->copy()->addDay();
                        $fechaDisponible = null;

                        while (!$fechaDisponible) {
                            $fechaHora = $proximaFecha->copy()->setTimeFromTimeString($horaDeseada);

                            $yaOcupado = \App\Models\Event::where('start_at', $fechaHora)
                                ->where('consultorio_id', $consultorioId)
                                ->exists();

                            if (!$yaOcupado) {
                                $fechaDisponible = $fechaHora;
                                break;
                            }

                            $proximaFecha->addDay();
                        }

                        // Crear nuevo evento base
                        $nuevo = \App\Models\Event::create([
                            'cliente_id' => $record->cliente_id,
                            'consultorio_id' => $record->consultorio_id,
                            'usuario_id' => $record->usuario_id,
                            'start_at' => $fechaDisponible,
                            'end_at' => $fechaDisponible->copy()->addMinutes(20),
                            'estado' => 'Pendiente',
                            'created_by' => Auth::id(),
                        ]);

                        // Sincronizar especialidades
                        $nuevo->especialidades()->sync($record->especialidades->pluck('id')->toArray());

                        // Sincronizar servicios
                        $nuevo->servicios()->sync($record->servicios->pluck('id')->toArray());

                        // Registrar historial
                        \App\Models\HistorialPaciente::create([
                            'paciente_id' => $record->cliente_id,
                            'evento_id' => $nuevo->id,
                            'accion' => 'Reagendado',
                            'descripcion' => 'El paciente no se presentó. Se reagendó automáticamente para el ' . $fechaDisponible->format('d/m/Y H:i'),
                            'created_by' => Auth::id(),
                        ]);

                        // Eliminar el evento original
                        $record->delete();

                        \Filament\Notifications\Notification::make()
                            ->title('Reagendado')
                            ->body('El paciente fue reagendado para el ' . $fechaDisponible->translatedFormat('l d \d\e F h:i A'))
                            ->success()
                            ->send();
                    }),
                //aca iniciamos reagendacion directa
                \Filament\Forms\Components\Actions\Action::make('reagendar_directo')
                    ->label('Reagendar (Directo)')
                    ->color('warning')
                    ->icon('heroicon-o-calendar')
                    ->visible(fn($record) => $record && in_array($record->estado, ['Pendiente', 'Reagendado']))
                    ->action(function ($record) {
                        $horaDeseada = \Carbon\Carbon::parse($record->start_at)->format('H:i:s');
                        $consultorioId = $record->consultorio_id;

                        $proximaFecha = \Carbon\Carbon::parse($record->start_at)->copy()->addDay();
                        $fechaDisponible = null;

                        while (!$fechaDisponible) {
                            $fechaHora = $proximaFecha->copy()->setTimeFromTimeString($horaDeseada);

                            $yaOcupado = \App\Models\Event::where('start_at', $fechaHora)
                                ->where('consultorio_id', $consultorioId)
                                ->exists();

                            if (!$yaOcupado) {
                                $fechaDisponible = $fechaHora;
                                break;
                            }

                            $proximaFecha->addDay();
                        }

                        // Guardar historial
                        \App\Models\HistorialPaciente::create([
                            'paciente_id' => $record->cliente_id,
                            'evento_id' => $record->id,
                            'accion' => 'Reagendado',
                            'descripcion' => 'La cita fue reagendada directamente a la próxima fecha disponible sin intercambio.',
                            'created_by' => \Illuminate\Support\Facades\Auth::id(),
                        ]);

                        // Actualizar el evento actual
                        $record->start_at = $fechaDisponible;
                        $record->end_at = $fechaDisponible->copy()->addMinutes(20);
                        $record->estado = 'Reagendado';
                        $record->save();

                        \Filament\Notifications\Notification::make()
                            ->title('Cita reagendada')
                            ->body('La cita ha sido reagendada automáticamente a la próxima fecha libre.')
                            ->success()
                            ->send();
                    }),


                \Filament\Forms\Components\Actions\Action::make('reagendar')
                    ->label('Intercambiar')
                    ->color('warning')
                    ->icon('heroicon-o-calendar')
                    ->form([
                        Forms\Components\Select::make('evento_reemplazo_id')
                            ->label('Paciente alternativo')
                            ->options(
                                fn($record) =>
                                \App\Models\Event::whereBetween('start_at', [
                                    \Carbon\Carbon::parse($record->start_at)->addDay()->startOfDay(),
                                    \Carbon\Carbon::parse($record->start_at)->addDays(2)->endOfDay(),
                                ])
                                    ->where('estado', 'Pendiente')
                                    ->get()
                                    ->mapWithKeys(fn($e) => [
                                        $e->id => $e->cliente->nombre . ' (' .
                                            \Carbon\Carbon::parse($e->start_at)->locale('es')->translatedFormat('l d/m h:i A') . ')',
                                    ])
                            )
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function ($data, $record) {
                        $eventoA = $record;
                        $eventoB = \App\Models\Event::find($data['evento_reemplazo_id']);

                        if (!$eventoB) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body('No se encontró el evento alternativo.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Cambiar ambos eventos a estado "Reagendando"
                        $eventoA->estado = 'Reagendando';
                        $eventoA->save();

                        $eventoB->estado = 'Reagendando';
                        $eventoB->save();

                        // Registrar solicitud en tabla cambio_eventos
                        \App\Models\CambioEvento::create([
                            'evento_id_origen' => $eventoA->id,
                            'evento_id_destino' => $eventoB->id,
                            'created_by' => \Illuminate\Support\Facades\Auth::id(),
                            'estado' => 'pendiente',
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Solicitud enviada')
                            ->body('Se ha solicitado el cambio. Ambos pacientes están en estado "Reagendando".')
                            ->success()
                            ->send();
                    })

                    ->visible(fn($record) => $record?->estado === 'Pendiente')


            ])
                ->columnSpanFull()
                ->hiddenLabel(),


        ];
    }
    protected function getFullCalendarOptions(): array
    {
        return [
            'selectable' => true,
        ];
    }
    protected function getNavigateToCreateEventUrl(string $date): ?string
    {
        return route('filament.admin.resources.events.create', [
            'start_date' => $date,
        ]);
    }

    //empece a cambiar
}
