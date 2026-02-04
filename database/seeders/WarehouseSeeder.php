<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // Hapus data lama (opsional, comment jika tidak ingin menghapus)
        DB::table('stock_transactions')->truncate();
        DB::table('warehouse_items')->truncate();
        DB::table('warehouse_categories')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->seedCategories();
        $this->seedItems();
        $this->seedInitialStockTransactions();
        
        $this->command->info('✓ Warehouse seeder completed successfully!');
    }

    /**
     * Seed Kategori Warehouse
     */
    private function seedCategories()
    {
        $categories = [
            [
                'name' => 'Bahan Baku Makanan',
                'units' => json_encode(['kg', 'gram', 'liter', 'ml', 'pcs', 'pack']),
            ],
            [
                'name' => 'Bahan Baku Minuman',
                'units' => json_encode(['liter', 'ml', 'bottle', 'can', 'pack']),
            ],
            [
                'name' => 'Bumbu & Rempah',
                'units' => json_encode(['kg', 'gram', 'pcs', 'sachet', 'bottle']),
            ],
            [
                'name' => 'Peralatan Dapur',
                'units' => json_encode(['pcs', 'unit', 'set', 'lusin']),
            ],
            [
                'name' => 'Peralatan Makan',
                'units' => json_encode(['pcs', 'lusin', 'set']),
            ],
            [
                'name' => 'Bahan Pembersih',
                'units' => json_encode(['liter', 'ml', 'pcs', 'pack', 'kg']),
            ],
            [
                'name' => 'Linen & Tekstil',
                'units' => json_encode(['pcs', 'lusin', 'set']),
            ],
            [
                'name' => 'Barang Konsumsi',
                'units' => json_encode(['pcs', 'pack', 'box', 'karton']),
            ],
        ];

        foreach ($categories as $category) {
            DB::table('warehouse_categories')->insert([
                'name' => $category['name'],
                'units' => $category['units'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✓ Categories seeded');
    }

    /**
     * Seed Items Warehouse dengan data realistis restoran hotel
     */
    private function seedItems()
    {
        $items = [
            // ========================================
            // BAHAN BAKU MAKANAN
            // ========================================
            [
                'code' => 'BBM001',
                'name' => 'Beras Premium',
                'category' => 'Bahan Baku Makanan',
                'unit' => 'kg',
                'min_stock' => 100,
                'current_stock' => 250,
                'cost_price' => 15000,
                'supplier' => 'PT Pangan Sejahtera',
                'notes' => 'Beras premium untuk restoran',
            ],
            [
                'code' => 'BBM002',
                'name' => 'Daging Sapi Grade A',
                'category' => 'Bahan Baku Makanan',
                'unit' => 'kg',
                'min_stock' => 30,
                'current_stock' => 75,
                'cost_price' => 145000,
                'supplier' => 'CV Karya Daging',
                'notes' => 'Daging sapi impor untuk steak',
            ],
            [
                'code' => 'BBM003',
                'name' => 'Daging Ayam Fillet',
                'category' => 'Bahan Baku Makanan',
                'unit' => 'kg',
                'min_stock' => 25,
                'current_stock' => 60,
                'cost_price' => 42000,
                'supplier' => 'CV Karya Daging',
                'notes' => 'Fillet ayam tanpa tulang',
            ],
            [
                'code' => 'BBM004',
                'name' => 'Ikan Salmon Fillet',
                'category' => 'Bahan Baku Makanan',
                'unit' => 'kg',
                'min_stock' => 15,
                'current_stock' => 35,
                'cost_price' => 185000,
                'supplier' => 'Toko Seafood Segar',
                'notes' => 'Salmon Norway fresh',
            ],
            [
                'code' => 'BBM005',
                'name' => 'Udang Jumbo',
                'category' => 'Bahan Baku Makanan',
                'unit' => 'kg',
                'min_stock' => 10,
                'current_stock' => 25,
                'cost_price' => 165000,
                'supplier' => 'Toko Seafood Segar',
                'notes' => 'Udang vaname size 30',
            ],
            [
                'code' => 'BBM006',
                'name' => 'Telur Ayam Negeri',
                'category' => 'Bahan Baku Makanan',
                'unit' => 'kg',
                'min_stock' => 20,
                'current_stock' => 50,
                'cost_price' => 28000,
                'supplier' => 'UD Sumber Telur',
                'notes' => 'Telur segar grade A',
            ],
            [
                'code' => 'BBM007',
                'name' => 'Kentang',
                'category' => 'Bahan Baku Makanan',
                'unit' => 'kg',
                'min_stock' => 30,
                'current_stock' => 80,
                'cost_price' => 18000,
                'supplier' => 'Pasar Sayur Segar',
                'notes' => 'Kentang granola',
            ],
            [
                'code' => 'BBM008',
                'name' => 'Wortel',
                'category' => 'Bahan Baku Makanan',
                'unit' => 'kg',
                'min_stock' => 15,
                'current_stock' => 40,
                'cost_price' => 12000,
                'supplier' => 'Pasar Sayur Segar',
                'notes' => 'Wortel organik',
            ],
            [
                'code' => 'BBM009',
                'name' => 'Bawang Bombay',
                'category' => 'Bahan Baku Makanan',
                'unit' => 'kg',
                'min_stock' => 10,
                'current_stock' => 30,
                'cost_price' => 35000,
                'supplier' => 'Pasar Sayur Segar',
                'notes' => 'Bawang bombay import',
            ],
            [
                'code' => 'BBM010',
                'name' => 'Tomat Segar',
                'category' => 'Bahan Baku Makanan',
                'unit' => 'kg',
                'min_stock' => 15,
                'current_stock' => 35,
                'cost_price' => 15000,
                'supplier' => 'Pasar Sayur Segar',
                'notes' => 'Tomat merah segar',
            ],
            [
                'code' => 'BBM011',
                'name' => 'Tepung Terigu Segitiga Biru',
                'category' => 'Bahan Baku Makanan',
                'unit' => 'kg',
                'min_stock' => 50,
                'current_stock' => 120,
                'cost_price' => 12500,
                'supplier' => 'Toko Bahan Kue',
                'notes' => 'Tepung protein tinggi',
            ],
            [
                'code' => 'BBM012',
                'name' => 'Gula Pasir Premium',
                'category' => 'Bahan Baku Makanan',
                'unit' => 'kg',
                'min_stock' => 40,
                'current_stock' => 100,
                'cost_price' => 14000,
                'supplier' => 'PT Pangan Sejahtera',
                'notes' => 'Gula kristal putih',
            ],
            [
                'code' => 'BBM013',
                'name' => 'Garam Halus',
                'category' => 'Bahan Baku Makanan',
                'unit' => 'kg',
                'min_stock' => 20,
                'current_stock' => 50,
                'cost_price' => 8000,
                'supplier' => 'UD Bumbu Lengkap',
                'notes' => 'Garam dapur beryodium',
            ],
            [
                'code' => 'BBM014',
                'name' => 'Minyak Goreng Curah',
                'category' => 'Bahan Baku Makanan',
                'unit' => 'liter',
                'min_stock' => 50,
                'current_stock' => 120,
                'cost_price' => 16000,
                'supplier' => 'Toko Kebutuhan Dapur',
                'notes' => 'Minyak goreng kelapa sawit',
            ],
            [
                'code' => 'BBM015',
                'name' => 'Mentega Anchor',
                'category' => 'Bahan Baku Makanan',
                'unit' => 'kg',
                'min_stock' => 10,
                'current_stock' => 25,
                'cost_price' => 85000,
                'supplier' => 'Toko Bahan Kue',
                'notes' => 'Mentega tawar untuk baking',
            ],

            // ========================================
            // BAHAN BAKU MINUMAN
            // ========================================
            [
                'code' => 'BBN001',
                'name' => 'Kopi Arabica Premium',
                'category' => 'Bahan Baku Minuman',
                'unit' => 'kg',
                'min_stock' => 10,
                'current_stock' => 30,
                'cost_price' => 125000,
                'supplier' => 'CV Kopi Nusantara',
                'notes' => 'Kopi arabica specialty grade',
            ],
            [
                'code' => 'BBN002',
                'name' => 'Teh Celup Premium',
                'category' => 'Bahan Baku Minuman',
                'unit' => 'pack',
                'min_stock' => 20,
                'current_stock' => 50,
                'cost_price' => 35000,
                'supplier' => 'Distributor Minuman',
                'notes' => 'Teh celup isi 50 pcs/pack',
            ],
            [
                'code' => 'BBN003',
                'name' => 'Susu UHT Plain',
                'category' => 'Bahan Baku Minuman',
                'unit' => 'liter',
                'min_stock' => 30,
                'current_stock' => 80,
                'cost_price' => 18000,
                'supplier' => 'Distributor Minuman',
                'notes' => 'Susu UHT full cream',
            ],
            [
                'code' => 'BBN004',
                'name' => 'Air Mineral Galon',
                'category' => 'Bahan Baku Minuman',
                'unit' => 'pcs',
                'min_stock' => 50,
                'current_stock' => 120,
                'cost_price' => 18000,
                'supplier' => 'Depot Air Mineral',
                'notes' => 'Air mineral isi ulang 19L',
            ],
            [
                'code' => 'BBN005',
                'name' => 'Sirup Marjan Mix',
                'category' => 'Bahan Baku Minuman',
                'unit' => 'bottle',
                'min_stock' => 10,
                'current_stock' => 30,
                'cost_price' => 28000,
                'supplier' => 'Distributor Minuman',
                'notes' => 'Sirup aneka rasa 460ml',
            ],
            [
                'code' => 'BBN006',
                'name' => 'Coca Cola 1.5L',
                'category' => 'Bahan Baku Minuman',
                'unit' => 'pcs',
                'min_stock' => 24,
                'current_stock' => 72,
                'cost_price' => 9500,
                'supplier' => 'Distributor Minuman',
                'notes' => 'Soft drink botol plastik',
            ],

            // ========================================
            // BUMBU & REMPAH
            // ========================================
            [
                'code' => 'BRM001',
                'name' => 'Bawang Merah',
                'category' => 'Bumbu & Rempah',
                'unit' => 'kg',
                'min_stock' => 15,
                'current_stock' => 40,
                'cost_price' => 38000,
                'supplier' => 'UD Bumbu Lengkap',
                'notes' => 'Bawang merah lokal',
            ],
            [
                'code' => 'BRM002',
                'name' => 'Bawang Putih',
                'category' => 'Bumbu & Rempah',
                'unit' => 'kg',
                'min_stock' => 10,
                'current_stock' => 30,
                'cost_price' => 42000,
                'supplier' => 'UD Bumbu Lengkap',
                'notes' => 'Bawang putih kating',
            ],
            [
                'code' => 'BRM003',
                'name' => 'Cabai Merah',
                'category' => 'Bumbu & Rempah',
                'unit' => 'kg',
                'min_stock' => 10,
                'current_stock' => 25,
                'cost_price' => 45000,
                'supplier' => 'UD Bumbu Lengkap',
                'notes' => 'Cabai merah keriting',
            ],
            [
                'code' => 'BRM004',
                'name' => 'Merica Bubuk',
                'category' => 'Bumbu & Rempah',
                'unit' => 'kg',
                'min_stock' => 5,
                'current_stock' => 15,
                'cost_price' => 95000,
                'supplier' => 'UD Bumbu Lengkap',
                'notes' => 'Black pepper powder',
            ],
            [
                'code' => 'BRM005',
                'name' => 'Kecap Manis Bango',
                'category' => 'Bumbu & Rempah',
                'unit' => 'bottle',
                'min_stock' => 12,
                'current_stock' => 36,
                'cost_price' => 18000,
                'supplier' => 'Toko Kebutuhan Dapur',
                'notes' => 'Kecap manis botol 600ml',
            ],
            [
                'code' => 'BRM006',
                'name' => 'Saus Tomat Heinz',
                'category' => 'Bumbu & Rempah',
                'unit' => 'bottle',
                'min_stock' => 10,
                'current_stock' => 30,
                'cost_price' => 32000,
                'supplier' => 'Toko Kebutuhan Dapur',
                'notes' => 'Tomato sauce 567g',
            ],
            [
                'code' => 'BRM007',
                'name' => 'Saus Sambal ABC',
                'category' => 'Bumbu & Rempah',
                'unit' => 'bottle',
                'min_stock' => 10,
                'current_stock' => 30,
                'cost_price' => 15000,
                'supplier' => 'Toko Kebutuhan Dapur',
                'notes' => 'Saus sambal botol 335ml',
            ],
            [
                'code' => 'BRM008',
                'name' => 'Mayonaise Maestro',
                'category' => 'Bumbu & Rempah',
                'unit' => 'bottle',
                'min_stock' => 8,
                'current_stock' => 24,
                'cost_price' => 28000,
                'supplier' => 'Toko Kebutuhan Dapur',
                'notes' => 'Mayonaise 470ml',
            ],

            // ========================================
            // PERALATAN DAPUR
            // ========================================
            [
                'code' => 'PKD001',
                'name' => 'Pisau Chef 8 inch',
                'category' => 'Peralatan Dapur',
                'unit' => 'pcs',
                'min_stock' => 5,
                'current_stock' => 12,
                'cost_price' => 185000,
                'supplier' => 'Toko Peralatan Dapur',
                'notes' => 'Pisau stainless steel profesional',
            ],
            [
                'code' => 'PKD002',
                'name' => 'Talenan Plastik Besar',
                'category' => 'Peralatan Dapur',
                'unit' => 'pcs',
                'min_stock' => 10,
                'current_stock' => 25,
                'cost_price' => 65000,
                'supplier' => 'Toko Peralatan Dapur',
                'notes' => 'Cutting board 40x30cm',
            ],
            [
                'code' => 'PKD003',
                'name' => 'Wajan Teflon 30cm',
                'category' => 'Peralatan Dapur',
                'unit' => 'pcs',
                'min_stock' => 8,
                'current_stock' => 20,
                'cost_price' => 145000,
                'supplier' => 'Toko Peralatan Dapur',
                'notes' => 'Non-stick frying pan',
            ],
            [
                'code' => 'PKD004',
                'name' => 'Panci Stainless 40cm',
                'category' => 'Peralatan Dapur',
                'unit' => 'pcs',
                'min_stock' => 5,
                'current_stock' => 15,
                'cost_price' => 285000,
                'supplier' => 'Toko Peralatan Dapur',
                'notes' => 'Stock pot stainless steel',
            ],
            [
                'code' => 'PKD005',
                'name' => 'Spatula Stainless',
                'category' => 'Peralatan Dapur',
                'unit' => 'pcs',
                'min_stock' => 15,
                'current_stock' => 40,
                'cost_price' => 35000,
                'supplier' => 'Toko Peralatan Dapur',
                'notes' => 'Turner stainless steel',
            ],
            [
                'code' => 'PKD006',
                'name' => 'Timbangan Digital Dapur',
                'category' => 'Peralatan Dapur',
                'unit' => 'pcs',
                'min_stock' => 3,
                'current_stock' => 8,
                'cost_price' => 125000,
                'supplier' => 'Toko Peralatan Dapur',
                'notes' => 'Digital scale max 5kg',
            ],
            [
                'code' => 'PKD007',
                'name' => 'Food Container 5L',
                'category' => 'Peralatan Dapur',
                'unit' => 'pcs',
                'min_stock' => 20,
                'current_stock' => 50,
                'cost_price' => 45000,
                'supplier' => 'Toko Peralatan Dapur',
                'notes' => 'Tempat penyimpanan makanan',
            ],

            // ========================================
            // PERALATAN MAKAN
            // ========================================
            [
                'code' => 'PKM001',
                'name' => 'Piring Makan Keramik',
                'category' => 'Peralatan Makan',
                'unit' => 'pcs',
                'min_stock' => 100,
                'current_stock' => 250,
                'cost_price' => 25000,
                'supplier' => 'Toko Perlengkapan Hotel',
                'notes' => 'Dinner plate 10 inch',
            ],
            [
                'code' => 'PKM002',
                'name' => 'Mangkuk Sup Keramik',
                'category' => 'Peralatan Makan',
                'unit' => 'pcs',
                'min_stock' => 80,
                'current_stock' => 200,
                'cost_price' => 22000,
                'supplier' => 'Toko Perlengkapan Hotel',
                'notes' => 'Soup bowl 6 inch',
            ],
            [
                'code' => 'PKM003',
                'name' => 'Gelas Kaca Polos',
                'category' => 'Peralatan Makan',
                'unit' => 'pcs',
                'min_stock' => 100,
                'current_stock' => 280,
                'cost_price' => 8000,
                'supplier' => 'Toko Perlengkapan Hotel',
                'notes' => 'Drinking glass 250ml',
            ],
            [
                'code' => 'PKM004',
                'name' => 'Sendok Makan Stainless',
                'category' => 'Peralatan Makan',
                'unit' => 'pcs',
                'min_stock' => 150,
                'current_stock' => 350,
                'cost_price' => 5500,
                'supplier' => 'Toko Perlengkapan Hotel',
                'notes' => 'Table spoon stainless',
            ],
            [
                'code' => 'PKM005',
                'name' => 'Garpu Makan Stainless',
                'category' => 'Peralatan Makan',
                'unit' => 'pcs',
                'min_stock' => 150,
                'current_stock' => 350,
                'cost_price' => 5500,
                'supplier' => 'Toko Perlengkapan Hotel',
                'notes' => 'Table fork stainless',
            ],
            [
                'code' => 'PKM006',
                'name' => 'Pisau Makan Stainless',
                'category' => 'Peralatan Makan',
                'unit' => 'pcs',
                'min_stock' => 100,
                'current_stock' => 250,
                'cost_price' => 6500,
                'supplier' => 'Toko Perlengkapan Hotel',
                'notes' => 'Table knife stainless',
            ],
            [
                'code' => 'PKM007',
                'name' => 'Cangkir Kopi + Tatakan',
                'category' => 'Peralatan Makan',
                'unit' => 'set',
                'min_stock' => 50,
                'current_stock' => 150,
                'cost_price' => 18000,
                'supplier' => 'Toko Perlengkapan Hotel',
                'notes' => 'Coffee cup & saucer set',
            ],

            // ========================================
            // BAHAN PEMBERSIH
            // ========================================
            [
                'code' => 'BPB001',
                'name' => 'Detergen Cuci Piring',
                'category' => 'Bahan Pembersih',
                'unit' => 'liter',
                'min_stock' => 20,
                'current_stock' => 50,
                'cost_price' => 25000,
                'supplier' => 'Toko Bahan Kimia',
                'notes' => 'Dishwashing liquid concentrate',
            ],
            [
                'code' => 'BPB002',
                'name' => 'Cairan Pembersih Lantai',
                'category' => 'Bahan Pembersih',
                'unit' => 'liter',
                'min_stock' => 15,
                'current_stock' => 40,
                'cost_price' => 18000,
                'supplier' => 'Toko Bahan Kimia',
                'notes' => 'Floor cleaner concentrate',
            ],
            [
                'code' => 'BPB003',
                'name' => 'Pembersih Kaca',
                'category' => 'Bahan Pembersih',
                'unit' => 'bottle',
                'min_stock' => 10,
                'current_stock' => 30,
                'cost_price' => 22000,
                'supplier' => 'Toko Bahan Kimia',
                'notes' => 'Glass cleaner spray 500ml',
            ],
            [
                'code' => 'BPB004',
                'name' => 'Desinfektan Spray',
                'category' => 'Bahan Pembersih',
                'unit' => 'bottle',
                'min_stock' => 12,
                'current_stock' => 36,
                'cost_price' => 28000,
                'supplier' => 'Toko Bahan Kimia',
                'notes' => 'Disinfectant spray 400ml',
            ],
            [
                'code' => 'BPB005',
                'name' => 'Spons Cuci Piring',
                'category' => 'Bahan Pembersih',
                'unit' => 'pack',
                'min_stock' => 20,
                'current_stock' => 60,
                'cost_price' => 8000,
                'supplier' => 'Toko Bahan Kimia',
                'notes' => 'Sponge pack isi 5',
            ],
            [
                'code' => 'BPB006',
                'name' => 'Kantong Sampah Jumbo',
                'category' => 'Bahan Pembersih',
                'unit' => 'pack',
                'min_stock' => 10,
                'current_stock' => 30,
                'cost_price' => 45000,
                'supplier' => 'Toko Bahan Kimia',
                'notes' => 'Trash bag 80x100cm isi 20',
            ],

            // ========================================
            // LINEN & TEKSTIL
            // ========================================
            [
                'code' => 'LTX001',
                'name' => 'Serbet Makan Putih',
                'category' => 'Linen & Tekstil',
                'unit' => 'pcs',
                'min_stock' => 200,
                'current_stock' => 500,
                'cost_price' => 8000,
                'supplier' => 'CV Tekstil Hotel',
                'notes' => 'Table napkin 40x40cm',
            ],
            [
                'code' => 'LTX002',
                'name' => 'Taplak Meja Putih',
                'category' => 'Linen & Tekstil',
                'unit' => 'pcs',
                'min_stock' => 50,
                'current_stock' => 120,
                'cost_price' => 85000,
                'supplier' => 'CV Tekstil Hotel',
                'notes' => 'Table cloth 150x150cm',
            ],
            [
                'code' => 'LTX003',
                'name' => 'Lap Dapur Microfiber',
                'category' => 'Linen & Tekstil',
                'unit' => 'pcs',
                'min_stock' => 50,
                'current_stock' => 150,
                'cost_price' => 12000,
                'supplier' => 'CV Tekstil Hotel',
                'notes' => 'Kitchen towel 30x40cm',
            ],
            [
                'code' => 'LTX004',
                'name' => 'Apron Chef Putih',
                'category' => 'Linen & Tekstil',
                'unit' => 'pcs',
                'min_stock' => 20,
                'current_stock' => 50,
                'cost_price' => 45000,
                'supplier' => 'CV Tekstil Hotel',
                'notes' => 'Chef apron with pocket',
            ],

            // ========================================
            // BARANG KONSUMSI
            // ========================================
            [
                'code' => 'BKS001',
                'name' => 'Tissue Roll Jumbo',
                'category' => 'Barang Konsumsi',
                'unit' => 'pcs',
                'min_stock' => 24,
                'current_stock' => 72,
                'cost_price' => 18000,
                'supplier' => 'Toko Perlengkapan',
                'notes' => 'Tissue toilet roll 250m',
            ],
            [
                'code' => 'BKS002',
                'name' => 'Tissue Makan',
                'category' => 'Barang Konsumsi',
                'unit' => 'pack',
                'min_stock' => 30,
                'current_stock' => 100,
                'cost_price' => 12000,
                'supplier' => 'Toko Perlengkapan',
                'notes' => 'Napkin tissue pack isi 250',
            ],
            [
                'code' => 'BKS003',
                'name' => 'Sedotan Plastik',
                'category' => 'Barang Konsumsi',
                'unit' => 'pack',
                'min_stock' => 10,
                'current_stock' => 35,
                'cost_price' => 15000,
                'supplier' => 'Toko Perlengkapan',
                'notes' => 'Drinking straw pack isi 100',
            ],
            [
                'code' => 'BKS004',
                'name' => 'Kotak Makan Styrofoam',
                'category' => 'Barang Konsumsi',
                'unit' => 'pack',
                'min_stock' => 20,
                'current_stock' => 60,
                'cost_price' => 35000,
                'supplier' => 'Toko Perlengkapan',
                'notes' => 'Food container pack isi 50',
            ],
            [
                'code' => 'BKS005',
                'name' => 'Plastik Wrapping',
                'category' => 'Barang Konsumsi',
                'unit' => 'pcs',
                'min_stock' => 5,
                'current_stock' => 15,
                'cost_price' => 45000,
                'supplier' => 'Toko Perlengkapan',
                'notes' => 'Cling wrap 30cm x 300m',
            ],
            [
                'code' => 'BKS006',
                'name' => 'Aluminium Foil',
                'category' => 'Barang Konsumsi',
                'unit' => 'pcs',
                'min_stock' => 8,
                'current_stock' => 24,
                'cost_price' => 38000,
                'supplier' => 'Toko Perlengkapan',
                'notes' => 'Aluminium foil 30cm x 100m',
            ],
        ];

        foreach ($items as $item) {
            DB::table('warehouse_items')->insert([
                'code' => $item['code'],
                'name' => $item['name'],
                'category' => $item['category'],
                'unit' => $item['unit'],
                'min_stock' => $item['min_stock'],
                'current_stock' => $item['current_stock'],
                'cost_price' => $item['cost_price'],
                'supplier' => $item['supplier'],
                'notes' => $item['notes'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✓ ' . count($items) . ' warehouse items seeded');
    }

    /**
     * Seed Initial Stock Transactions (Stock Masuk Awal)
     */
    private function seedInitialStockTransactions()
    {
        $items = DB::table('warehouse_items')->get();
        $transactionDate = Carbon::now()->subDays(30); // 30 hari yang lalu

        foreach ($items as $item) {
            // Buat transaksi stock masuk awal
            DB::table('stock_transactions')->insert([
                'transaction_code' => 'INIT/' . date('Ymd') . '/' . strtoupper(substr(md5($item->id), 0, 5)),
                'warehouse_item_id' => $item->id,
                'transaction_type' => 'in',
                'quantity' => $item->current_stock,
                'unit_price' => $item->cost_price,
                'total_price' => $item->current_stock * $item->cost_price,
                'reference_type' => 'other',
                'reference_id' => null,
                'notes' => 'Stock awal ' . $item->name,
                'transaction_date' => $transactionDate,
                'created_by' => 1, // Asumsi user ID 1 adalah admin
                'created_at' => $transactionDate,
                'updated_at' => $transactionDate,
            ]);
        }

        $this->command->info('✓ Initial stock transactions seeded');
    }
}