<?php

namespace Tests\Feature;

use App\Models\PriceList;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PriceListGlobalDomestikTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_can_import_two_row_header_with_regions_and_global_domestik(): void
    {
        $admin = User::factory()->create(['is_approved' => true]);
        $admin->assignRole('admin');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Astinet');

        // Header Row 1: Merged Regions
        $sheet->setCellValue('A1', 'Bandwidth');
        $sheet->mergeCells('C1:D1');
        $sheet->setCellValue('C1', 'JaBoDeTaBek');
        $sheet->mergeCells('E1:F1');
        $sheet->setCellValue('E1', 'Jawa');
        $sheet->mergeCells('G1:H1');
        $sheet->setCellValue('G1', 'Sumatera');
        $sheet->mergeCells('I1:J1');
        $sheet->setCellValue('I1', 'Kalimantan & KTI');

        // Header Row 2: Sub-types Global & Domestik
        $sheet->setCellValue('A2', 'Bandwidth');
        $sheet->setCellValue('C2', 'Global');
        $sheet->setCellValue('D2', 'Domestik');
        $sheet->setCellValue('E2', 'Global');
        $sheet->setCellValue('F2', 'Domestik');
        $sheet->setCellValue('G2', 'Global');
        $sheet->setCellValue('H2', 'Domestik');
        $sheet->setCellValue('I2', 'Global');
        $sheet->setCellValue('J2', 'Domestik');

        // Data Row 1: 10 Mbps
        $sheet->setCellValue('A3', '10 Mbps');
        $sheet->setCellValue('C3', '15.000.000');
        $sheet->setCellValue('D3', '10.000.000');
        $sheet->setCellValue('E3', '16.000.000');
        $sheet->setCellValue('F3', '11.000.000');
        $sheet->setCellValue('G3', '17.000.000');
        $sheet->setCellValue('H3', '12.000.000');
        $sheet->setCellValue('I3', '18.500.000');
        $sheet->setCellValue('J3', '13.500.000');

        $tempFile = tempnam(sys_get_temp_dir(), 'test_excel_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        $uploadedFile = new UploadedFile(
            $tempFile,
            'price_list_global_domestik.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($admin)
            ->post(route('admin.price-list.import'), [
                'excel_file' => $uploadedFile,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.price-list', ['sort' => 'terbaru']));

        // Harus ada 8 record (4 wilayah x 2 varian: Global & Domestik)
        $this->assertEquals(8, PriceList::count());

        // Verifikasi Jabodetabek Global
        $jaboGlobal = PriceList::where('paketisasi', 'Astinet Global')
            ->where('bandwidth', '10 Mbps')
            ->where('wilayah', 'Jabodetabek')
            ->first();
        $this->assertNotNull($jaboGlobal, 'Jabodetabek Global harus terdaftar dengan benar.');
        $this->assertEquals(15000000, $jaboGlobal->tarif_normal_mrc);
        $this->assertEquals('Tipe: Global', $jaboGlobal->keterangan);

        // Verifikasi Jabodetabek Domestik
        $jaboDomestik = PriceList::where('paketisasi', 'Astinet Domestik')
            ->where('bandwidth', '10 Mbps')
            ->where('wilayah', 'Jabodetabek')
            ->first();
        $this->assertNotNull($jaboDomestik, 'Jabodetabek Domestik harus terdaftar dengan benar.');
        $this->assertEquals(10000000, $jaboDomestik->tarif_normal_mrc);
        $this->assertEquals('Tipe: Domestik', $jaboDomestik->keterangan);

        // Verifikasi Jawa Global
        $jawaGlobal = PriceList::where('paketisasi', 'Astinet Global')
            ->where('bandwidth', '10 Mbps')
            ->where('wilayah', 'Jawa')
            ->first();
        $this->assertNotNull($jawaGlobal);
        $this->assertEquals(16000000, $jawaGlobal->tarif_normal_mrc);

        // Verifikasi Jawa Domestik
        $jawaDomestik = PriceList::where('paketisasi', 'Astinet Domestik')
            ->where('bandwidth', '10 Mbps')
            ->where('wilayah', 'Jawa')
            ->first();
        $this->assertNotNull($jawaDomestik);
        $this->assertEquals(11000000, $jawaDomestik->tarif_normal_mrc);

        // Verifikasi Kalimantan & KTI Global
        $ktiGlobal = PriceList::where('paketisasi', 'Astinet Global')
            ->where('bandwidth', '10 Mbps')
            ->where('wilayah', 'Kalimantan / KTI')
            ->first();
        $this->assertNotNull($ktiGlobal);
        $this->assertEquals(18500000, $ktiGlobal->tarif_normal_mrc);

        // Verifikasi Kalimantan & KTI Domestik
        $ktiDomestik = PriceList::where('paketisasi', 'Astinet Domestik')
            ->where('bandwidth', '10 Mbps')
            ->where('wilayah', 'Kalimantan / KTI')
            ->first();
        $this->assertNotNull($ktiDomestik);
        $this->assertEquals(13500000, $ktiDomestik->tarif_normal_mrc);

        @unlink($tempFile);
    }
}
