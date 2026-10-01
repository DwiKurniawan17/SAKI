<?php

namespace Database\Seeders;

use App\Models\PriceList;
use Illuminate\Database\Seeder;

class PriceListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Data contoh acuan awal (bisa ditimpa kapan saja lewat upload Excel).
     */
    public function run(): void
    {
        $sampleData = [
            // Astinet (contoh matriks wilayah)
            ['paketisasi' => 'Astinet', 'bandwidth' => '10 Mbps', 'wilayah' => 'Jabodetabek', 'tarif_normal_mrc' => 10000000],
            ['paketisasi' => 'Astinet', 'bandwidth' => '10 Mbps', 'wilayah' => 'Jawa',        'tarif_normal_mrc' => 11000000],
            ['paketisasi' => 'Astinet', 'bandwidth' => '10 Mbps', 'wilayah' => 'Sumatera',    'tarif_normal_mrc' => 12000000],
            ['paketisasi' => 'Astinet', 'bandwidth' => '10 Mbps', 'wilayah' => 'Kalimantan / KTI', 'tarif_normal_mrc' => 13500000],

            ['paketisasi' => 'Astinet', 'bandwidth' => '20 Mbps', 'wilayah' => 'Jabodetabek', 'tarif_normal_mrc' => 18000000],
            ['paketisasi' => 'Astinet', 'bandwidth' => '20 Mbps', 'wilayah' => 'Jawa',        'tarif_normal_mrc' => 19500000],
            ['paketisasi' => 'Astinet', 'bandwidth' => '20 Mbps', 'wilayah' => 'Sumatera',    'tarif_normal_mrc' => 21000000],
            ['paketisasi' => 'Astinet', 'bandwidth' => '20 Mbps', 'wilayah' => 'Kalimantan / KTI', 'tarif_normal_mrc' => 23000000],

            ['paketisasi' => 'Astinet', 'bandwidth' => '50 Mbps', 'wilayah' => 'Jabodetabek', 'tarif_normal_mrc' => 35000000],
            ['paketisasi' => 'Astinet', 'bandwidth' => '50 Mbps', 'wilayah' => 'Jawa',        'tarif_normal_mrc' => 38000000],
            ['paketisasi' => 'Astinet', 'bandwidth' => '50 Mbps', 'wilayah' => 'Sumatera',    'tarif_normal_mrc' => 41000000],
            ['paketisasi' => 'Astinet', 'bandwidth' => '50 Mbps', 'wilayah' => 'Kalimantan / KTI', 'tarif_normal_mrc' => 45000000],

            ['paketisasi' => 'Astinet', 'bandwidth' => '100 Mbps', 'wilayah' => 'Jabodetabek', 'tarif_normal_mrc' => 60000000],
            ['paketisasi' => 'Astinet', 'bandwidth' => '100 Mbps', 'wilayah' => 'Jawa',        'tarif_normal_mrc' => 65000000],
            ['paketisasi' => 'Astinet', 'bandwidth' => '100 Mbps', 'wilayah' => 'Sumatera',    'tarif_normal_mrc' => 70000000],
            ['paketisasi' => 'Astinet', 'bandwidth' => '100 Mbps', 'wilayah' => 'Kalimantan / KTI', 'tarif_normal_mrc' => 78000000],

            // Indibiz
            ['paketisasi' => 'Indibiz', 'bandwidth' => '50 Mbps', 'wilayah' => 'Jabodetabek', 'tarif_normal_mrc' => 1200000],
            ['paketisasi' => 'Indibiz', 'bandwidth' => '50 Mbps', 'wilayah' => 'Jawa',        'tarif_normal_mrc' => 1350000],
            ['paketisasi' => 'Indibiz', 'bandwidth' => '50 Mbps', 'wilayah' => 'Sumatera',    'tarif_normal_mrc' => 1500000],
            ['paketisasi' => 'Indibiz', 'bandwidth' => '50 Mbps', 'wilayah' => 'Kalimantan / KTI', 'tarif_normal_mrc' => 1750000],

            ['paketisasi' => 'Indibiz', 'bandwidth' => '100 Mbps', 'wilayah' => 'Jabodetabek', 'tarif_normal_mrc' => 2000000],
            ['paketisasi' => 'Indibiz', 'bandwidth' => '100 Mbps', 'wilayah' => 'Jawa',        'tarif_normal_mrc' => 2200000],
            ['paketisasi' => 'Indibiz', 'bandwidth' => '100 Mbps', 'wilayah' => 'Sumatera',    'tarif_normal_mrc' => 2400000],
            ['paketisasi' => 'Indibiz', 'bandwidth' => '100 Mbps', 'wilayah' => 'Kalimantan / KTI', 'tarif_normal_mrc' => 2700000],
        ];

        foreach ($sampleData as $item) {
            PriceList::updateOrCreate(
                [
                    'paketisasi' => $item['paketisasi'],
                    'bandwidth'  => $item['bandwidth'],
                    'wilayah'    => $item['wilayah'],
                ],
                [
                    'kategori'         => 'connectivity',
                    'tarif_normal_mrc' => $item['tarif_normal_mrc'],
                ]
            );
        }
    }
}
