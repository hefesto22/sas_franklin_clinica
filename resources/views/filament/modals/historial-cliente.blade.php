<div class="space-y-3">
    @forelse($historial as $item)
        <div class="border p-3 rounded shadow-sm bg-gray-50">
            <div><strong>Acción:</strong> {{ ucfirst($item->accion) }}</div>
            <div><strong>Descripción:</strong> {{ $item->descripcion }}</div>
            <div><strong>Fecha:</strong> {{ $item->created_at->format('d/m/Y h:i A') }}</div>
        </div>
    @empty
        <p>No hay historial registrado.</p>
    @endforelse
</div>
