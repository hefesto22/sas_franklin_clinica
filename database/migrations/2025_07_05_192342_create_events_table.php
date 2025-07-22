<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de consultorios
        Schema::create('consultorios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // Tabla de pacientes por hora (configuración por consultorio y hora)
        Schema::create('pacientes_por_hora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultorio_id')->constrained('consultorios')->onDelete('cascade');
            $table->time('hora'); // Ej: 08:00, 09:00, etc.
            $table->integer('cantidad');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['consultorio_id', 'hora']);
        });

        // Tabla de eventos (ahora representa una cita o atención)
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            // Relaciones clave foránea
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('consultorio_id')->constrained('consultorios')->onDelete('cascade');

            // Teléfono y estado
            $table->string('telefono')->nullable();
            $table->enum('estado', ['Pendiente', 'Reagendando', 'Reagendado', 'Confirmado', 'Se Presentó'])->default('Pendiente');

            // Fechas de la cita
            $table->dateTime('start_at');
            $table->dateTime('end_at');

            // Auditoría
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('cascade');

            $table->timestamps();
        });

        // Tabla pivote event_especialidad
        Schema::create('event_especialidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('especialidad_id')->constrained('especialidades')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['event_id', 'especialidad_id']);
        });

        // Tabla pivote event_servicio
        Schema::create('event_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('servicio_id')->constrained('servicios')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['event_id', 'servicio_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_servicio');
        Schema::dropIfExists('event_especialidad');
        Schema::dropIfExists('events');
        Schema::dropIfExists('pacientes_por_hora');
        Schema::dropIfExists('consultorios');
    }
};
