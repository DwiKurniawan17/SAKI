<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReactStudioTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_studio(): void
    {
        $response = $this->get('/simulasi-studio');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_studio(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create([
            'is_approved' => true,
        ]);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/simulasi-studio');
        $response->assertStatus(200);
        $response->assertSee('react-simulation-studio');
        $response->assertSee('Studio Simulasi');
    }

    public function test_calculate_live_endpoint_returns_financial_metrics(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create([
            'is_approved' => true,
        ]);
        $user->assignRole('admin');

        $payload = [
            'nama_proyek'    => 'Test Project React Studio',
            'lokasi'         => 'Jakarta',
            'paketisasi'     => 'Astinet',
            'bandwidth'      => '100 Mbps',
            'durasi_kontrak' => 48,
            'bulan_tahun1'   => 6,
            'otc'            => 10000000,
            'mrc'            => 25000000,
            'material'       => 50000000,
            'jasa'           => 20000000,
            'bop_pct'        => 40.0,
            'lifetime'       => 4,
            'om_pct'         => 12.0,
            'tax_pct'        => 22.0,
            'wacc_pct'       => 10.9,
            'rdm_otc_pct'    => 25.0,
            'rdm_mrc_pct'    => 30.0,
        ];

        $response = $this->actingAs($user)->postJson('/api/simulasi/calculate-live', $payload);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'npv',
            'irr',
            'pbp',
            'gpm',
            'nim',
            'layak',
            'rows',
            'grand_capex'
        ]);
    }

    public function test_non_admin_is_forbidden_from_studio(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $marketing = User::factory()->create(['is_approved' => true]);
        $marketing->assignRole('sales_marketing');

        $ed = User::factory()->create(['is_approved' => true]);
        $ed->assignRole('engineering_capex');

        // Marketing cannot access studio
        $this->actingAs($marketing)->get('/simulasi-studio')->assertStatus(403);

        // ED cannot access studio
        $this->actingAs($ed)->get('/simulasi-studio')->assertStatus(403);
    }

    public function test_non_admin_cannot_call_studio_apis(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $marketing = User::factory()->create(['is_approved' => true]);
        $marketing->assignRole('sales_marketing');

        // calculate-live forbidden
        $this->actingAs($marketing)
            ->postJson('/api/simulasi/calculate-live', [])
            ->assertStatus(403);

        // save-studio forbidden
        $this->actingAs($marketing)
            ->postJson('/api/simulasi/save-studio', [])
            ->assertStatus(403);
    }

    public function test_studio_passes_wilayah_and_initial_summary_for_existing_simulasi(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['is_approved' => true]);
        $user->assignRole('admin');

        $simulasi = \App\Models\SimulasiAki::create([
            'nama_proyek' => 'Proyek Telkom Accuracy Test',
            'customer' => 'Customer Test',
            'lokasi' => 'Jakarta Barat',
            'wilayah' => 'Regional 2',
            'kategori' => 'connectivity',
            'paketisasi' => 'Astinet',
            'bandwidth' => '100 Mbps',
            'durasi_kontrak' => 48,
            'bulan_tahun1' => 6,
            'otc' => 10000000,
            'mrc' => 25000000,
            'material' => 50000000,
            'jasa' => 20000000,
            'hasil_json' => [
                'npv' => 125000000,
                'irr' => 0.28,
                'pbp' => 2.5,
                'layak' => true,
                'fcf_list' => [-60000000, 30000000, 30000000, 30000000]
            ],
            'layak' => true,
            'status' => 'draft',
            'created_by' => $user->id,
            'user_id_pihak_b' => $user->id,
        ]);

        $response = $this->actingAs($user)->get("/simulasi/{$simulasi->id}/studio");
        $response->assertStatus(200);
        $response->assertSee('data-wilayah-list', false);
        $response->assertSee('data-initial-summary', false);
        $response->assertSee('Proyek Telkom Accuracy Test');
    }

    public function test_save_studio_accurately_persists_wilayah_and_feasibility(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['is_approved' => true]);
        $user->assignRole('admin');

        $payload = [
            'nama_proyek'    => 'Simulasi Studio Accurate Feasibility',
            'lokasi'         => 'Surabaya',
            'wilayah'        => 'Regional 5',
            'kategori'       => 'connectivity',
            'paketisasi'     => 'Astinet',
            'bandwidth'      => '50 Mbps',
            'durasi_kontrak' => 36,
            'bulan_tahun1'   => 6,
            'otc'            => 5000000,
            'mrc'            => 15000000,
            'material'       => 20000000,
            'jasa'           => 10000000,
            'bop_pct'        => 0.4,
            'lifetime'       => 4,
            'om_pct'         => 12.0,
            'tax_pct'        => 22.0,
            'wacc_pct'       => 10.9,
            'rdm_otc_pct'    => 25.0,
            'rdm_mrc_pct'    => 30.0,
        ];

        $response = $this->actingAs($user)->postJson('/api/simulasi/save-studio', $payload);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('simulasi_aki', [
            'nama_proyek' => 'Simulasi Studio Accurate Feasibility',
            'status'      => \App\Models\SimulasiAki::STATUS_MENUNGGU_REVIEW,
        ]);

        $saved = \App\Models\SimulasiAki::where('nama_proyek', 'Simulasi Studio Accurate Feasibility')->first();
        $this->assertEquals('Regional 5', $saved->input_json['wilayah']);
        $this->assertNotNull($saved->layak, 'Layak column should be accurately calculated and saved');
        $this->assertNotNull($saved->hasil_json, 'hasil_json should be saved');
        $this->assertArrayHasKey('npv', $saved->hasil_json);
        $this->assertArrayHasKey('cum_fcf', $saved->hasil_json);
    }

    public function test_simulation_detail_page_embeds_cashflow_chart_visualization(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['is_approved' => true]);
        $user->assignRole('admin');

        $simulasi = \App\Models\SimulasiAki::create([
            'nama_proyek' => 'Proyek Uji Visualisasi Detail',
            'customer' => 'Customer Test',
            'lokasi' => 'Jakarta Barat',
            'wilayah' => 'Regional 2',
            'kategori' => 'connectivity',
            'paketisasi' => 'Astinet',
            'bandwidth' => '100 Mbps',
            'durasi_kontrak' => 48,
            'bulan_tahun1' => 6,
            'otc' => 10000000,
            'mrc' => 25000000,
            'material' => 50000000,
            'jasa' => 20000000,
            'hasil_json' => [
                'npv' => 125000000,
                'irr' => 0.28,
                'pbp' => 2.5,
                'layak' => true,
                'rows' => [
                    ['year' => 1, 'revenue' => 150000000, 'free_cash_flow' => 50000000],
                    ['year' => 2, 'revenue' => 300000000, 'free_cash_flow' => 120000000]
                ],
                'fcf_list' => [-60000000, 120000000],
                'cum_fcf' => [-60000000, 60000000]
            ],
            'layak' => true,
            'status' => 'draft',
            'created_by' => $user->id,
            'user_id_pihak_b' => $user->id,
        ]);

        $response = $this->actingAs($user)->get("/simulasi/{$simulasi->id}");
        $response->assertStatus(200);
        $response->assertSee('react-cashflow-chart');
        $response->assertDontSee('Studio Simulasi');
        $response->assertDontSee('Buka di Studio (React)');
    }
}
