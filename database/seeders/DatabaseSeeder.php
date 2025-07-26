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
        // Crear roles
        $roles = ['Admin', 'Jefe', 'Doctor', 'Gerente', 'recepcion'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Crear usuario Admin
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

        // Servicios
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

        // Pacientes (clientes)
        $pacientes = [
            [
                'nombre' => 'Ana Martínez',
                'dni' => '080119900001',
                'telefono' => '33012826',
                'fecha_nacimiento' => '1990-05-10',
                'edad' => 34,
                'genero' => 'femenino',
                'direccion' => 'Colonia Centro',
                'ocupacion' => 'Secretaria',
                'motivo_consulta' => 'Dolor de muela',
                'antecedentes' => 'Sin antecedentes relevantes',
                'alergias' => 'Penicilina',
            ],
            [
                'nombre' => 'Carlos Pérez',
                'dni' => '080119900002',
                'telefono' => '33012826',
                'fecha_nacimiento' => '1985-07-20',
                'edad' => 39,
                'genero' => 'masculino',
                'direccion' => 'Barrio Abajo',
                'ocupacion' => 'Maestro',
                'motivo_consulta' => 'Sangrado de encías',
                'antecedentes' => 'Hipertensión controlada',
                'alergias' => null,
            ],
            [
                'nombre' => 'Lucía Rodríguez',
                'dni' => '080119900003',
                'telefono' => '33012826',
                'fecha_nacimiento' => '1995-11-02',
                'edad' => 28,
                'genero' => 'femenino',
                'direccion' => 'Residencial Las Palmas',
                'ocupacion' => 'Estudiante',
                'motivo_consulta' => 'Dolor en muela posterior',
                'antecedentes' => 'Sin antecedentes',
                'alergias' => 'Ibuprofeno',
            ],
        ];

        foreach ($pacientes as $p) {
            DB::table('clientes')->insert([
                ...$p,
                'estado' => 'activo',
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
