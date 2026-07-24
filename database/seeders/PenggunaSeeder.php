<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Pengguna;
use App\Models\Role;

class PenggunaSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // INSERT PENGGUNA (14 user)
        // ==========================================
        DB::table('pengguna')->insert([
            // ==========================================
            // 1. AFET REGIONAL (Admin Pusat)
            // ==========================================
            [
                'nama' => 'Admin Pusat',
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'role' => 'afet_regional',
                'id_bandara' => null,
                'id_lokasi' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ==========================================
            // 2. AFET BANDARA (4 user)
            // ==========================================
            [
                'nama' => 'AFET CGK',
                'username' => 'afet_cgk',
                'password' => Hash::make('afet123'),
                'role' => 'afet_bandara',
                'id_bandara' => 1,
                'id_lokasi' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'AFET BDO',
                'username' => 'afet_bdo',
                'password' => Hash::make('afet123'),
                'role' => 'afet_bandara',
                'id_bandara' => 2,
                'id_lokasi' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'AFET HLP',
                'username' => 'afet_hlp',
                'password' => Hash::make('afet123'),
                'role' => 'afet_bandara',
                'id_bandara' => 3,
                'id_lokasi' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'AFET KJT',
                'username' => 'afet_kjt',
                'password' => Hash::make('afet123'),
                'role' => 'afet_bandara',
                'id_bandara' => 4,
                'id_lokasi' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ==========================================
            // 3. DIVISI HEAD
            // ==========================================
            [
                'nama' => 'Div Head CGK Terminal 1',
                'username' => 'divhead_cgk_t1',
                'password' => Hash::make('divhead123'),
                'role' => 'div_head',
                'id_bandara' => 1,
                'id_lokasi' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Div Head CGK Terminal 2',
                'username' => 'divhead_cgk_t2',
                'password' => Hash::make('divhead123'),
                'role' => 'div_head',
                'id_bandara' => 1,
                'id_lokasi' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Div Head CGK Terminal 3',
                'username' => 'divhead_cgk_t3',
                'password' => Hash::make('divhead123'),
                'role' => 'div_head',
                'id_bandara' => 1,
                'id_lokasi' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Div Head CGK Non Terminal',
                'username' => 'divhead_cgk_non',
                'password' => Hash::make('divhead123'),
                'role' => 'div_head',
                'id_bandara' => 1,
                'id_lokasi' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Div Head BDO',
                'username' => 'divhead_bdo',
                'password' => Hash::make('divhead123'),
                'role' => 'div_head',
                'id_bandara' => 2,
                'id_lokasi' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Div Head HLP',
                'username' => 'divhead_hlp',
                'password' => Hash::make('divhead123'),
                'role' => 'div_head',
                'id_bandara' => 3,
                'id_lokasi' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Div Head KJT',
                'username' => 'divhead_kjt',
                'password' => Hash::make('divhead123'),
                'role' => 'div_head',
                'id_bandara' => 4,
                'id_lokasi' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ==========================================
            // 4. GM KC (4 user)
            // ==========================================
            [
                'nama' => 'GM CGK',
                'username' => 'gm_cgk',
                'password' => Hash::make('gm123'),
                'role' => 'gm_kc',
                'id_bandara' => 1,
                'id_lokasi' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'GM BDO',
                'username' => 'gm_bdo',
                'password' => Hash::make('gm123'),
                'role' => 'gm_kc',
                'id_bandara' => 2,
                'id_lokasi' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'GM HLP',
                'username' => 'gm_hlp',
                'password' => Hash::make('gm123'),
                'role' => 'gm_kc',
                'id_bandara' => 3,
                'id_lokasi' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'GM KJT',
                'username' => 'gm_kjt',
                'password' => Hash::make('gm123'),
                'role' => 'gm_kc',
                'id_bandara' => 4,
                'id_lokasi' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ==========================================
            // 5. HEAD OFFICE
            // ==========================================
            [
                'nama' => 'Head Office',
                'username' => 'ho',
                'password' => Hash::make('ho123'),
                'role' => 'ho',
                'id_bandara' => null,
                'id_lokasi' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ==========================================
            // 6. CEO
            // ==========================================
            [
                'nama' => 'CEO',
                'username' => 'ceo',
                'password' => Hash::make('ceo123'),
                'role' => 'ceo',
                'id_bandara' => null,
                'id_lokasi' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ==========================================
            // 7. TEKNISI (4 user)
            // ==========================================
            [
                'nama' => 'Teknisi CGK Terminal 1',
                'username' => 'teknisi_cgk1',
                'password' => Hash::make('teknisi123'),
                'role' => 'teknisi',
                'id_bandara' => 1,
                'id_lokasi' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Teknisi BDO Terminal 1',
                'username' => 'teknisi_bdo_t1',
                'password' => Hash::make('teknisi123'),
                'role' => 'teknisi',
                'id_bandara' => 2,
                'id_lokasi' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Teknisi HLP Terminal 1',
                'username' => 'teknisi_hlp_t1',
                'password' => Hash::make('teknisi123'),
                'role' => 'teknisi',
                'id_bandara' => 3,
                'id_lokasi' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Teknisi KJT Terminal 1',
                'username' => 'teknisi_kjt_t1',
                'password' => Hash::make('teknisi123'),
                'role' => 'teknisi',
                'id_bandara' => 4,
                'id_lokasi' => 9,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // ==========================================
        // ASSIGN ROLE KE SEMUA PENGGUNA
        // ==========================================
        $this->assignRoles();
    }

    private function assignRoles(): void
    {
        $users = Pengguna::all();

        foreach ($users as $user) {
            $role = Role::where('slug', $user->role)->first();

            if ($role && !$user->roles()->exists()) {
                $user->roles()->attach($role->id);
                $this->command->info("✅ Role '{$role->slug}' → user '{$user->username}'");
            } else {
                $this->command->warn("⚠️ Role '{$user->role}' tidak ditemukan untuk user '{$user->username}'");
            }
        }
    }
}