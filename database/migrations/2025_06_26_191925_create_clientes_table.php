<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla especialidades
        Schema::create('especialidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Tabla servicios
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('especialidad_id')->constrained('especialidades')->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 10, 2)->nullable();
            $table->decimal('precio_promocional', 10, 2)->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Tabla clientes (expediente clínico)
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('dni')->unique();
            $table->unsignedTinyInteger('edad')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('genero', ['masculino', 'femenino', 'otro'])->nullable();
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->string('ocupacion')->nullable();
            $table->text('motivo_consulta')->nullable();
            $table->text('antecedentes')->nullable();
            $table->text('alergias')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Tabla actividades del cliente (expediente)
        Schema::create('cliente_actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->date('fecha');
            $table->string('actividad');
            $table->decimal('pago', 10, 2)->nullable();
            $table->timestamps();
        });

        // Tabla imágenes del cliente (expediente)
        Schema::create('cliente_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('path'); // ruta de imagen subida
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_imagenes');
        Schema::dropIfExists('cliente_actividades');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('servicios');
        Schema::dropIfExists('especialidades');
    }
};
