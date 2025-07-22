<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('historial_paciente', function (Blueprint $table) {
            $table->id();

            $table->foreignId('paciente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('evento_id')->nullable()->constrained('events')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            $table->enum('accion', ['asistio', 'no_asistio', 'reagendado', 'cancelado']);
            $table->text('descripcion')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_paciente');
    }
};
