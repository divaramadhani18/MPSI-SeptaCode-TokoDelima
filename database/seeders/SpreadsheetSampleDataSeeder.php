<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class SpreadsheetSampleDataSeeder extends Seeder
{
    private const DATA_FILE = 'database/seeders/data/spreadsheet-demo.json';

    public function run(): void
    {
        $path = base_path(self::DATA_FILE);

        if (! File::exists($path)) {
            throw new RuntimeException("Data demo workbook tidak ditemukan: {$path}");
        }

        $data = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        $this->validateData($data);

        DB::transaction(function () use ($data): void {
            $hasImportedSales = Sale::where('receipt_no', 'like', 'DEMO-%')->exists();

            if (! $hasImportedSales) {
                $this->removePreviousSampleRecords();
            }

            foreach ($data['suppliers'] as $attributes) {
                Supplier::updateOrCreate(
                    ['code' => $attributes['code']],
                    [
                        'name' => $attributes['name'],
                        'contact_name' => $attributes['contact_name'],
                        'phone' => $attributes['phone'],
                        'address' => $attributes['city'] ?: null,
                    ],
                );
            }

            $products = [];
            foreach ($data['products'] as $attributes) {
                $productAttributes = [
                    'name' => $attributes['name'],
                    'category' => $attributes['category'],
                    'unit' => 'pcs',
                    'purchase_price' => $attributes['purchase_price'],
                    'selling_price' => $attributes['selling_price'],
                    'min_stock' => 5,
                    'is_active' => true,
                ];
                if (! $hasImportedSales) {
                    $productAttributes['current_stock'] = $attributes['opening_stock'];
                }

                $products[$attributes['code']] = Product::updateOrCreate(
                    ['code' => $attributes['code']],
                    $productAttributes,
                );
            }

            if ($hasImportedSales) {
                return;
            }

            $cashier = User::query()->where('role', 'kasir')->firstOrFail();
            $transactions = collect($data['transactions'])
                ->sortBy(fn (array $transaction) => $transaction['sold_at'].'|'.$transaction['source_code'])
                ->values();
            $dailySequences = [];

            foreach ($transactions as $transaction) {
                $soldAt = Carbon::parse($transaction['sold_at']);
                $dateKey = $soldAt->format('dmy');
                $dailySequences[$dateKey] = ($dailySequences[$dateKey] ?? 0) + 1;
                $receiptNo = 'DEMO-'.$dateKey.'-'.str_pad((string) $dailySequences[$dateKey], 3, '0', STR_PAD_LEFT);
                $product = $products[$transaction['product_code']];
                $stockBefore = $product->current_stock;
                $quantity = (int) $transaction['quantity'];

                if ($quantity > $stockBefore) {
                    throw new RuntimeException("Stok awal tidak mencukupi untuk transaksi demo {$receiptNo}.");
                }

                $total = (string) $transaction['line_total'];
                $sale = Sale::create([
                    'receipt_no' => $receiptNo,
                    'user_id' => $cashier->id,
                    'sold_at' => $soldAt,
                    'payment_method' => $transaction['payment_method'],
                    'total_amount' => $total,
                    'amount_received' => $total,
                    'change_amount' => '0.00',
                ]);
                $this->setHistoricalTimestamps($sale, $soldAt);

                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_code' => $product->code,
                    'unit' => $product->unit,
                    'quantity' => $quantity,
                    'unit_price' => $transaction['unit_price'],
                    'line_total' => $total,
                ]);
                $this->setHistoricalTimestamps($saleItem, $soldAt);

                $stockAfter = $stockBefore - $quantity;
                $product->update(['current_stock' => $stockAfter]);

                $movement = StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $cashier->id,
                    'sale_item_id' => $saleItem->id,
                    'movement_type' => 'sale',
                    'qty_change' => -$quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                ]);
                $this->setHistoricalTimestamps($movement, $soldAt);
            }
        });
    }

    private function validateData(array $data): void
    {
        foreach (['products', 'suppliers', 'transactions'] as $key) {
            if (! isset($data[$key]) || ! is_array($data[$key])) {
                throw new RuntimeException("Bagian {$key} tidak tersedia di data demo workbook.");
            }
        }

        if (count($data['products']) <= 20 || count($data['transactions']) <= 50) {
            throw new RuntimeException('Data demo harus memuat lebih dari 20 barang dan 50 transaksi.');
        }
    }

    private function removePreviousSampleRecords(): void
    {
        $legacyProductCodes = [
            'BRG-DEMO-01',
            'BRG-DEMO-02',
            'BRG-DEMO-03',
            'TKD0001',
            'TKD0002',
            'TKD0003',
        ];

        if (! Product::whereIn('code', ['BRG-DEMO-01', 'BRG-DEMO-02', 'BRG-DEMO-03'])->exists()) {
            return;
        }

        $legacyPurchaseIds = DB::table('purchases')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->whereIn('suppliers.code', ['SUP-DEMO-01', 'SP0012', 'SP0040'])
            ->whereNotExists(function ($query) use ($legacyProductCodes) {
                $query->selectRaw('1')
                    ->from('purchase_items')
                    ->join('products', 'products.id', '=', 'purchase_items.product_id')
                    ->whereColumn('purchase_items.purchase_id', 'purchases.id')
                    ->whereNotIn('products.code', $legacyProductCodes);
            })
            ->whereExists(function ($query) use ($legacyProductCodes) {
                $query->selectRaw('1')
                    ->from('purchase_items')
                    ->join('products', 'products.id', '=', 'purchase_items.product_id')
                    ->whereColumn('purchase_items.purchase_id', 'purchases.id')
                    ->whereIn('products.code', $legacyProductCodes);
            })
            ->pluck('purchases.id');

        if ($legacyPurchaseIds->isNotEmpty()) {
            $purchaseItemIds = DB::table('purchase_items')->whereIn('purchase_id', $legacyPurchaseIds)->pluck('id');
            StockMovement::whereIn('purchase_item_id', $purchaseItemIds)->delete();
            DB::table('purchase_items')->whereIn('id', $purchaseItemIds)->delete();
            DB::table('purchases')->whereIn('id', $legacyPurchaseIds)->delete();
        }

        $legacyProducts = Product::whereIn('code', $legacyProductCodes)->get();
        foreach ($legacyProducts as $product) {
            if ($product->stockMovements()->exists()
                || DB::table('purchase_items')->where('product_id', $product->id)->exists()
                || DB::table('sale_items')->where('product_id', $product->id)->exists()) {
                $product->update(['is_active' => false]);

                continue;
            }

            $product->delete();
        }

        Supplier::where('code', 'SUP-DEMO-01')
            ->whereDoesntHave('purchases')
            ->delete();
    }

    private function setHistoricalTimestamps(Sale|SaleItem|StockMovement $model, Carbon $soldAt): void
    {
        $model->forceFill(['created_at' => $soldAt, 'updated_at' => $soldAt])->saveQuietly();
    }
}
