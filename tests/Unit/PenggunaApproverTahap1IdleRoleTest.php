<?php

namespace Tests\Unit;

use App\Models\Bandara;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit test untuk Pengguna::approverTahap1IdleRole(), yaitu logic inti
 * penentu siapa yang berwenang approve tahap 1 pengajuan idle:
 *
 *  - Ada dep_head di bandara itu   -> 'dep_head' (perilaku lama, CGK)
 *  - Gak ada dep_head, ada div_head -> 'div_head' (fallback, non-CGK)
 *  - Gak ada keduanya               -> null ("stuck", butuh admin buatkan akun)
 *
 * Setup DB sengaja minimal: cuma tabel bandara + pengguna, tanpa
 * Alat/PengajuanIdle, karena method ini murni query dua tabel itu.
 */
class PenggunaApproverTahap1IdleRoleTest extends TestCase
{
    use RefreshDatabase;

    private function buatBandara(string $kode = 'CGK'): Bandara
    {
        return Bandara::create([
            'nama_bandara' => "Bandara {$kode}",
            'kode_bandara' => $kode,
            'lokasi' => 'Test',
            'jam_operasional' => 24,
        ]);
    }

    private function buatPengguna(string $role, ?int $idBandara, string $username): Pengguna
    {
        return Pengguna::create([
            'nama' => ucfirst($role) . ' ' . $username,
            'username' => $username,
            'password' => bcrypt('password'),
            'role' => $role,
            'id_bandara' => $idBandara,
        ]);
    }

    public function test_null_id_bandara_selalu_menghasilkan_null(): void
    {
        $this->assertNull(Pengguna::approverTahap1IdleRole(null));
    }

    public function test_ada_dep_head_di_bandara_itu_mengembalikan_dep_head(): void
    {
        $cgk = $this->buatBandara('CGK');
        $this->buatPengguna('dep_head', $cgk->id_bandara, 'dephead_cgk_sses');

        $this->assertSame(
            'dep_head',
            Pengguna::approverTahap1IdleRole($cgk->id_bandara)
        );
    }

    public function test_tanpa_dep_head_tapi_ada_div_head_mengembalikan_div_head(): void
    {
        $dps = $this->buatBandara('DPS');
        $this->buatPengguna('div_head', $dps->id_bandara, 'divhead_dps');

        $this->assertSame(
            'div_head',
            Pengguna::approverTahap1IdleRole($dps->id_bandara)
        );
    }

    public function test_tanpa_dep_head_dan_div_head_mengembalikan_null_alias_stuck(): void
    {
        $kno = $this->buatBandara('KNO');
        $this->buatPengguna('teknisi', $kno->id_bandara, 'teknisi_kno');
        $this->buatPengguna('afet_bandara', $kno->id_bandara, 'afet_kno');

        $this->assertNull(Pengguna::approverTahap1IdleRole($kno->id_bandara));
    }

    public function test_dep_head_di_bandara_lain_tidak_ikut_terhitung(): void
    {
        // dep_head ada, tapi terikat ke CGK — bandara DPS tetap harus
        // fallback ke div_head-nya sendiri, bukan ketarik dep_head CGK.
        $cgk = $this->buatBandara('CGK');
        $dps = $this->buatBandara('DPS');

        $this->buatPengguna('dep_head', $cgk->id_bandara, 'dephead_cgk_bhs');
        $this->buatPengguna('div_head', $dps->id_bandara, 'divhead_dps');

        $this->assertSame('div_head', Pengguna::approverTahap1IdleRole($dps->id_bandara));
        $this->assertSame('dep_head', Pengguna::approverTahap1IdleRole($cgk->id_bandara));
    }

    public function test_div_head_di_bandara_lain_tidak_ikut_terhitung(): void
    {
        // div_head ada, tapi di bandara lain — bandara tanpa dep_head
        // maupun div_head sendiri harus tetap null (stuck), bukan
        // "meminjam" div_head bandara tetangga.
        $dps = $this->buatBandara('DPS');
        $kno = $this->buatBandara('KNO');

        $this->buatPengguna('div_head', $dps->id_bandara, 'divhead_dps');

        $this->assertNull(Pengguna::approverTahap1IdleRole($kno->id_bandara));
    }
}