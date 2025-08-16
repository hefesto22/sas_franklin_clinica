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

use Filament\Forms\Get;
use Filament\Forms\Set;


class CalendarWidget extends FullCalendarWidget
{
    public Model | string | null $model = Event::class;

    protected function headerActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear evento')
                ->icon('heroicon-o-plus')

                // 👉 Usa el schema COMPLETO para crear (con Teléfono, Fecha, Hora, etc.)
                ->form(fn() => $this->getCreateFormSchema())

                ->mountUsing(function (Forms\Form $form, array $arguments) {
                    $form->fill([
                        'start_date' => isset($arguments['start'])
                            ? \Illuminate\Support\Carbon::parse($arguments['start'])->format('Y-m-d')
                            : now()->format('Y-m-d'),
                        'start_time' => isset($arguments['start'])
                            ? \Illuminate\Support\Carbon::parse($arguments['start'])->format('H:i')
                            : null,
                        'end_time'   => isset($arguments['end'])
                            ? \Illuminate\Support\Carbon::parse($arguments['end'])->format('H:i')
                            : null,
                    ]);
                })
                ->mutateFormDataUsing(function (array $data): array {
                    $start = \Illuminate\Support\Carbon::parse("{$data['start_date']} {$data['start_time']}:00");
                    $end   = isset($data['end_time'])
                        ? \Illuminate\Support\Carbon::parse("{$data['start_date']} {$data['end_time']}:00")
                        : (clone $start)->addMinutes(30);

                    $data['start_at'] = $start;
                    $data['end_at']   = $end;

                    unset($data['start_date'], $data['start_time'], $data['end_time']);

                    $data['estado']     = $data['estado'] ?? 'Pendiente';
                    $data['created_by'] = \Illuminate\Support\Facades\Auth::id(); // 👈 AQUI

                    // (si tu tabla también requiere usuario_id)
                    // $data['usuario_id'] = $data['usuario_id'] ?? \Illuminate\Support\Facades\Auth::id();

                    return $data;
                })

                ->using(function (array $data) {
                    $especialidades = $data['especialidades'] ?? [];
                    $servicios      = $data['servicios'] ?? [];
                    unset($data['especialidades'], $data['servicios']);

                    $event = \App\Models\Event::create($data);
                    if ($especialidades) $event->especialidades()->sync($especialidades);
                    if ($servicios)      $event->servicios()->sync($servicios);

                    return $event;
                }),
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
                    ->visible(
                        fn($record) =>
                        $record
                            && $record->estado !== 'Confirmado'
                            && $record->estado !== 'Reagendando'
                    )
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
                        $servicio = $record->servicios->first(); // Solo tomamos el primer servicio por simplicidad
                        $nombreServicio = $servicio?->nombre ?? 'Servicio no especificado';
                        $precioServicio = $servicio?->precio ?? 0;

                        // Guardar historial del paciente
                        \App\Models\HistorialPaciente::create([
                            'paciente_id' => $record->cliente_id,
                            'evento_id' => $record->id,
                            'accion' => 'asistió',
                            'descripcion' => "El paciente asistió a su cita. Servicio realizado: $nombreServicio.",
                            'created_by' => \Illuminate\Support\Facades\Auth::id() ?? 1,
                        ]);

                        // Registrar en cliente_actividades
                        \App\Models\ClienteActividad::create([
                            'cliente_id' => $record->cliente_id,
                            'fecha' => now(),
                            'actividad' => $nombreServicio,
                            'pago' => $precioServicio,
                        ]);

                        // Eliminar evento
                        $record->delete();

                        // Notificación
                        \Filament\Notifications\Notification::make()
                            ->title('Asistencia registrada')
                            ->body("El paciente fue marcado como presente. Se registró el servicio y se eliminó la cita.")
                            ->success()
                            ->send();

                        // Actualizar calendario
                        $this->dispatch('refreshCalendar');
                    }),


                //aca iniciamos no se presento

                \Filament\Forms\Components\Actions\Action::make('no_se_presento')
                    ->label('No se presentó')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn($record) => $record?->estado === 'Confirmado')
                    ->action(function ($record) {
                        $horaDeseada   = \Carbon\Carbon::parse($record->start_at)->format('H:i:s');
                        $consultorioId = $record->consultorio_id;

                        // Empezar desde el día siguiente
                        $proximaFecha = \Carbon\Carbon::parse($record->start_at)->copy()->addDay();

                        // Si cae domingo, mover a lunes
                        if ($proximaFecha->isSunday()) {
                            $proximaFecha->addDay();
                        }

                        $fechaDisponible = null;

                        while (!$fechaDisponible) {
                            // Evita domingos dentro del bucle también
                            if ($proximaFecha->isSunday()) {
                                $proximaFecha->addDay();
                                continue;
                            }

                            $fechaHora = $proximaFecha->copy()->setTimeFromTimeString($horaDeseada);

                            $yaOcupado = \App\Models\Event::where('consultorio_id', $consultorioId)
                                ->where('start_at', $fechaHora)
                                ->exists();

                            if (!$yaOcupado) {
                                $fechaDisponible = $fechaHora;
                                break;
                            }

                            $proximaFecha->addDay();
                        }

                        // Crear nuevo evento base
                        $nuevo = \App\Models\Event::create([
                            'cliente_id'     => $record->cliente_id,
                            'consultorio_id' => $record->consultorio_id,
                            'usuario_id'     => $record->usuario_id,
                            'start_at'       => $fechaDisponible,
                            'end_at'         => $fechaDisponible->copy()->addMinutes(30), // cambia a 30 si tu slot es de 30'
                            'estado'         => 'Pendiente',
                            'created_by'     => \Illuminate\Support\Facades\Auth::id(),
                        ]);

                        // Sincronizar especialidades y servicios
                        $nuevo->especialidades()->sync($record->especialidades->pluck('id')->toArray());
                        $nuevo->servicios()->sync($record->servicios->pluck('id')->toArray());

                        // Registrar historial
                        \App\Models\HistorialPaciente::create([
                            'paciente_id' => $record->cliente_id,
                            'evento_id'   => $nuevo->id,
                            'accion'      => 'Reagendado',
                            'descripcion' => 'El paciente no se presentó. Reagendado para el ' . $fechaDisponible->format('d/m/Y H:i'),
                            'created_by'  => \Illuminate\Support\Facades\Auth::id(),
                        ]);

                        // Eliminar el evento original
                        $record->delete();

                        \Filament\Notifications\Notification::make()
                            ->title('Reagendado')
                            ->body('El paciente fue reagendado para el ' . $fechaDisponible->locale('es')->translatedFormat('l d \\d\\e F h:i A'))
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
                        $horaDeseada   = \Carbon\Carbon::parse($record->start_at)->format('H:i:s');
                        $consultorioId = $record->consultorio_id;

                        // Comienza desde el día siguiente
                        $proximaFecha = \Carbon\Carbon::parse($record->start_at)->copy()->addDay();

                        // Si cae en domingo, pasa a lunes
                        if ($proximaFecha->isSunday()) {
                            $proximaFecha->addDay();
                        }

                        $fechaDisponible = null;

                        while (!$fechaDisponible) {
                            // Si en el ciclo volvemos a caer en domingo, saltar a lunes
                            if ($proximaFecha->isSunday()) {
                                $proximaFecha->addDay();
                                continue;
                            }

                            $fechaHora = $proximaFecha->copy()->setTimeFromTimeString($horaDeseada);

                            $yaOcupado = \App\Models\Event::where('consultorio_id', $consultorioId)
                                ->where('start_at', $fechaHora)
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
                            'evento_id'   => $record->id,
                            'accion'      => 'Reagendado',
                            'descripcion' => 'La cita fue reagendada directamente a la próxima fecha disponible sin intercambio.',
                            'created_by'  => \Illuminate\Support\Facades\Auth::id(),
                        ]);

                        // Actualizar el evento actual (ajusta la duración si usas 30 min)
                        $record->start_at = $fechaDisponible;
                        $record->end_at   = $fechaDisponible->copy()->addMinutes(20);
                        $record->estado   = 'Reagendado';
                        $record->save();

                        \Filament\Notifications\Notification::make()
                            ->title('Cita reagendada')
                            ->body('La cita ha sido reagendada automáticamente a la próxima fecha libre (evitando domingos).')
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
                            ->searchable()
                            ->required()
                            ->options(function ($record) {
                                $inicio = \Carbon\Carbon::parse($record->start_at)->addDay()->startOfDay();
                                $fin    = \Carbon\Carbon::parse($record->start_at)->addDays(5)->endOfDay();

                                // Ventana de la MISMA HORA (00–59 min)
                                $h      = \Carbon\Carbon::parse($record->start_at);
                                $horaIni = $h->copy()->startOfHour()->format('H:i:s'); // ej. 08:00:00
                                $horaFin = $h->copy()->endOfHour()->format('H:i:s');   // ej. 08:59:59

                                return \App\Models\Event::query()
                                    ->where('id', '!=', $record->id)
                                    ->where('estado', 'Pendiente')
                                    ->whereBetween('start_at', [$inicio, $fin])           // próximos 5 días
                                    ->whereTime('start_at', '>=', $horaIni)               // misma hora
                                    ->whereTime('start_at', '<=', $horaFin)
                                    // ->where('consultorio_id', $record->consultorio_id)  // (opcional) mismo consultorio
                                    ->orderBy('start_at')
                                    ->get()
                                    ->mapWithKeys(fn($e) => [
                                        $e->id => $e->cliente->nombre . ' (' .
                                            \Carbon\Carbon::parse($e->start_at)
                                            ->locale('es')->translatedFormat('l d/m h:i A') . ')',
                                    ]);
                            }),
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

                        // Cambiar ambos eventos a "Reagendando"
                        $eventoA->update(['estado' => 'Reagendando']);
                        $eventoB->update(['estado' => 'Reagendando']);

                        // Registrar solicitud
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

    // aca comienza la maravila
    protected function getCreateFormSchema(): array
    {
        return [
            Forms\Components\Grid::make([
                'default' => 1,   // 1 columna en pantallas pequeñas
                'md'      => 2,   // 2 columnas desde md en adelante
            ])->schema([

                // Fila 1
                Forms\Components\Select::make('cliente_id')
                    ->label('Cliente')
                    ->searchable()
                    ->required()
                    ->getSearchResultsUsing(
                        fn(string $search) =>
                        \App\Models\Cliente::query()
                            ->where('nombre', 'like', "%{$search}%")
                            ->orWhere('dni', 'like', "%{$search}%")
                            ->limit(5)
                            ->pluck('nombre', 'id')
                    )
                    ->getOptionLabelUsing(fn($value) => \App\Models\Cliente::find($value)?->nombre)
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Get|Forms\Set $set) {
                        if ($state) {
                            $cli = \App\Models\Cliente::find($state);
                            $set('telefono', $cli?->telefono);
                        }
                    }),

                Forms\Components\TextInput::make('telefono')
                    ->label('Teléfono')
                    ->tel()
                    ->disabled()
                    ->dehydrated(true),

                // Fila 2
                Forms\Components\Select::make('especialidades')
                    ->label('Especialidades')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(\App\Models\Especialidad::pluck('nombre', 'id'))
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn(Forms\Set $set) => $set('servicios', [])),

                Forms\Components\Select::make('servicios')
                    ->label('Servicios')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->disabled(fn(Forms\Get $get) => empty($get('especialidades')))
                    ->relationship(
                        name: 'servicios',
                        titleAttribute: 'nombre',
                        modifyQueryUsing: fn($query, Forms\Get $get) =>
                        $query->whereIn('especialidad_id', $get('especialidades') ?: [-1])
                    ),

                // Fila 3
                Forms\Components\Select::make('consultorio_id')
                    ->label('Consultorio')
                    ->options(\App\Models\Consultorio::pluck('nombre', 'id'))
                    ->searchable()
                    ->required()
                    ->reactive(),

                Forms\Components\DatePicker::make('start_date')
                    ->label('Fecha')
                    ->required()
                    ->reactive()
                    ->native(false)
                    ->minDate(now())
                    ->rule(function () {
                        return function (string $attribute, $value, $fail) {
                            $fecha = \Illuminate\Support\Carbon::parse($value);
                            if ($fecha->isSunday()) {
                                $fail('Los domingos no se trabaja. Por favor seleccione otro día.');
                            }
                        };
                    }),

                // Fila 4
                Forms\Components\Select::make('start_time')
                    ->label('Hora disponible')
                    ->required()
                    ->options(fn(Forms\Get $get) => \App\Helpers\HorarioHelper::getHorasDisponibles(
                        $get('start_date'),
                        $get('consultorio_id')
                    ))
                    ->disabled(fn(Forms\Get $get) => !$get('start_date') || !$get('consultorio_id'))
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $h = \Illuminate\Support\Carbon::createFromFormat('H:i', $state);
                            $set('end_time', $h->copy()->addMinutes(30)->format('H:i'));
                        }
                    }),

                Forms\Components\TimePicker::make('end_time')
                    ->label('Hora de finalización')
                    ->required()
                    ->disabled()
                    ->seconds(false)
                    ->step(20),
            ]),
        ];
    }
}
