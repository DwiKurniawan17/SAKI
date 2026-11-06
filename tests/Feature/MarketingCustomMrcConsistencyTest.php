<?php

namespace Tests\Feature;

use App\Models\ParameterRdm;
use App\Models\PriceList;
use App\Models\SimulasiAki;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingCustomMrcConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_marketing_can_submit_custom_discounted_mrc(): void
    {
        $ed = User::factory()->create(['is_approved' => true]);
        $ed->assignRole('engineering_capex');

        $mkt = User::factory()->create(['is_approved' => true]);
        $mkt->assignRole('sales_marketing');

        // Create catalog price list: Astinet 50 Mbps in Jabodetabek = 10,000,000
        PriceList::create([
            'kategori'         => 'connectivity',
            'paketisasi'       => 'Astinet',
            'bandwidth'        => '50 Mbps',
            'wilayah'          => 'Jabodetabek',
            'tarif_normal_mrc' => 10000000,
        ]);

        $simulasi = SimulasiAki::create([
            'user_id_pihak_a' => $ed->id,
            'status'          => SimulasiAki::STATUS_MENUNGGU_PIHAK_B,
            'nama_proyek'     => 'Proyek Astinet Diskon',
            'lokasi'          => 'Jakarta Selatan',
            'paketisasi'      => 'Astinet',
            'bandwidth'       => '50 Mbps',
            'input_json'      => [
                'nama_proyek' => 'Proyek Astinet Diskon',
                'lokasi'      => 'Jakarta Selatan',
                'material'    => 5000000,
                'jasa'        => 3000000,
                'capex'       => 8000000,
            ],
        ]);

        // Marketing inputs custom MRC: 9,000,000 (10% discount from 10,000,000)
        $response = $this->actingAs($mkt)->post("/simulasi/{$simulasi->id}/complete-marketing", [
            'kategori'         => 'connectivity',
            'paketisasi'       => 'Astinet',
            'bandwidth'        => '50 Mbps',
            'wilayah'          => 'Jabodetabek',
            'otc'              => 2500000,
            'mrc'              => 9000000,
            'tarif_normal_mrc' => 10000000,
            'durasi_kontrak'   => 48,
            'bulan_tahun1'     => 6,
        ]);

        $response->assertRedirect("/simulasi/{$simulasi->id}");

        $simulasi->refresh();
        $this->assertEquals(SimulasiAki::STATUS_MENUNGGU_REVIEW, $simulasi->status);
        $this->assertEquals(9000000.00, $simulasi->mrc);
        $this->assertEquals(10000000.00, $simulasi->input_json['tarif_normal_mrc']);
        $this->assertEquals(1000000.00, $simulasi->input_json['diskon_nominal']);
        $this->assertEquals(10.0, $simulasi->input_json['diskon_pct']);
    }

    public function test_views_contain_data_currency_and_handle_form_change(): void
    {
        $ed = User::factory()->create(['is_approved' => true]);
        $ed->assignRole('engineering_capex');

        $mkt = User::factory()->create(['is_approved' => true]);
        $mkt->assignRole('sales_marketing');

        $simulasi = SimulasiAki::create([
            'user_id_pihak_a' => $ed->id,
            'status'          => SimulasiAki::STATUS_MENUNGGU_PIHAK_B,
            'nama_proyek'     => 'Proyek Test View',
            'lokasi'          => 'Bandung',
            'paketisasi'      => 'Astinet',
            'bandwidth'       => '10 Mbps',
            'input_json'      => [
                'nama_proyek' => 'Proyek Test View',
                'lokasi'      => 'Bandung',
                'material'    => 1000000,
                'jasa'        => 500000,
                'capex'       => 1500000,
            ],
        ]);

        $response = $this->actingAs($mkt)->get("/simulasi/{$simulasi->id}");
        $response->assertOk();
        $response->assertSee('handleFormChange($event)', false);
        $response->assertSee('name="mrc_display"', false);
        $response->assertSee('name="otc_display"', false);
        $response->assertSee('data-currency="true"', false);
        $response->assertSee('x-on:change.stop', false);
    }
}
