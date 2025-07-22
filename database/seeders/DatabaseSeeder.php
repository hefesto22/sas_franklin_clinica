<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Crear los roles
        $roles = [
            'Admin',
            'Jefe',
            'Doctor',
            'Gerente',
            'recepcion',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Crear el usuario Admin
        $adminRole = Role::where('name', 'Admin')->first();

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('12345678'),
            'role_id' => $adminRole->id,
            'telefono' => '9999-9999',
            'direccion' => 'Calle Principal, Ciudad Central',
            'avatar' => null,
            'estado' => true,
        ]);

        $admin->created_by = $admin->id;
        $admin->updated_by = $admin->id;
        $admin->save();

        $adminId = $admin->id;

        // Especialidades
        $especialidades = [
            ['nombre' => 'Odontología', 'descripcion' => 'Tratamientos dentales'],
            ['nombre' => 'Dermatología', 'descripcion' => 'Cuidado de la piel'],
            ['nombre' => 'Pediatría', 'descripcion' => 'Atención médica infantil'],
        ];

        foreach ($especialidades as $esp) {
            DB::table('especialidades')->insert([
                ...$esp,
                'estado' => 'activo',
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Servicios (asume que las especialidades tienen IDs del 1 al 3)
        $servicios = [
            ['especialidad_id' => 1, 'nombre' => 'Limpieza dental', 'precio' => 500.00],
            ['especialidad_id' => 2, 'nombre' => 'Consulta dermatológica', 'precio' => 700.00],
            ['especialidad_id' => 3, 'nombre' => 'Control pediátrico', 'precio' => null],
        ];

        foreach ($servicios as $serv) {
            DB::table('servicios')->insert([
                ...$serv,
                'descripcion' => null,
                'precio_promocional' => null,
                'estado' => 'activo',
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Clientes de prueba
        for ($i = 1; $i <= 5; $i++) {
            DB::table('clientes')->insert([
                'nombre' => "Cliente $i",
                'dni' => '08011990' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'telefono' => '9999' . rand(1000, 9999),
                'fecha_nacimiento' => now()->subYears(rand(18, 50)),
                'observaciones' => null,
                'estado' => 'activo',
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
