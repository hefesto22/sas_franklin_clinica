<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cambio_eventos', function (Blueprint $table) {
            $table->id();

            // Eventos involucrados
            $table->foreignId('evento_id_origen')->constrained('events')->onDelete('cascade');
            $table->foreignId('evento_id_destino')->constrained('events')->onDelete('cascade');

            // Usuario que propone el cambio
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            // Usuario que acepta o rechaza (opcional, nullable)
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            // Estado del proceso
            $table->enum('estado', ['pendiente', 'aceptado', 'rechazado', 'cancelado'])->default('pendiente');

            // Campos auxiliares
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamp('aprobado_en')->nullable();
            $table->timestamp('rechazado_en')->nullable();
            $table->timestamp('cancelado_en')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cambio_eventos');
    }
};
