<?php

namespace Tests\Feature;

use App\Models\ParameterRdm;
use App\Models\SimulasiAki;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingDefaultOtcTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_parameter_rdm_defaults_to_2500000_for_otc(): void
    {
        $rdm = ParameterRdm::getActive();

        $this->assertEquals(2500000.00, $rdm->default_otc);
    }

    public function test_admin_can_update_annual_default_otc(): void
    {
        $admin = User::factory()->create(['is_approved' => true]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post('/admin/parameter/otc', [
            'default_otc' => 3000000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertEquals(3000000.00, ParameterRdm::getActive()->default_otc);
    }

    public function test_non_admin_cannot_update_default_otc(): void
    {
        $mkt = User::factory()->create(['is_approved' => true]);
        $mkt->assignRole('sales_marketing');

        $response = $this->actingAs($mkt)->post('/admin/parameter/otc', [
            'default_otc' => 3000000,
        ]);

        $response->assertStatus(403);
    }

    public function test_marketing_sees_default_otc_in_form_when_new(): void
    {
        $ed = User::factory()->create(['is_approved' => true]);
        $ed->assignRole('engineering_capex');

        $mkt = User::factory()->create(['is_approved' => true]);
        $mkt->assignRole('sales_marketing');

        $simulasi = SimulasiAki::create([
            'user_id_pihak_a' => $ed->id,
            'status'          => SimulasiAki::STATUS_MENUNGGU_PIHAK_B,
            'nama_proyek'     => 'Proyek Uji OTC',
            'lokasi'          => 'Jakarta',
            'paketisasi'      => 'Astinet',
            'bandwidth'       => '50 Mbps',
            'input_json'      => [
                'material' => 10000000,
                'jasa'     => 5000000,
            ],
        ]);

        $response = $this->actingAs($mkt)->get("/simulasi/{$simulasi->id}");

        $response->assertStatus(200);
        $response->assertSee('2.500.000');
        $response->assertSee('Standar: Rp 2.500.000');
    }

    public function test_marketing_can_edit_otc_to_custom_value(): void
    {
        $ed = User::factory()->create(['is_approved' => true]);
        $ed->assignRole('engineering_capex');

        $mkt = User::factory()->create(['is_approved' => true]);
        $mkt->assignRole('sales_marketing');

        $simulasi = SimulasiAki::create([
            'user_id_pihak_a' => $ed->id,
            'status'          => SimulasiAki::STATUS_MENUNGGU_PIHAK_B,
            'nama_proyek'     => 'Proyek Nego OTC',
            'lokasi'          => 'Jakarta',
            'paketisasi'      => 'Astinet',
            'bandwidth'       => '50 Mbps',
            'input_json'      => [
                'material' => 10000000,
                'jasa'     => 5000000,
            ],
        ]);

        \Illuminate\Support\Facades\Http::fake([
            '*/api/calculate' => \Illuminate\Support\Facades\Http::response([
                'npv' => 5000000,
                'irr' => 20.5,
                'mirr' => 18.0,
                'payback_period' => 2.1,
                'bcr' => 1.5,
                'layak' => true,
                'evaluasi' => 'LAYAK',
                'ringkasan' => [],
            ], 200),
        ]);

        // Marketing inputs custom negotiated OTC = 1.750.000
        $response = $this->actingAs($mkt)->post("/simulasi/{$simulasi->id}/complete-marketing", [
            'kategori'       => 'connectivity',
            'paketisasi'     => 'Astinet',
            'wilayah'        => 'Jabodetabek',
            'bandwidth'      => '50 Mbps',
            'durasi_kontrak' => 36,
            'bulan_tahun1'   => 6,
            'otc'            => 1750000,
            'mrc'            => 15000000,
        ]);

        $response->assertRedirect("/simulasi/{$simulasi->id}");

        $simulasi->refresh();
        $this->assertEquals(1750000, $simulasi->input_json['otc']);
    }
}
