<?php

namespace Tests\Feature;

use App\Models\PriceList;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceListUpdateTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_price_update_tracks_old_and_new_price_specifically_for_updated_item(): void
    {
        $admin = User::factory()->create(['is_approved' => true]);
        $admin->assignRole('admin');

        // 1. Buat 2 item tarif Astinet: 10 Mbps (harga 11.000.000) dan 20 Mbps (harga 20.000.000)
        $item10Mbps = PriceList::create([
            'paketisasi' => 'Astinet',
            'bandwidth' => '10 Mbps',
            'wilayah' => 'Jabodetabek',
            'kategori' => 'connectivity',
            'tarif_normal_mrc' => 11000000,
            'tarif_lama_mrc' => null,
        ]);

        $item20Mbps = PriceList::create([
            'paketisasi' => 'Astinet',
            'bandwidth' => '20 Mbps',
            'wilayah' => 'Jabodetabek',
            'kategori' => 'connectivity',
            'tarif_normal_mrc' => 20000000,
            'tarif_lama_mrc' => null,
        ]);

        // 2. Admin update hanya tarif 10 Mbps dari 11.000.000 menjadi 10.000.000
        $response = $this->actingAs($admin)
            ->patch(route('admin.price-list.update', $item10Mbps), [
                'tarif_normal_mrc' => 10000000,
                'keterangan' => 'Update penyesuaian tarif Q4',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('price_changed_ids', [$item10Mbps->id]);

        // Refresh model dari database
        $item10Mbps->refresh();
        $item20Mbps->refresh();

        // 3. Pastikan item 10 Mbps mencatat tarif lama 11.000.000 dan tarif baru 10.000.000
        $this->assertEquals(11000000, $item10Mbps->tarif_lama_mrc);
        $this->assertEquals(10000000, $item10Mbps->tarif_normal_mrc);

        // 4. Pastikan item 20 Mbps TIDAK berubah dan tidak memiliki tarif lama
        $this->assertNull($item20Mbps->tarif_lama_mrc);
        $this->assertEquals(20000000, $item20Mbps->tarif_normal_mrc);

        // 5. Akses halaman price-list dan verifikasi tag update hanya ada pada item 10 Mbps
        $viewResponse = $this->actingAs($admin)->get(route('admin.price-list'));
        $viewResponse->assertOk();

        // Tag "Update Tarif" harus muncul untuk item yang diupdate
        $viewResponse->assertSee('Update Tarif');
        // Angka tarif lama dan tarif baru harus dirender
        $viewResponse->assertSee('11.000.000');
        $viewResponse->assertSee('10.000.000');
        // Badge persentase turun tarif (misal -9.1%)
        $viewResponse->assertSee('Turun');
    }

    public function test_updating_with_same_price_does_not_tag_as_updated(): void
    {
        $admin = User::factory()->create(['is_approved' => true]);
        $admin->assignRole('admin');

        $item = PriceList::create([
            'paketisasi' => 'Astinet',
            'bandwidth' => '50 Mbps',
            'wilayah' => 'Jawa',
            'kategori' => 'connectivity',
            'tarif_normal_mrc' => 35000000,
            'tarif_lama_mrc' => null,
        ]);

        // Update dengan harga yang sama
        $this->actingAs($admin)
            ->patch(route('admin.price-list.update', $item), [
                'tarif_normal_mrc' => 35000000,
                'keterangan' => 'Keterangan baru saja',
            ]);

        $item->refresh();

        // tarif_lama_mrc harus tetap null karena harga tidak berubah
        $this->assertNull($item->tarif_lama_mrc);
        $this->assertEquals(35000000, $item->tarif_normal_mrc);
    }

    public function test_import_excel_tracks_price_changes_accurately_only_for_changed_rows(): void
    {
        $admin = User::factory()->create(['is_approved' => true]);
        $admin->assignRole('admin');

        // Existing data: Astinet 10 Mbps (11.000.000) dan Astinet 20 Mbps (20.000.000)
        $item10 = PriceList::create([
            'paketisasi' => 'Astinet',
            'bandwidth' => '10 Mbps',
            'wilayah' => 'Jabodetabek',
            'kategori' => 'connectivity',
            'tarif_normal_mrc' => 11000000,
            'tarif_lama_mrc' => null,
        ]);

        $item20 = PriceList::create([
            'paketisasi' => 'Astinet',
            'bandwidth' => '20 Mbps',
            'wilayah' => 'Jabodetabek',
            'kategori' => 'connectivity',
            'tarif_normal_mrc' => 20000000,
            'tarif_lama_mrc' => null,
        ]);

        // Buat file Excel temporer
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Astinet');

        // Header: Bandwidth | Jabodetabek
        $sheet->setCellValue('A1', 'Bandwidth');
        $sheet->setCellValue('B1', 'Jabodetabek');

        // Row 1: 10 Mbps diubah jadi 10.000.000 (harga lama 11.000.000)
        $sheet->setCellValue('A2', '10 Mbps');
        $sheet->setCellValue('B2', 10000000);

        // Row 2: 20 Mbps tetap 20.000.000 (tidak ada perubahan harga)
        $sheet->setCellValue('A3', '20 Mbps');
        $sheet->setCellValue('B3', 20000000);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_excel_') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempPath);

        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $tempPath,
            'price_list_astinet.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($admin)->post(route('admin.price-list.import'), [
            'excel_file' => $uploadedFile,
            'paket_default' => 'Astinet',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $item10->refresh();
        $item20->refresh();

        // Item 10 Mbps: harga berubah dari 11jt ke 10jt
        $this->assertEquals(11000000, $item10->tarif_lama_mrc);
        $this->assertEquals(10000000, $item10->tarif_normal_mrc);

        // Item 20 Mbps: harga tetap 20jt, tarif_lama_mrc harus null
        $this->assertNull($item20->tarif_lama_mrc);
        $this->assertEquals(20000000, $item20->tarif_normal_mrc);

        // Bersihkan temp file
        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
    }

    public function test_price_list_view_displays_updated_at_timestamp_column_and_modal(): void
    {
        $admin = User::factory()->create(['is_approved' => true]);
        $admin->assignRole('admin');

        $item = PriceList::create([
            'paketisasi' => 'Astinet',
            'bandwidth' => '100 Mbps',
            'wilayah' => 'Jabodetabek',
            'kategori' => 'connectivity',
            'tarif_normal_mrc' => 45000000,
            'tarif_lama_mrc' => 50000000,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.price-list'));
        $response->assertOk();
        $response->assertSee('Terakhir Diupdate');
        $response->assertSee('WIB');
        $response->assertSee('modalUpdatedAt');
    }

    public function test_api_lookup_returns_updated_at_timestamp(): void
    {
        $user = User::factory()->create(['is_approved' => true]);
        $user->assignRole('sales_marketing');

        $item = PriceList::create([
            'paketisasi' => 'Astinet',
            'bandwidth' => '10 Mbps',
            'wilayah' => 'Jabodetabek',
            'kategori' => 'connectivity',
            'tarif_normal_mrc' => 10000000,
        ]);

        $response = $this->actingAs($user)->getJson(route('price-list.lookup', [
            'paket' => 'Astinet',
            'bandwidth' => '10 Mbps',
            'wilayah' => 'Jabodetabek',
        ]));

        $response->assertOk();
        $response->assertJson([
            'found' => true,
            'tarif_normal_mrc' => 10000000,
        ]);
        $this->assertNotNull($response->json('updated_at'));
        $this->assertStringContainsString('WIB', $response->json('updated_at'));
        $this->assertNotNull($response->json('updated_at_human'));
    }

    public function test_rso_simulation_review_displays_catalog_and_commercial_update_timestamp(): void
    {
        $admin = User::factory()->create(['is_approved' => true]);
        $admin->assignRole('admin');

        $marketing = User::factory()->create(['name' => 'Marketing User', 'is_approved' => true]);
        $marketing->assignRole('sales_marketing');

        $catalogItem = PriceList::create([
            'paketisasi' => 'Astinet',
            'bandwidth' => '50 Mbps',
            'wilayah' => 'Jabodetabek',
            'kategori' => 'connectivity',
            'tarif_normal_mrc' => 30000000,
        ]);

        $simulasi = \App\Models\SimulasiAki::create([
            'user_id_pihak_a' => $admin->id,
            'user_id_pihak_b' => $marketing->id,
            'nama_proyek' => 'Proyek Uji RSO Update Tarif',
            'lokasi' => 'Jakarta',
            'paketisasi' => 'Astinet',
            'bandwidth' => '50 Mbps',
            'status' => \App\Models\SimulasiAki::STATUS_MENUNGGU_REVIEW,
            'input_json' => [
                'nama_proyek' => 'Proyek Uji RSO Update Tarif',
                'lokasi' => 'Jakarta',
                'paketisasi' => 'Astinet',
                'bandwidth' => '50 Mbps',
                'wilayah' => 'Jabodetabek',
                'otc' => 2500000,
                'mrc' => 28000000,
                'tarif_normal_mrc' => 30000000,
                'diskon_pct' => 6.67,
                'material' => 10000000,
                'jasa' => 5000000,
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('simulasi.show', $simulasi));
        $response->assertSee('Katalog RSO diupdate:');
        $response->assertSee('Diupdate Komersial (MKT):');
    }

    public function test_sisa_margin_calculation_in_simulation_views(): void
    {
        $admin = User::factory()->create(['is_approved' => true]);
        $admin->assignRole('admin');

        $marketing = User::factory()->create(['is_approved' => true]);
        $marketing->assignRole('sales_marketing');

        $simulasi = \App\Models\SimulasiAki::create([
            'user_id_pihak_a' => $admin->id,
            'user_id_pihak_b' => $marketing->id,
            'nama_proyek' => 'Proyek Uji Sisa Margin',
            'lokasi' => 'Bandung',
            'paketisasi' => 'Astinet',
            'bandwidth' => '100 Mbps',
            'status' => \App\Models\SimulasiAki::STATUS_MENUNGGU_REVIEW,
            'input_json' => [
                'nama_proyek' => 'Proyek Uji Sisa Margin',
                'lokasi' => 'Bandung',
                'paketisasi' => 'Astinet',
                'bandwidth' => '100 Mbps',
                'wilayah' => 'Jabar',
                'otc' => 1000000,
                'mrc' => 9000000,
                'tarif_normal_mrc' => 10000000,
                'diskon_pct' => 10.0,
                'material' => 5000000,
                'jasa' => 2000000,
            ],
        ]);

        // Verifikasi di halaman show
        $resShow = $this->actingAs($marketing)->get(route('simulasi.show', $simulasi));
        $resShow->assertOk();
        $resShow->assertSee('const sisa = (this.rdmMrcPct || 0) - (parseFloat(this.diskonPct) || 0);', false);
        $resShow->assertSee('Sisa Margin Profit: ', false);

        // Ubah status ke revisi untuk mengakses halaman edit
        $simulasi->update(['status' => \App\Models\SimulasiAki::STATUS_REVISI]);

        // Verifikasi di halaman edit
        $resEdit = $this->actingAs($marketing)->get(route('simulasi.edit', $simulasi));
        $resEdit->assertOk();
        $resEdit->assertSee('const sisa = (this.rdmMrcPct || 0) - (parseFloat(this.diskonPct) || 0);', false);
        $resEdit->assertSee('Sisa Margin Profit: ', false);
    }
}
