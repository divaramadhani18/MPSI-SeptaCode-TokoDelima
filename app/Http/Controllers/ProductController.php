<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(fn ($search) => $search
                    ->where('code', 'like', $term)
                    ->orWhere('name', 'like', $term));
            })
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'categories' => Product::query()->select('category')->distinct()->orderBy('category')->pluck('category'),
            'lowStockCount' => Product::where('is_active', true)->whereColumn('current_stock', '<', 'min_stock')->count(),
        ]);
    }

    public function create(): View
    {
        $product = new Product();

        return view('products.form', $this->formData($product));
    }

    public function store(Request $request): RedirectResponse
    {
        Product::create($this->validated($request));

        return redirect()->route('products.index')->with('success', 'Barang berhasil ditambahkan dengan stok awal 0.');
    }

    public function edit(Product $product): View
    {
        return view('products.form', $this->formData($product));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));

        return redirect()->route('products.index')->with('success', 'Data barang berhasil diperbarui.');
    }

    public function toggleStatus(Product $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('success', $product->is_active ? 'Barang diaktifkan kembali.' : 'Barang berhasil dinonaktifkan.');
    }

    public function movements(Request $request): View
    {
        $filters = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);

        $selectedProduct = isset($filters['product_id'])
            ? Product::findOrFail($filters['product_id'])
            : Product::orderBy('name')->first();
        $movementQuery = StockMovement::with([
            'product',
            'user',
            'purchaseItem.purchase.supplier',
            'saleItem.sale',
        ])
            ->where('product_id', $selectedProduct?->id ?? 0);

        $movementQuery->oldest('created_at')->oldest('id');

        $movements = $movementQuery->paginate(14)->withQueryString();
        $stockSummary = null;

        if ($selectedProduct) {
            $totals = StockMovement::query()
                ->where('product_id', $selectedProduct->id)
                ->selectRaw('SUM(CASE WHEN qty_change > 0 THEN qty_change ELSE 0 END) as total_in')
                ->selectRaw('SUM(CASE WHEN qty_change < 0 THEN -qty_change ELSE 0 END) as total_out')
                ->first();
            $firstMovement = StockMovement::query()
                ->where('product_id', $selectedProduct->id)
                ->oldest('created_at')
                ->oldest('id')
                ->first();

            $stockSummary = [
                'opening' => $firstMovement?->stock_before ?? $selectedProduct->current_stock,
                'total_in' => (int) ($totals->total_in ?? 0),
                'total_out' => (int) ($totals->total_out ?? 0),
                'ending' => $selectedProduct->current_stock,
                'opening_date' => $firstMovement?->created_at ?? $selectedProduct->created_at,
            ];
        }

        return view('stock.movements', [
            'movements' => $movements,
            'filters' => $filters,
            'selectedProduct' => $selectedProduct,
            'stockSummary' => $stockSummary,
        ]);
    }

    public function stockReport(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['safe', 'low_stock'])],
        ]);
        $activeProducts = Product::query()->where('is_active', true);
        $productsQuery = (clone $activeProducts)
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(fn ($search) => $search->where('code', 'like', $term)->orWhere('name', 'like', $term));
            })
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when(($filters['status'] ?? null) === 'low_stock', fn ($query) => $query->whereColumn('current_stock', '<', 'min_stock'))
            ->when(($filters['status'] ?? null) === 'safe', fn ($query) => $query->whereColumn('current_stock', '>=', 'min_stock'));

        $printProducts = (clone $productsQuery)->orderBy('name')->get();
        $products = $productsQuery
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'category_count' => (clone $activeProducts)->distinct()->count('category'),
            'stock_quantity' => (int) (clone $activeProducts)->sum('current_stock'),
            'low_stock_count' => (clone $activeProducts)->whereColumn('current_stock', '<', 'min_stock')->count(),
            'updated_at' => (clone $activeProducts)->max('updated_at'),
        ];

        return view('stock.report', [
            'products' => $products,
            'printProducts' => $printProducts,
            'categories' => (clone $activeProducts)->select('category')->distinct()->orderBy('category')->pluck('category'),
            'summary' => $summary,
            'stockPositionAt' => now(),
        ]);
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('products', 'code')->ignore($product?->id)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:40'],
            'purchase_price' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999999999.99'],
            'selling_price' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999999999.99'],
            'min_stock' => ['required', 'integer', 'min:0'],
        ]);
    }

    private function formData(Product $product): array
    {
        return [
            'product' => $product,
            'categories' => Product::query()->select('category')->distinct()->orderBy('category')->pluck('category')
                ->prepend('Perlengkapan Rumah')
                ->prepend('Pakaian')
                ->when($product->category, fn ($categories) => $categories->prepend($product->category))
                ->unique()
                ->values(),
            'units' => collect(['pcs', 'pasang', 'lusin'])
                ->when($product->unit, fn ($units) => $units->prepend($product->unit))
                ->unique()
                ->values(),
        ];
    }
}
