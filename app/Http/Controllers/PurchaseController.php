<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function create(): View
    {
        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'unit', 'purchase_price']);

        return view('purchases.create', [
            'suppliers' => Supplier::orderBy('name')->get(),
            'products' => $products,
            'productOptions' => $products->map(fn (Product $product) => [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'unit' => $product->unit,
                'cost' => $product->purchase_price,
            ])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchased_at' => ['required', 'date'],
            'supplier_note' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'items.*.unit_cost' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999999999.99'],
        ]);

        $purchase = DB::transaction(function () use ($data, $request) {
            $productIds = collect($data['items'])->pluck('product_id')->map(fn ($id) => (int) $id)->sort()->values();
            $products = Product::whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            if ($products->count() !== $productIds->count() || $products->contains(fn (Product $product) => ! $product->is_active)) {
                throw ValidationException::withMessages(['items' => 'Barang yang dipilih tidak aktif atau tidak tersedia.']);
            }

            $total = '0.00';
            foreach ($data['items'] as $item) {
                $total = Money::add($total, Money::multiply($item['unit_cost'], (int) $item['quantity']));
            }

            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'user_id' => $request->user()->id,
                'purchased_at' => $data['purchased_at'],
                'supplier_note' => $data['supplier_note'] ?? null,
                'note' => $data['note'] ?? null,
                'total_amount' => $total,
            ]);

            foreach ($data['items'] as $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];
                $before = $product->current_stock;
                $after = $before + $quantity;
                if ($after > 4294967295) {
                    throw ValidationException::withMessages(['items' => 'Jumlah stok barang melebihi kapasitas penyimpanan.']);
                }
                $lineTotal = Money::multiply($item['unit_cost'], $quantity);

                $purchaseItem = PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => $lineTotal,
                ]);

                $product->update([
                    'current_stock' => $after,
                    'purchase_price' => $item['unit_cost'],
                ]);

                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'purchase_item_id' => $purchaseItem->id,
                    'movement_type' => 'purchase',
                    'qty_change' => $quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                ]);
            }

            return $purchase;
        });

        return redirect()->route('purchases.create')->with('success', 'Pembelian tersimpan dan stok berhasil diperbarui.');
    }

    public function report(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
        ]);

        $query = Purchase::with(['supplier', 'user', 'items.product'])
            ->when($filters['from'] ?? null, fn ($builder, $from) => $builder->whereDate('purchased_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($builder, $to) => $builder->whereDate('purchased_at', '<=', $to))
            ->when($filters['supplier_id'] ?? null, fn ($builder, $supplierId) => $builder->where('supplier_id', $supplierId))
            ->latest('purchased_at');

        $total = (clone $query)->sum('total_amount');
        $transactionCount = (clone $query)->count();
        $supplierCount = (clone $query)->distinct('supplier_id')->count('supplier_id');
        $itemTypeCount = PurchaseItem::query()
            ->whereIn('purchase_id', (clone $query)->reorder()->select('purchases.id'))
            ->count();
        $unitTotals = PurchaseItem::query()
            ->join('products', 'products.id', '=', 'purchase_items.product_id')
            ->whereIn('purchase_items.purchase_id', (clone $query)->reorder()->select('purchases.id'))
            ->selectRaw('products.unit, SUM(purchase_items.quantity) as quantity')
            ->groupBy('products.unit')
            ->orderBy('products.unit')
            ->get();
        $purchases = $query->paginate(15)->withQueryString();
        $unitSummary = $unitTotals
            ->map(fn ($unitTotal) => number_format((int) $unitTotal->quantity, 0, ',', '.').' '.$unitTotal->unit)
            ->join(', ');

        return view('purchases.report', [
            'purchases' => $purchases,
            'total' => $total,
            'filters' => $filters,
            'suppliers' => Supplier::orderBy('name')->get(),
            'transactionCount' => $transactionCount,
            'supplierCount' => $supplierCount,
            'itemTypeCount' => $itemTypeCount,
            'unitSummary' => $unitSummary ?: '0 pcs',
            'reportUpdatedAt' => now(),
        ]);
    }
}
