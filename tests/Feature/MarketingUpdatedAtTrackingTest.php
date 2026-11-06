<?php

namespace Tests\Feature;

use App\Models\PriceList;
use App\Models\SimulasiAki;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MarketingUpdatedAtTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_complete_marketing_records_marketing_updated_at(): void
    {
        Carbon::setTestNow('2026-10-20 11:30:00');

        $ed = User::factory()->create(['name' => 'Engineer Budi', 'is_approved' => true]);
        $ed->assignRole('engineering_capex');

        $mkt = User::factory()->create(['name' => 'Marketing Siti', 'is_approved' => true]);
        $mkt->assignRole('sales_marketing');

        $rso = User::factory()->create(['name' => 'RSO Denny', 'is_approved' => true]);
        $rso->assignRole('admin');

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
            'nama_proyek'     => 'Proyek Test Marketing Update',
            'lokasi'          => 'Jakarta',
            'paketisasi'      => 'Astinet',
            'bandwidth'       => '50 Mbps',
            'input_json'      => [
                'nama_proyek' => 'Proyek Test Marketing Update',
                'lokasi'      => 'Jakarta',
                'material'    => 5000000,
                'jasa'        => 3000000,
            ],
        ]);

        $this->assertNull($simulasi->marketing_updated_at);

        // Marketing completes the simulation
        $response = $this->actingAs($mkt)->post("/simulasi/{$simulasi->id}/complete-marketing", [
            'kategori'       => 'connectivity',
            'paketisasi'     => 'Astinet',
            'bandwidth'      => '50 Mbps',
            'wilayah'        => 'Jabodetabek',
            'otc'            => 2500000,
            'mrc'            => 9500000,
            'durasi_kontrak' => 48,
            'bulan_tahun1'   => 6,
        ]);

        $response->assertRedirect("/simulasi/{$simulasi->id}");

        $simulasi->refresh();
        $this->assertEquals(SimulasiAki::STATUS_MENUNGGU_REVIEW, $simulasi->status);
        $this->assertNotNull($simulasi->marketing_updated_at);
        $this->assertEquals('2026-10-20 11:30:00', $simulasi->marketing_updated_at->format('Y-m-d H:i:s'));
        $this->assertNotNull($simulasi->input_json['marketing_updated_at']);

        // RSO views the simulation detail page and sees Marketing update notice
        $rsoView = $this->actingAs($rso)->get("/simulasi/{$simulasi->id}");
        $rsoView->assertOk();
        $rsoView->assertSee('Laporan Telah Diupdate oleh Marketing', false);
        $rsoView->assertSee('Laporan Siap Direview RSO', false);
        $rsoView->assertSee('Marketing Siti', false);
        $rsoView->assertSee('20 Okt 2026, 11:30 WIB', false);
        $rsoView->assertSee('Update MKT:', false);

        // RSO views Riwayat page and sees MKT update timestamp
        $riwayatView = $this->actingAs($rso)->get(route('simulasi.riwayat'));
        $riwayatView->assertOk();
        $riwayatView->assertSee('Marketing Siti', false);
        $riwayatView->assertSee('20/10 11:30', false);

        // RSO views Dashboard and sees MKT update timestamp
        $dashView = $this->actingAs($rso)->get('/dashboard');
        $dashView->assertOk();
        $dashView->assertSee('Marketing Siti', false);
        $dashView->assertSee('20/10 11:30', false);

        Carbon::setTestNow();
    }

    public function test_marketing_update_revision_refreshes_marketing_updated_at(): void
    {
        Carbon::setTestNow('2026-10-20 09:00:00');

        $ed = User::factory()->create(['name' => 'Engineer Budi', 'is_approved' => true]);
        $ed->assignRole('engineering_capex');

        $mkt = User::factory()->create(['name' => 'Marketing Siti', 'is_approved' => true]);
        $mkt->assignRole('sales_marketing');

        $simulasi = SimulasiAki::create([
            'user_id_pihak_a'      => $ed->id,
            'user_id_pihak_b'      => $mkt->id,
            'status'               => SimulasiAki::STATUS_REVISI,
            'nama_proyek'          => 'Proyek Revisi Komersial',
            'lokasi'               => 'Surabaya',
            'paketisasi'           => 'Astinet',
            'bandwidth'            => '100 Mbps',
            'marketing_updated_at' => '2026-10-19 14:00:00',
            'input_json'           => [
                'nama_proyek'          => 'Proyek Revisi Komersial',
                'lokasi'               => 'Surabaya',
                'material'             => 10000000,
                'jasa'                 => 5000000,
                'paketisasi'           => 'Astinet',
                'bandwidth'            => '100 Mbps',
                'wilayah'              => 'Jawa',
                'otc'                  => 2500000,
                'mrc'                  => 12000000,
                'durasi_kontrak'       => 48,
                'bulan_tahun1'         => 6,
                'marketing_updated_at' => '2026-10-19T14:00:00+07:00',
            ],
        ]);

        $this->assertEquals('2026-10-19 14:00:00', $simulasi->marketing_updated_at->format('Y-m-d H:i:s'));

        // Fast forward to revision update time
        Carbon::setTestNow('2026-10-20 15:45:00');

        $response = $this->actingAs($mkt)->put("/simulasi/{$simulasi->id}", [
            'kategori'       => 'connectivity',
            'paketisasi'     => 'Astinet',
            'bandwidth'      => '100 Mbps',
            'wilayah'        => 'Jawa',
            'otc'            => 2500000,
            'mrc'            => 14000000,
            'durasi_kontrak' => 48,
            'bulan_tahun1'   => 6,
        ]);

        $response->assertRedirect("/simulasi/{$simulasi->id}");

        $simulasi->refresh();
        $this->assertEquals(SimulasiAki::STATUS_MENUNGGU_REVIEW, $simulasi->status);
        $this->assertEquals('2026-10-20 15:45:00', $simulasi->marketing_updated_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_accessor_fallback_for_legacy_records(): void
    {
        $mkt = User::factory()->create(['name' => 'Marketing Andi']);

        // Legacy record with null marketing_updated_at column but completed by marketing
        $legacy = new SimulasiAki([
            'user_id_pihak_b'      => $mkt->id,
            'status'               => SimulasiAki::STATUS_MENUNGGU_REVIEW,
            'nama_proyek'          => 'Proyek Lama',
            'marketing_updated_at' => null,
            'input_json'           => [
                'nama_proyek' => 'Proyek Lama',
            ],
        ]);
        $legacy->updated_at = Carbon::parse('2026-09-15 10:00:00');

        // Should fallback to updated_at
        $this->assertEquals('2026-09-15 10:00:00', $legacy->marketing_updated_at->format('Y-m-d H:i:s'));

        // Record still waiting for marketing (STATUS_MENUNGGU_PIHAK_B)
        $waiting = new SimulasiAki([
            'status'               => SimulasiAki::STATUS_MENUNGGU_PIHAK_B,
            'marketing_updated_at' => null,
            'input_json'           => [],
        ]);
        $this->assertNull($waiting->marketing_updated_at);
    }
}
