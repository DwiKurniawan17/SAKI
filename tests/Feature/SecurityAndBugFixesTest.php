<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use App\Models\SimulasiAki;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SecurityAndBugFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_approved_simulation_cannot_be_overwritten_via_studio_save(): void
    {
        $user = User::factory()->create(['is_approved' => true]);
        $user->assignRole('admin');

        $simulasi = SimulasiAki::create([
            'user_id_pihak_a' => $user->id,
            'user_id_pihak_b' => $user->id,
            'status' => SimulasiAki::STATUS_APPROVED,
            'nama_proyek' => 'Locked Approved Project',
            'lokasi' => 'Jakarta',
            'paketisasi' => 'Astinet',
            'bandwidth' => '100 Mbps',
            'input_json' => ['durasi_kontrak' => 12],
            'hasil_json' => ['npv' => 1000],
            'npv' => 1000,
            'mirr' => 15.0,
            'payback_period' => 2.5,
            'layak' => true,
        ]);

        $payload = [
            'simulasi_id'    => $simulasi->id,
            'nama_proyek'    => 'Hacked Overwrite Name',
            'lokasi'         => 'Jakarta',
            'paketisasi'     => 'Astinet',
            'bandwidth'      => '100 Mbps',
            'durasi_kontrak' => 12,
            'bulan_tahun1'   => 12,
            'otc'            => 5000000,
            'mrc'            => 10000000,
            'material'       => 1000000,
            'jasa'           => 1000000,
            'bop_pct'        => 10,
            'lifetime'       => 1,
            'om_pct'         => 10,
            'tax_pct'        => 22,
            'wacc_pct'       => 10,
            'rdm_otc_pct'    => 0,
            'rdm_mrc_pct'    => 0,
        ];

        $response = $this->actingAs($user)->postJson('/api/simulasi/save-studio', $payload);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'error' => 'Simulasi ini telah berstatus Disetujui (Approved) oleh RSO dan tidak dapat diubah lagi.',
        ]);

        $this->assertDatabaseHas('simulasi_aki', [
            'id' => $simulasi->id,
            'status' => SimulasiAki::STATUS_APPROVED,
            'nama_proyek' => 'Locked Approved Project',
        ]);
    }

    public function test_admin_cannot_demote_themselves(): void
    {
        $admin = User::factory()->create(['is_approved' => true]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->put("/admin/users/{$admin->id}/role", [
            'role' => 'sales_marketing',
        ]);

        $response->assertSessionHas('error', 'Anda tidak dapat mengubah role akun administrator Anda sendiri demi mencegah penguncian sistem (admin lockout).');
        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }

    public function test_security_headers_middleware_allows_unpkg_in_production(): void
    {
        $this->app->detectEnvironment(fn() => 'production');

        $middleware = new SecurityHeaders();
        $request = Request::create('/test', 'GET');
        $response = $middleware->handle($request, fn($req) => new Response('OK'));

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);
        $this->assertStringContainsString('https://unpkg.com', $csp);
    }

    public function test_price_list_lookup_requires_wilayah_before_returning_price(): void
    {
        $user = User::factory()->create(['is_approved' => true]);
        $user->assignRole('sales_marketing');

        \App\Models\PriceList::create([
            'kategori'         => 'connectivity',
            'paketisasi'       => 'Astinet',
            'bandwidth'        => '100 Mbps',
            'wilayah'          => 'Jawa',
            'tarif_normal_mrc' => 5000000,
            'tarif_normal_otc' => 2500000,
        ]);

        // Tanpa wilayah: harga TIDAK boleh muncul (found: false)
        $respWithoutWilayah = $this->actingAs($user)->getJson('/api/price-list/lookup?paket=Astinet&bandwidth=100+Mbps');
        $respWithoutWilayah->assertStatus(200);
        $respWithoutWilayah->assertJson(['found' => false]);

        // Dengan wilayah: harga katalog baru muncul
        $respWithWilayah = $this->actingAs($user)->getJson('/api/price-list/lookup?paket=Astinet&bandwidth=100+Mbps&wilayah=Jawa');
        $respWithWilayah->assertStatus(200);
        $respWithWilayah->assertJson([
            'found'            => true,
            'tarif_normal_mrc' => 5000000,
        ]);
    }

    public function test_marketing_complete_fails_validation_if_wilayah_is_empty(): void
    {
        $marketing = User::factory()->create(['is_approved' => true]);
        $marketing->assignRole('sales_marketing');

        $simulasi = SimulasiAki::create([
            'user_id_pihak_a' => $marketing->id,
            'status'          => SimulasiAki::STATUS_MENUNGGU_PIHAK_B,
            'nama_proyek'     => 'Project Pending Mkt',
            'lokasi'          => 'Bandung',
            'input_json'      => [
                'material' => 1000000,
                'jasa'     => 1000000,
            ],
        ]);

        $payloadWithoutWilayah = [
            'kategori'       => 'connectivity',
            'paketisasi'     => 'Astinet',
            'bandwidth'      => '100 Mbps',
            // 'wilayah' sengaja dikosongkan
            'otc'            => 1000000,
            'mrc'            => 5000000,
            'durasi_kontrak' => 12,
            'bulan_tahun1'   => 12,
        ];

        $response = $this->actingAs($marketing)
            ->post("/simulasi/{$simulasi->id}/complete-marketing", $payloadWithoutWilayah);

        $response->assertSessionHasErrors('wilayah');
    }
}
