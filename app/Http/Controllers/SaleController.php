<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function create(): View
    {
        return view('sales.create');
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'min:1', 'max:100']]);
        $term = '%'.$request->string('q').'%';

        $products = Product::where('is_active', true)
            ->where(fn ($query) => $query->where('code', 'like', $term)->orWhere('name', 'like', $term))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'code', 'name', 'unit', 'selling_price', 'current_stock'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'unit' => $product->unit,
                'selling_price' => $product->selling_price,
                'current_stock' => $product->current_stock,
            ]);

        return response()->json($products);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'payment_method' => ['required', 'in:cash,transfer'],
            'amount_received' => ['required_if:payment_method,cash', 'nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999.99'],
        ]);

        $sale = DB::transaction(function () use ($data, $request) {
            $productIds = collect($data['items'])->pluck('product_id')->map(fn ($id) => (int) $id)->sort()->values();
            $products = Product::whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            if ($products->count() !== $productIds->count()) {
                throw ValidationException::withMessages(['items' => 'Salah satu barang tidak ditemukan.']);
            }

            $total = '0.00';
            foreach ($data['items'] as $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];
                if (! $product->is_active || $quantity > $product->current_stock) {
                    throw ValidationException::withMessages([
                        'items' => $product->name.' tidak aktif atau stoknya tidak mencukupi.',
                    ]);
                }
                $total = Money::add($total, Money::multiply($product->selling_price, $quantity));
            }

            $received = $data['payment_method'] === 'transfer' ? $total : (string) $data['amount_received'];
            if (Money::compare($received, $total) < 0) {
                throw ValidationException::withMessages(['amount_received' => 'Uang diterima tidak boleh kurang dari total belanja.']);
            }

            $soldAt = now();
            $counter = DB::table('receipt_counters')->where('id', 1)->lockForUpdate()->first();
            $date = $soldAt->toDateString();
            $sequence = $counter->counter_date === $date ? $counter->last_sequence + 1 : 1;
            DB::table('receipt_counters')->where('id', 1)->update([
                'counter_date' => $date,
                'last_sequence' => $sequence,
            ]);

            $sale = Sale::create([
                'receipt_no' => 'PJ-'.$soldAt->format('dmy').'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
                'user_id' => $request->user()->id,
                'sold_at' => $soldAt,
                'payment_method' => $data['payment_method'],
                'total_amount' => $total,
                'amount_received' => $received,
                'change_amount' => $data['payment_method'] === 'cash' ? Money::subtract($received, $total) : '0.00',
            ]);

            foreach ($data['items'] as $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];
                $before = $product->current_stock;
                $after = $before - $quantity;
                $lineTotal = Money::multiply($product->selling_price, $quantity);

                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_code' => $product->code,
                    'unit' => $product->unit,
                    'quantity' => $quantity,
                    'unit_price' => $product->selling_price,
                    'line_total' => $lineTotal,
                ]);

                $product->update(['current_stock' => $after]);
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'sale_item_id' => $saleItem->id,
                    'movement_type' => 'sale',
                    'qty_change' => -$quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                ]);
            }

            return $sale;
        });

        return redirect()->route('sales.receipt', $sale)->with('success', 'Transaksi berhasil disimpan.');
    }

    public function receipt(Sale $sale): View
    {
        abort_if(auth()->user()->role === 'kasir' && $sale->user_id !== auth()->id(), 403);
        $sale->load(['user', 'items']);

        return view('sales.receipt', compact('sale'));
    }

    public function report(Request $request): View
    {
        $filters = $request->validate([
            'period' => ['nullable', 'in:day,month'],
            'date' => ['nullable', 'date'],
            'date_start' => ['nullable', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $period = $filters['period'] ?? 'day';
        $dateStart = $filters['date_start'] ?? $filters['date'] ?? $filters['date_end'] ?? today()->toDateString();
        $dateEnd = $filters['date_end'] ?? $dateStart;
        $month = $filters['month'] ?? today()->format('Y-m');

        $query = Sale::with(['user', 'items']);
        if ($period === 'month') {
            $query->whereYear('sold_at', substr($month, 0, 4))->whereMonth('sold_at', substr($month, 5, 2));
        } else {
            $query->whereDate('sold_at', '>=', $dateStart)->whereDate('sold_at', '<=', $dateEnd);
        }

        $query->latest('sold_at');
        $total = (clone $query)->sum('total_amount');
        $transactionCount = (clone $query)->count();
        $itemCount = SaleItem::whereIn('sale_id', (clone $query)->select('id'))->sum('quantity');
        $printSales = (clone $query)->get();
        $sales = $query->paginate(15)->withQueryString();

        return view('sales.report', compact('sales', 'printSales', 'total', 'transactionCount', 'itemCount', 'period', 'dateStart', 'dateEnd', 'month'));
    }
}
