<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (Auth::user()->role === 'kasir') {
            return redirect()->route('sales.create');
        }

        $todaySales = Sale::whereDate('sold_at', today());
        $todayPurchases = Purchase::whereDate('purchased_at', today());

        return view('dashboard', [
            'productCount' => Product::where('is_active', true)->count(),
            'lowStockCount' => Product::where('is_active', true)
                ->whereColumn('current_stock', '<', 'min_stock')
                ->count(),
            'supplierCount' => Supplier::count(),
            'todayPurchases' => (clone $todayPurchases)->sum('total_amount'),
            'todayPurchaseCount' => $todayPurchases->count(),
            'todaySales' => (clone $todaySales)->sum('total_amount'),
            'todaySalesCount' => $todaySales->count(),
            'lowStockProducts' => Product::where('is_active', true)
                ->whereColumn('current_stock', '<', 'min_stock')
                ->orderBy('current_stock')
                ->limit(5)
                ->get(),
        ]);
    }
}
