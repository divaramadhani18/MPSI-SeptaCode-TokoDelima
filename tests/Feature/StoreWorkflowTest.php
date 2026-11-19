<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\SpreadsheetSampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StoreWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_cannot_open_inventory_or_reports_and_product_search_hides_purchase_price(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $product = Product::create([
            'code' => 'BRG-001',
            'name' => 'Barang Contoh',
            'category' => 'Pakaian',
            'unit' => 'pcs',
            'purchase_price' => 7000,
            'selling_price' => 10000,
            'current_stock' => 2,
            'min_stock' => 1,
        ]);

        $this->actingAs($cashier)->get('/products')->assertForbidden();
        $this->actingAs($cashier)->get('/reports/sales')->assertForbidden();
        $this->actingAs($cashier)->get('/sales/search?q=BRG')->assertOk()
            ->assertJsonPath('0.code', $product->code)
            ->assertDontSee('purchase_price');
    }

    public function test_supervisor_cannot_open_owner_only_reports(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'supervisor']))
            ->get('/reports/purchases')
            ->assertForbidden();
    }

    public function test_purchase_report_filters_and_displays_receiving_summary_and_details(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $supplier = Supplier::create(['code' => 'SUP-RPT-01', 'name' => 'Supplier Laporan']);
        $otherSupplier = Supplier::create(['code' => 'SUP-RPT-02', 'name' => 'Supplier Lain']);
        $product = Product::create([
            'code' => 'RPT-001',
            'name' => 'Barang Laporan',
            'category' => 'Perlengkapan',
            'unit' => 'pcs',
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'current_stock' => 10,
            'min_stock' => 2,
        ]);
        $purchase = Purchase::create([
            'supplier_id' => $supplier->id,
            'user_id' => $owner->id,
            'purchased_at' => '2026-10-30 09:00:00',
            'supplier_note' => 'NOTA-RPT-001',
            'note' => 'Barang diterima lengkap.',
            'total_amount' => 20000,
        ]);
        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_cost' => 10000,
            'line_total' => 20000,
        ]);
        Purchase::create([
            'supplier_id' => $otherSupplier->id,
            'user_id' => $owner->id,
            'purchased_at' => '2026-10-31 09:00:00',
            'total_amount' => 5000,
        ]);

        $this->actingAs($owner)->get(route('purchases.report', [
            'from' => '2026-10-30',
            'to' => '2026-10-30',
            'supplier_id' => $supplier->id,
        ]))->assertOk()
            ->assertSee('Rekap pembelian berdasarkan tanggal penerimaan barang yang sudah disimpan.')
            ->assertSee('Cetak laporan')
            ->assertSee('Filter laporan')
            ->assertSee('Tanggal mulai')
            ->assertSee('Tanggal akhir')
            ->assertSee('Tampilkan')
            ->assertSee('Total pembelian')
            ->assertSee('Jumlah transaksi')
            ->assertSee('2 pcs')
            ->assertSee('Periode 30 Oktober 2026')
            ->assertSee('PB-301026-'.str_pad((string) $purchase->id, 3, '0', STR_PAD_LEFT))
            ->assertSee('1 transaksi')
            ->assertSee('1 supplier')
            ->assertSee('1 jenis')
            ->assertSee('Barang Laporan')
            ->assertSee('NOTA-RPT-001')
            ->assertSee('Barang diterima lengkap.')
            ->assertSee('Petugas:')
            ->assertDontSee('PB-311026-'.str_pad((string) ($purchase->id + 1), 3, '0', STR_PAD_LEFT));
    }

    public function test_owner_pages_render_the_store_navigation_and_purchase_unit_column(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $this->actingAs($owner)->get('/dashboard')->assertOk()
            ->assertSee('images/toko-delima-logo.png')
            ->assertSee('Ringkasan')
            ->assertSee('Stok')
            ->assertSee('Pembelian')
            ->assertSee('Kasir')
            ->assertSee('Transaksi tunai')
            ->assertDontSee('MENU OWNER')
            ->assertSee('Ringkasan toko');

        $this->get('/purchases/create')->assertOk()
            ->assertSee('Catat pembelian dan penerimaan')
            ->assertSee('Catat barang yang benar-benar diterima.')
            ->assertSee('Nota supplier (opsional)')
            ->assertSee('Cari barang untuk diterima')
            ->assertSee('Jumlah diterima')
            ->assertSee('Catatan (opsional)')
            ->assertSee('Simpan penerimaan')
            ->assertSee('Satuan');
        $this->get('/purchases/create')->assertDontSee('Pilih supplier dan tanggal barang diterima.')
            ->assertDontSee('Barang yang diterima');

        $this->get('/sales/create')->assertOk()
            ->assertSee('<h2>Transaksi</h2>', false)
            ->assertSee('Cari nama atau kode barang')
            ->assertSee('Barang / kode')
            ->assertSee('Harga jual')
            ->assertSee('Metode Pembayaran')
            ->assertSee('Kembalian')
            ->assertSee('Bayar &amp; cetak struk', false);
    }

    public function test_dashboard_summary_uses_active_stock_and_transactions_from_today_only(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $supplier = Supplier::create(['code' => 'SUP-DASH', 'name' => 'Supplier Ringkasan']);

        Product::create([
            'code' => 'DASH-001',
            'name' => 'Barang Aktif',
            'category' => 'Pakaian',
            'unit' => 'pcs',
            'purchase_price' => 5000,
            'selling_price' => 10000,
            'current_stock' => 1,
            'min_stock' => 3,
            'is_active' => true,
        ]);
        Product::create([
            'code' => 'DASH-002',
            'name' => 'Barang Aktif Aman',
            'category' => 'Pakaian',
            'unit' => 'pcs',
            'purchase_price' => 5000,
            'selling_price' => 10000,
            'current_stock' => 5,
            'min_stock' => 3,
            'is_active' => true,
        ]);
        Product::create([
            'code' => 'DASH-003',
            'name' => 'Barang Nonaktif',
            'category' => 'Pakaian',
            'unit' => 'pcs',
            'purchase_price' => 5000,
            'selling_price' => 10000,
            'current_stock' => 0,
            'min_stock' => 3,
            'is_active' => false,
        ]);

        foreach ([
            ['receipt_no' => 'DASH-TODAY-001', 'sold_at' => now()->setTime(9, 0), 'total_amount' => 10000],
            ['receipt_no' => 'DASH-TODAY-002', 'sold_at' => now()->setTime(10, 0), 'total_amount' => 18000],
            ['receipt_no' => 'DASH-YESTERDAY', 'sold_at' => now()->subDay()->setTime(15, 0), 'total_amount' => 99000],
        ] as $saleData) {
            Sale::create($saleData + [
               'user_id' => $owner->id,
               'payment_method' => 'cash',
               'amount_received' => $saleData['total_amount'],
               'change_amount' => 0,
            ]);
        }

        foreach ([
            ['purchased_at' => now()->setTime(11, 0), 'total_amount' => 45000],
            ['purchased_at' => now()->subDay()->setTime(15, 0), 'total_amount' => 88000],
        ] as $purchaseData) {
            Purchase::create($purchaseData + [
               'supplier_id' => $supplier->id,
               'user_id' => $owner->id,
            ]);
        }

        $this->actingAs($owner)->get('/dashboard')->assertOk()
            ->assertSee('<span>Barang aktif</span><strong>2</strong>', false)
            ->assertSee('<span>Stok di bawah minimum</span><strong>1</strong>', false)
            ->assertSee('<span>Supplier</span><strong>1</strong>', false)
            ->assertSee('Rp 28.000')
            ->assertSee('2 transaksi hari ini')
            ->assertSee('Rp 45.000')
            ->assertSee('1 penerimaan hari ini')
            ->assertDontSee('Rp 99.000')
            ->assertDontSee('Rp 88.000');
    }

    public function test_supplier_list_has_custom_pagination_and_searches_phone_numbers(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        foreach (range(1, 13) as $number) {
            Supplier::create([
                'code' => sprintf('SUP-%03d', $number),
                'name' => sprintf('Supplier %02d', $number),
                'contact_name' => sprintf('Kontak %02d', $number),
                'phone' => sprintf('0812-5550-%04d', $number),
                'address' => 'Jakarta',
            ]);
        }

        $this->actingAs($owner)->get(route('suppliers.index'))->assertOk()
            ->assertSee('Cari nama, kode, atau kontak supplier...')
            ->assertSee('<th>Kode</th>', false)
            ->assertSee('supplier-filter')
            ->assertSee('pagination-controls')
            ->assertSee('Menampilkan 1–12 dari 13 supplier');

        $this->get(route('suppliers.index', ['q' => '0812-5550-0013']))->assertOk()
            ->assertSee('Supplier 13')
            ->assertDontSee('Supplier 01');
    }

    public function test_supplier_form_uses_figma_field_order_and_left_aligned_actions(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $this->actingAs($owner)->get(route('suppliers.create'))->assertOk()
            ->assertSee('supplier-form-grid')
            ->assertSee('supplier-form-phone')
            ->assertSee('<section class="panel form-panel">', false)
            ->assertSee('panel-heading')
            ->assertSeeInOrder([
                'Kode supplier',
                'Nama supplier',
                'Nama kontak',
                'Nomor telepon',
                'Alamat',
                'Batal',
                'Simpan supplier',
            ])
            ->assertDontSee('Informasi supplier digunakan saat mencatat pembelian.');
    }

    public function test_successful_save_flash_uses_the_shared_saved_notification(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $this->actingAs($owner)
            ->withSession(['success' => 'Supplier berhasil ditambahkan.'])
            ->get(route('suppliers.index'))
            ->assertOk()
            ->assertSee('Data Berhasil Disimpan')
            ->assertSee('data-success-toast', false)
            ->assertDontSee('Supplier berhasil ditambahkan.');
    }

    public function test_product_list_matches_catalog_layout_and_paginates_all_product_statuses(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $inactiveProduct = Product::create([
            'code' => 'BRG-000',
            'name' => 'A Barang Nonaktif',
            'category' => 'Pakaian',
            'unit' => 'pcs',
            'purchase_price' => 7000,
            'selling_price' => 10000,
            'current_stock' => 0,
            'min_stock' => 1,
            'is_active' => false,
        ]);

        foreach (range(1, 12) as $number) {
            Product::create([
                'code' => sprintf('BRG-%03d', $number),
                'name' => sprintf('Barang %02d', $number),
                'category' => 'Pakaian',
                'unit' => 'pcs',
                'purchase_price' => 7000,
                'selling_price' => 10000,
                'current_stock' => 2,
                'min_stock' => 1,
            ]);
        }

        $this->actingAs($owner)->get('/products')->assertOk()
            ->assertSee('Cari barang')
            ->assertSee('Semua Kategori')
            ->assertSee('Stok minimum')
            ->assertSee('A Barang Nonaktif')
            ->assertSee('Nonaktif')
            ->assertSee('Kartu stok')
            ->assertSee('Menampilkan 1–12 dari 13 barang')
            ->assertDontSee('Termasuk nonaktif')
            ->assertDontSee('Batas min.')
            ->assertDontSee('Nonaktifkan');

        $this->get(route('products.edit', $inactiveProduct))->assertOk()
            ->assertDontSee('Aktifkan barang')
            ->assertDontSee('Nonaktifkan barang');
    }

    public function test_add_product_form_uses_figma_field_layout_and_dropdowns(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $this->actingAs($owner)->get('/products/create')->assertOk()
            ->assertSee('Panel Tambah Barang')
            ->assertSee('Tambah barang')
            ->assertSee('name="category"', false)
            ->assertSee('name="unit"', false)
            ->assertSee('Pilih kategori')
            ->assertSee('Pilih satuan')
            ->assertSee('product-form-code', false)
            ->assertSee('product-form-minimum', false)
            ->assertDontSee('Stok hanya berubah melalui transaksi pembelian dan penjualan.');
    }

    public function test_stock_card_shows_summary_and_opening_row_for_selected_product(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $product = Product::create([
            'code' => 'BRG-KARTU',
            'name' => 'Kaos Basic',
            'category' => 'Pakaian',
            'unit' => 'pcs',
            'purchase_price' => 7000,
            'selling_price' => 10000,
            'current_stock' => 28,
            'min_stock' => 2,
        ]);

        foreach ([
            ['purchase', 10, 20, 30, now()->subMinutes(20)],
            ['sale', -2, 30, 28, now()->subMinutes(10)],
        ] as [$type, $change, $before, $after, $createdAt]) {
            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $owner->id,
                'movement_type' => $type,
                'qty_change' => $change,
                'stock_before' => $before,
                'stock_after' => $after,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        $response = $this->actingAs($owner)->get(route('stock.movements', ['product_id' => $product->id]));

        $response->assertOk()
            ->assertSee('Kartu stok / Kaos Basic')
            ->assertSee('Stok awal')
            ->assertSee('Total masuk')
            ->assertSee('Total keluar')
            ->assertSee('Stok akhir')
            ->assertSee('Stok awal periode')
            ->assertSee(now()->translatedFormat('d F'), false)
            ->assertSee('Ke data barang')
            ->assertSee('10')
            ->assertSee('28')
            ->assertDontSee('stock-product-select')
            ->assertDontSee('pagination-summary');

        $this->assertSame(5, substr_count($response->getContent(), '<tr>'));
    }

    public function test_stock_card_paginates_movements_without_duplicating_the_opening_row(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $product = Product::create([
            'code' => 'BRG-PAGE',
            'name' => 'Barang Pagination',
            'category' => 'Pakaian',
            'unit' => 'pcs',
            'purchase_price' => 7000,
            'selling_price' => 10000,
            'current_stock' => 16,
            'min_stock' => 2,
        ]);

        foreach (range(1, 16) as $number) {
            $movementTime = now()->subMinutes(20 - $number);
            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $owner->id,
                'movement_type' => 'purchase',
                'qty_change' => 1,
                'stock_before' => $number - 1,
                'stock_after' => $number,
                'created_at' => $movementTime,
                'updated_at' => $movementTime,
            ]);
        }

        $this->actingAs($owner)->get(route('stock.movements', ['product_id' => $product->id]))->assertOk()
            ->assertSee('Menampilkan 1–14 dari 16 mutasi')
            ->assertSee('15 baris ditampilkan')
            ->assertSee('Halaman berikutnya');

        $this->get(route('stock.movements', ['product_id' => $product->id, 'page' => 2]))->assertOk()
            ->assertSee('Menampilkan 15–16 dari 16 mutasi')
            ->assertSee('2 baris ditampilkan')
            ->assertDontSee('Stok awal periode');
    }

    public function test_stock_card_without_product_shows_empty_state_without_filters(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $this->actingAs($owner)->get(route('stock.movements'))->assertOk()
            ->assertSee('Belum ada barang untuk ditampilkan di kartu stok.')
            ->assertSee('Tambah barang')
            ->assertSee('Tanggal / waktu')
            ->assertSee('Referensi')
            ->assertSee('Keterangan')
            ->assertSee('Masuk')
            ->assertSee('Keluar')
            ->assertSee('Stok')
            ->assertSee('Petugas')
            ->assertDontSee('stock-product-select')
            ->assertDontSee('Dari tanggal')
            ->assertDontSee('Terapkan');
    }

    public function test_stock_card_defaults_to_first_product_when_none_is_selected(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        Product::create([
            'code' => 'BRG-Z',
            'name' => 'Zebra',
            'category' => 'Pakaian',
            'unit' => 'pcs',
            'purchase_price' => 7000,
            'selling_price' => 10000,
            'current_stock' => 8,
            'min_stock' => 1,
        ]);
        Product::create([
            'code' => 'BRG-A',
            'name' => 'Anggrek',
            'category' => 'Pakaian',
            'unit' => 'pcs',
            'purchase_price' => 7000,
            'selling_price' => 10000,
            'current_stock' => 3,
            'min_stock' => 1,
        ]);

        $this->actingAs($owner)->get(route('stock.movements'))->assertOk()
            ->assertSee('Kartu stok / Anggrek')
            ->assertSee('Stok awal')
            ->assertSee('Total masuk')
            ->assertSee('Total keluar')
            ->assertSee('Stok akhir')
            ->assertDontSee('Kartu stok / Zebra')
            ->assertDontSee('Semua barang');
    }

    public function test_current_stock_report_matches_figma_summary_filters_and_table(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        Product::create([
            'code' => 'REP-001',
            'name' => 'Kaos Aman',
            'category' => 'Atasan',
            'unit' => 'pcs',
            'purchase_price' => 7000,
            'selling_price' => 10000,
            'current_stock' => 28,
            'min_stock' => 10,
        ]);
        Product::create([
            'code' => 'REP-002',
            'name' => 'Kemeja Minimum',
            'category' => 'Atasan',
            'unit' => 'pcs',
            'purchase_price' => 8000,
            'selling_price' => 12000,
            'current_stock' => 7,
            'min_stock' => 12,
        ]);
        Product::create([
            'code' => 'REP-003',
            'name' => 'Celana Jeans',
            'category' => 'Bawahan',
            'unit' => 'pcs',
            'purchase_price' => 9000,
            'selling_price' => 14000,
            'current_stock' => 15,
            'min_stock' => 10,
            'is_active' => false,
        ]);

        $this->actingAs($owner)->get(route('stock.report'))->assertOk()
            ->assertSee('Ringkasan ketersediaan stok barang.')
            ->assertSee('Jenis barang')
            ->assertSee('Total kuantitas stok')
            ->assertSee('Di bawah minimum')
            ->assertSee('Pembaruan terakhir')
            ->assertSee('Posisi stok')
            ->assertSee('Semua kategori')
            ->assertSee('Status stok')
            ->assertSee('Terapkan')
            ->assertSee('Kode')
            ->assertSee('Kuantitas stok')
            ->assertSee('Batas minimum')
            ->assertSee('REP-001')
            ->assertSee('REP-002')
            ->assertDontSee('REP-003')
            ->assertSee('Menampilkan 1–2 dari 2 barang')
            ->assertDontSee('pagination-controls')
            ->assertDontSee('Harga beli')
            ->assertDontSee('Nilai persediaan')
            ->assertSee('Prioritaskan 1 barang di bawah batas minimum');

        $this->get(route('stock.report', ['status' => 'low_stock']))->assertOk()
            ->assertSee('REP-002')
            ->assertDontSee('REP-001');

        $this->get(route('stock.report', ['category' => 'Bawahan']))->assertOk()
            ->assertDontSee('REP-001')
            ->assertDontSee('REP-002');
    }

    public function test_stock_report_print_table_includes_all_products_beyond_the_current_page(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        foreach (range(1, 18) as $number) {
            Product::create([
                'code' => sprintf('PRINT-%03d', $number),
                'name' => sprintf('Barang Cetak %02d', $number),
                'category' => 'Pakaian',
                'unit' => 'pcs',
                'purchase_price' => 7000,
                'selling_price' => 10000,
                'current_stock' => 20,
                'min_stock' => 5,
            ]);
        }

        $this->actingAs($owner)->get(route('stock.report'))->assertOk()
            ->assertSee('Menampilkan 1–15 dari 18 barang')
            ->assertSee('stock-report-print-table')
            ->assertSee('PRINT-018')
            ->assertSee('Menampilkan semua 18 barang');
    }

    public function test_sales_report_filters_date_range_and_shows_summary_and_print_rows(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $product = Product::create([
            'code' => 'RPT-001',
            'name' => 'Barang Laporan',
            'category' => 'Pakaian',
            'unit' => 'pcs',
            'purchase_price' => 5000,
            'selling_price' => 10000,
            'current_stock' => 5,
            'min_stock' => 1,
        ]);
        $sale = Sale::create([
            'receipt_no' => 'PJ-301026-001',
            'user_id' => $owner->id,
            'sold_at' => '2026-10-30 10:15:00',
            'payment_method' => 'cash',
            'total_amount' => 15000,
            'amount_received' => 20000,
            'change_amount' => 5000,
        ]);
        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_code' => $product->code,
            'unit' => $product->unit,
            'quantity' => 3,
            'unit_price' => 5000,
            'line_total' => 15000,
        ]);
        Sale::create([
            'receipt_no' => 'PJ-291026-001',
            'user_id' => $owner->id,
            'sold_at' => '2026-10-29 10:15:00',
            'payment_method' => 'cash',
            'total_amount' => 25000,
            'amount_received' => 25000,
            'change_amount' => 0,
        ]);

        $this->actingAs($owner)->get(route('sales.report', [
            'period' => 'day',
            'date_start' => '2026-10-30',
            'date_end' => '2026-10-30',
        ]))->assertOk()
            ->assertSee('Filter laporan')
            ->assertSee('Tanggal mulai')
            ->assertSee('Tanggal akhir')
            ->assertSee('Omzet tunai')
            ->assertSee('Rp15.000')
            ->assertSee('<td>3</td>', false)
            ->assertSee('sales-report-print-table')
            ->assertSee('PJ-301026-001')
            ->assertDontSee('PJ-291026-001')
            ->assertSee('Menampilkan 1 transaksi');
    }

    public function test_cashier_cannot_open_another_cashiers_receipt(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);
        $otherCashier = User::factory()->create(['role' => 'kasir']);
        $sale = Sale::create([
            'receipt_no' => 'PJ-051126-001',
            'user_id' => $otherCashier->id,
            'sold_at' => now(),
            'payment_method' => 'cash',
            'total_amount' => 10000,
            'amount_received' => 10000,
            'change_amount' => 0,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => Product::create([
                'code' => 'BRG-004',
                'name' => 'Barang Struk',
                'category' => 'Pakaian',
                'unit' => 'pcs',
                'purchase_price' => 5000,
                'selling_price' => 10000,
                'current_stock' => 0,
                'min_stock' => 0,
            ])->id,
            'product_name' => 'Barang Struk',
            'product_code' => 'BRG-004',
            'unit' => 'pcs',
            'quantity' => 1,
            'unit_price' => 10000,
            'line_total' => 10000,
        ]);

        $this->actingAs($cashier)->get(route('sales.receipt', $sale))->assertForbidden();
    }

    public function test_purchase_and_sale_update_stock_and_create_append_only_movements(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $supplier = Supplier::create(['code' => 'SUP-001', 'name' => 'Supplier Contoh']);
        $product = Product::create([
            'code' => 'BRG-002',
            'name' => 'Barang Uji',
            'category' => 'Pakaian',
            'unit' => 'pcs',
            'purchase_price' => 7000,
            'selling_price' => 10000,
            'current_stock' => 0,
            'min_stock' => 2,
        ]);

        $this->actingAs($owner)->post('/purchases', [
            'supplier_id' => $supplier->id,
            'purchased_at' => now()->format('Y-m-d'),
            'supplier_note' => 'NOTA-2026-001',
            'note' => 'Diterima lengkap dan dalam kondisi baik.',
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'unit_cost' => 7000]],
        ])->assertRedirect('/purchases/create');

        $this->assertDatabaseHas('purchases', [
            'supplier_id' => $supplier->id,
            'supplier_note' => 'NOTA-2026-001',
            'note' => 'Diterima lengkap dan dalam kondisi baik.',
            'total_amount' => '21000.00',
        ]);
        $this->get(route('purchases.report'))->assertOk()
            ->assertSee('NOTA-2026-001')
            ->assertSee('Diterima lengkap dan dalam kondisi baik.');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'current_stock' => 3]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'purchase',
            'qty_change' => 3,
            'stock_before' => 0,
            'stock_after' => 3,
        ]);

        $this->post('/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment_method' => 'cash',
            'amount_received' => 25000,
        ])->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'current_stock' => 1]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'sale',
            'qty_change' => -2,
            'stock_before' => 3,
            'stock_after' => 1,
        ]);
        $this->assertDatabaseHas('sales', [
            'receipt_no' => 'PJ-'.now()->format('dmy').'-001',
            'total_amount' => '20000.00',
            'amount_received' => '25000.00',
            'change_amount' => '5000.00',
        ]);

        $sale = Sale::where('receipt_no', 'PJ-'.now()->format('dmy').'-001')->firstOrFail();
        $this->get(route('sales.receipt', $sale))->assertOk()
            ->assertSee('STRUK PENJUALAN')
            ->assertSee('Nota')
            ->assertSee('Petugas')
            ->assertSee('Barang Uji')
            ->assertSee('Tunai')
            ->assertSee('Kembalian')
            ->assertSee('Rp 25.000')
            ->assertSee('Rp 5.000')
            ->assertSee('Cetak struk');
    }

    public function test_sale_is_rejected_if_quantity_exceeds_stock(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $product = Product::create([
            'code' => 'BRG-003',
            'name' => 'Stok Terbatas',
            'category' => 'Pakaian',
            'unit' => 'pcs',
            'purchase_price' => 5000,
            'selling_price' => 8000,
            'current_stock' => 1,
            'min_stock' => 0,
        ]);

        $this->actingAs($owner)->post('/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment_method' => 'cash',
            'amount_received' => 20000,
        ])->assertSessionHasErrors('items');

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame(1, $product->fresh()->current_stock);
    }

    public function test_login_rejects_disabled_users(): void
    {
        $user = User::factory()->create([
            'username' => 'nonaktif',
            'password' => Hash::make('SandiUji!234'),
            'is_active' => false,
        ]);

        $this->post('/login', ['username' => $user->username, 'password' => 'SandiUji!234'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_spreadsheet_sample_seeder_imports_workbook_catalog_and_transactions_idempotently(): void
    {
        User::factory()->create(['role' => 'owner']);
        User::factory()->create(['role' => 'kasir']);
        Product::create([
            'code' => 'BRG-DEMO-01',
            'name' => 'Blus Contoh',
            'category' => 'Pakaian',
            'unit' => 'pcs',
            'purchase_price' => 75000,
            'selling_price' => 99000,
            'current_stock' => 0,
            'min_stock' => 3,
        ]);

        $this->seed(SpreadsheetSampleDataSeeder::class);

        $this->assertDatabaseCount('products', 54);
        $this->assertDatabaseCount('suppliers', 16);
        $this->assertDatabaseCount('sales', 100);
        $this->assertDatabaseCount('sale_items', 100);
        $this->assertDatabaseCount('stock_movements', 100);
        $sourceData = json_decode(
            file_get_contents(database_path('seeders/data/spreadsheet-demo.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $kemeja = collect($sourceData['products'])->firstWhere('code', 'TKD0001');
        $kemejaSampleSales = collect($sourceData['transactions'])
            ->where('product_code', 'TKD0001')
            ->sum('quantity');
        $this->assertDatabaseHas('products', [
            'code' => 'TKD0001',
            'name' => 'Kemeja Batik Pria',
            'current_stock' => $kemeja['opening_stock'] - $kemejaSampleSales,
        ]);
        $this->assertDatabaseHas('products', [
            'code' => 'TKD0047',
            'current_stock' => 73,
        ]);
        $this->assertDatabaseHas('suppliers', ['code' => 'SP0012', 'name' => 'MITRA SANDANG']);
        $this->assertDatabaseHas('suppliers', [
            'code' => 'SP0012',
            'contact_name' => 'Sari Wulandari',
            'phone' => '0812-3400-1212',
        ]);
        $this->assertDatabaseHas('sales', ['payment_method' => 'cash', 'user_id' => User::where('role', 'kasir')->value('id')]);
        $this->assertDatabaseMissing('products', ['code' => 'BRG-DEMO-01']);

        $this->seed(SpreadsheetSampleDataSeeder::class);
        $this->assertDatabaseCount('products', 54);
        $this->assertDatabaseCount('sales', 100);
        $this->assertDatabaseCount('stock_movements', 100);
        $this->assertDatabaseHas('products', ['code' => 'TKD0047', 'current_stock' => 73]);
    }

    public function test_configured_demo_password_updates_existing_demo_accounts(): void
    {
        $owner = User::factory()->create([
            'username' => 'owner',
            'role' => 'owner',
            'password' => Hash::make('OldDemoPassword!'),
        ]);
        $previousValue = getenv('DEMO_USER_PASSWORD');
        $previousEnv = $_ENV['DEMO_USER_PASSWORD'] ?? null;
        $previousServer = $_SERVER['DEMO_USER_PASSWORD'] ?? null;

        putenv('DEMO_USER_PASSWORD=DelimaDemo2026!');
        $_ENV['DEMO_USER_PASSWORD'] = 'DelimaDemo2026!';
        $_SERVER['DEMO_USER_PASSWORD'] = 'DelimaDemo2026!';

        try {
            $this->seed(\Database\Seeders\DatabaseSeeder::class);
        } finally {
            $previousValue === false
                ? putenv('DEMO_USER_PASSWORD')
                : putenv('DEMO_USER_PASSWORD='.$previousValue);
            unset($_ENV['DEMO_USER_PASSWORD'], $_SERVER['DEMO_USER_PASSWORD']);

            if ($previousEnv !== null) {
                $_ENV['DEMO_USER_PASSWORD'] = $previousEnv;
            }

            if ($previousServer !== null) {
                $_SERVER['DEMO_USER_PASSWORD'] = $previousServer;
            }
        }

        $this->assertTrue(Hash::check('DelimaDemo2026!', $owner->fresh()->password));
    }
}
