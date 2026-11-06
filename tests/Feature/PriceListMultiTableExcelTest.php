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

class PriceListMultiTableExcelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_can_import_multi_table_with_best_effort_and_critical_in_single_sheet(): void
    {
        $admin = User::factory()->create(['is_approved' => true]);
        $admin->assignRole('admin');

        // 1. Buat spreadsheet dengan format persis gambar user:
        // Tabel 1: BEST EFFORT
        // Tabel 2: CRITICAL (di sheet yang sama)
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Astinet');

        // --- TABEL 1: BEST EFFORT ---
        $sheet->setCellValue('A1', 'BEST EFFORT');

        $sheet->setCellValue('A2', 'Bandwidth');
        $sheet->mergeCells('C2:I2');
        $sheet->setCellValue('C2', 'BEST EFFORT');

        $sheet->setCellValue('A3', 'Bandwidth');
        $sheet->setCellValue('B3', 'Kbps');
        $sheet->setCellValue('C3', 'JABODETABEK');
        $sheet->setCellValue('D3', 'JABAR & JATENG');
        $sheet->setCellValue('E3', 'JATIM & BALNUS');
        $sheet->setCellValue('F3', 'SUMATRA');
        $sheet->setCellValue('G3', 'KALIMANTAN');
        $sheet->setCellValue('H3', 'SULAWESI');
        $sheet->setCellValue('I3', 'PALMAL');

        // Baris data 1: 32 Kbps
        $sheet->setCellValue('A4', '32');
        $sheet->setCellValue('B4', 'Kbps');
        $sheet->setCellValue('C4', '634.000');
        $sheet->setCellValue('D4', '685.000');
        $sheet->setCellValue('E4', '685.000');
        $sheet->setCellValue('F4', '755.000');
        $sheet->setCellValue('G4', '838.000');
        $sheet->setCellValue('H4', '846.000');
        $sheet->setCellValue('I4', '973.000');

        // --- TABEL 2: CRITICAL (di sheet yang sama) ---
        $sheet->setCellValue('A7', 'CRITICAL');

        $sheet->setCellValue('A8', 'Bandwidth');
        $sheet->mergeCells('C8:I8');
        $sheet->setCellValue('C8', 'CRITICAL');

        $sheet->setCellValue('A9', 'Bandwidth');
        $sheet->setCellValue('B9', 'kbps');
        $sheet->setCellValue('C9', 'JABODETABEK');
        $sheet->setCellValue('D9', 'JABAR & JATENG');
        $sheet->setCellValue('E9', 'JATIM & BALNUS');
        $sheet->setCellValue('F9', 'SUMATRA');
        $sheet->setCellValue('G9', 'KALIMANTAN');
        $sheet->setCellValue('H9', 'SULAWESI');
        $sheet->setCellValue('I9', 'PALMAL');

        // Baris data 2: 32 kbps (harga critical lebih tinggi)
        $sheet->setCellValue('A10', '32');
        $sheet->setCellValue('B10', 'kbps');
        $sheet->setCellValue('C10', '720.000');
        $sheet->setCellValue('D10', '779.000');
        $sheet->setCellValue('E10', '779.000');
        $sheet->setCellValue('F10', '857.000');
        $sheet->setCellValue('G10', '952.000');
        $sheet->setCellValue('H10', '960.000');
        $sheet->setCellValue('I10', '1.105.000');

        // --- TABEL 3: INTERACTIVE (di sheet yang sama) ---
        $sheet->setCellValue('A13', 'Bandwidth');
        $sheet->mergeCells('C13:I13');
        $sheet->setCellValue('C13', 'INTERACTIVE');

        $sheet->setCellValue('A14', 'Bandwidth');
        $sheet->setCellValue('B14', 'Kbps');
        $sheet->setCellValue('C14', 'JABODETABEK');
        $sheet->setCellValue('D14', 'JABAR & JATENG');
        $sheet->setCellValue('E14', 'JATIM & BALNUS');
        $sheet->setCellValue('F14', 'SUMATRA');
        $sheet->setCellValue('G14', 'KALIMANTAN');
        $sheet->setCellValue('H14', 'SULAWESI');
        $sheet->setCellValue('I14', 'PALMAL');

        // Baris data 3: 32 Kbps Interactive
        $sheet->setCellValue('A15', '32');
        $sheet->setCellValue('B15', 'Kbps');
        $sheet->setCellValue('C15', '68.000');
        $sheet->setCellValue('D15', '77.000');
        $sheet->setCellValue('E15', '86.000');
        $sheet->setCellValue('F15', '97.000');
        $sheet->setCellValue('G15', '108.000');
        $sheet->setCellValue('H15', '117.000');
        $sheet->setCellValue('I15', '135.000');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_price_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $uploadedFile = new UploadedFile(
            $tempPath,
            'astinet_multi_table.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        // 2. Upload file Excel lewat controller
        $response = $this->actingAs($admin)
            ->post(route('admin.price-list.import'), [
                'excel_file' => $uploadedFile,
            ]);

        $response->assertRedirect();
        @unlink($tempPath);

        // 3. Verifikasi Database:
        // - Harus ada 21 baris (7 wilayah x 3 paket: Best Effort, Critical, Interactive)
        $this->assertEquals(21, PriceList::count());

        // Verifikasi Paket Best Effort: 32 Kbps di Jabodetabek harus Rp 634.000
        $beJabo = PriceList::where('paketisasi', 'Astinet Best Effort')
            ->where('bandwidth', '32 Kbps')
            ->where('wilayah', 'Jabodetabek')
            ->first();
        $this->assertNotNull($beJabo);
        $this->assertEquals(634000, $beJabo->tarif_normal_mrc);

        // Verifikasi Paket Best Effort: 32 Kbps di Jabar & Jateng harus Rp 685.000
        $beJabarJateng = PriceList::where('paketisasi', 'Astinet Best Effort')
            ->where('bandwidth', '32 Kbps')
            ->where('wilayah', 'Jabar & Jateng')
            ->first();
        $this->assertNotNull($beJabarJateng);
        $this->assertEquals(685000, $beJabarJateng->tarif_normal_mrc);

        // Verifikasi Paket Critical: 32 Kbps di Jabodetabek harus Rp 720.000
        $critJabo = PriceList::where('paketisasi', 'Astinet Critical')
            ->where('bandwidth', '32 Kbps')
            ->where('wilayah', 'Jabodetabek')
            ->first();
        $this->assertNotNull($critJabo);
        $this->assertEquals(720000, $critJabo->tarif_normal_mrc);

        // Verifikasi Paket Interactive: 32 Kbps di Jabodetabek harus Rp 68.000
        $interJabo = PriceList::where('paketisasi', 'Astinet Interactive')
            ->where('bandwidth', '32 Kbps')
            ->where('wilayah', 'Jabodetabek')
            ->first();
        $this->assertNotNull($interJabo);
        $this->assertEquals(68000, $interJabo->tarif_normal_mrc);

        // Verifikasi Paket Interactive: 32 Kbps di Jabar & Jateng harus Rp 77.000
        $interJateng = PriceList::where('paketisasi', 'Astinet Interactive')
            ->where('bandwidth', '32 Kbps')
            ->where('wilayah', 'Jabar & Jateng')
            ->first();
        $this->assertNotNull($interJateng);
        $this->assertEquals(77000, $interJateng->tarif_normal_mrc);

        // Verifikasi Paket Interactive: 32 Kbps di Palmal harus Rp 135.000
        $interPalmal = PriceList::where('paketisasi', 'Astinet Interactive')
            ->where('bandwidth', '32 Kbps')
            ->where('wilayah', 'Palmal (Papua & Maluku)')
            ->first();
        $this->assertNotNull($interPalmal);
        $this->assertEquals(135000, $interPalmal->tarif_normal_mrc);

        // Verifikasi Paket Critical: 32 Kbps di Palmal harus Rp 1.105.000
        $critPalmal = PriceList::where('paketisasi', 'Astinet Critical')
            ->where('bandwidth', '32 Kbps')
            ->where('wilayah', 'Palmal (Papua & Maluku)')
            ->first();
        $this->assertNotNull($critPalmal);
        $this->assertEquals(1105000, $critPalmal->tarif_normal_mrc);

        // 4. Verifikasi Pencarian Cerdas (findTarif):
        // Cari Astinet + Best Effort + Semarang (Jawa Tengah) -> harus cocok dengan Jabar & Jateng Rp 685.000
        $foundBe = PriceList::findTarif('Astinet', '32 Kbps', 'Semarang', 'Best Effort');
        $this->assertNotNull($foundBe);
        $this->assertEquals(685000, $foundBe->tarif_normal_mrc);

        // Cari Astinet Critical + Jayapura (Papua) -> harus cocok dengan Palmal Rp 1.105.000
        $foundCrit = PriceList::findTarif('Astinet Critical', '32', 'Jayapura');
        $this->assertNotNull($foundCrit);
        $this->assertEquals(1105000, $foundCrit->tarif_normal_mrc);
    }
}
