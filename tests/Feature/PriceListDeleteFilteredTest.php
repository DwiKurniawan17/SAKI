<?php

namespace Tests\Feature;

use App\Models\PriceList;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceListDeleteFilteredTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_delete_all_records_of_a_specific_filtered_package(): void
    {
        $admin = User::factory()->create(['is_approved' => true]);
        $admin->assignRole('admin');

        // Buat 3 item Astinet
        PriceList::create([
            'paketisasi' => 'Astinet',
            'bandwidth' => '10 Mbps',
            'wilayah' => 'Jabodetabek',
            'kategori' => 'connectivity',
            'tarif_normal_mrc' => 10000000,
        ]);
        PriceList::create([
            'paketisasi' => 'Astinet',
            'bandwidth' => '20 Mbps',
            'wilayah' => 'Jawa',
            'kategori' => 'connectivity',
            'tarif_normal_mrc' => 18000000,
        ]);

        // Buat 2 item Indibiz (tidak boleh terhapus)
        PriceList::create([
            'paketisasi' => 'Indibiz',
            'bandwidth' => '50 Mbps',
            'wilayah' => 'Jabodetabek',
            'kategori' => 'connectivity',
            'tarif_normal_mrc' => 500000,
        ]);
        PriceList::create([
            'paketisasi' => 'Indibiz',
            'bandwidth' => '100 Mbps',
            'wilayah' => 'Jawa',
            'kategori' => 'connectivity',
            'tarif_normal_mrc' => 900000,
        ]);

        $this->assertEquals(4, PriceList::count());

        // Request hapus hanya paket Astinet
        $response = $this->actingAs($admin)
            ->post(route('admin.price-list.delete-filtered'), [
                'paket' => 'Astinet',
            ]);

        $response->assertRedirect(route('admin.price-list'));
        $response->assertSessionHas('status');

        // Verifikasi: Astinet terhapus, Indibiz tetap ada
        $this->assertEquals(2, PriceList::count());
        $this->assertEquals(0, PriceList::where('paketisasi', 'Astinet')->count());
        $this->assertEquals(2, PriceList::where('paketisasi', 'Indibiz')->count());
    }

    public function test_admin_can_delete_by_filtered_package_and_wilayah(): void
    {
        $admin = User::factory()->create(['is_approved' => true]);
        $admin->assignRole('admin');

        PriceList::create([
            'paketisasi' => 'Astinet',
            'bandwidth' => '10 Mbps',
            'wilayah' => 'Jabodetabek',
            'kategori' => 'connectivity',
            'tarif_normal_mrc' => 10000000,
        ]);
        PriceList::create([
            'paketisasi' => 'Astinet',
            'bandwidth' => '20 Mbps',
            'wilayah' => 'Jawa',
            'kategori' => 'connectivity',
            'tarif_normal_mrc' => 18000000,
        ]);

        // Hapus hanya Astinet di Jabodetabek
        $response = $this->actingAs($admin)
            ->post(route('admin.price-list.delete-filtered'), [
                'paket' => 'Astinet',
                'wilayah' => 'Jabodetabek',
            ]);

        $response->assertRedirect(route('admin.price-list'));
        $this->assertEquals(1, PriceList::count());
        $this->assertEquals(1, PriceList::where('wilayah', 'Jawa')->count());
    }

    public function test_delete_filtered_requires_at_least_one_filter(): void
    {
        $admin = User::factory()->create(['is_approved' => true]);
        $admin->assignRole('admin');

        PriceList::create([
            'paketisasi' => 'Astinet',
            'bandwidth' => '10 Mbps',
            'wilayah' => 'Jabodetabek',
            'kategori' => 'connectivity',
            'tarif_normal_mrc' => 10000000,
        ]);

        // Kirim request tanpa parameter filter sama sekali
        $response = $this->actingAs($admin)
            ->post(route('admin.price-list.delete-filtered'), []);

        $response->assertSessionHas('error');
        $this->assertEquals(1, PriceList::count());
    }

    public function test_non_admin_cannot_delete_filtered_prices(): void
    {
        $sales = User::factory()->create(['is_approved' => true]);
        $sales->assignRole('sales_marketing');

        $response = $this->actingAs($sales)
            ->post(route('admin.price-list.delete-filtered'), [
                'paket' => 'Astinet',
            ]);

        $response->assertForbidden();
    }
}
