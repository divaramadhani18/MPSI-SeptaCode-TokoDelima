@extends('layouts.app')

@section('title', 'Ringkasan')
@section('heading', 'Ringkasan toko')

@section('content')
    <div class="page-lead">
        <div><h2>Ringkasan toko</h2><p>Selamat datang, {{ auth()->user()->name }}. Pantau stok dan aktivitas toko hari ini.</p></div>
        <a href="{{ route('products.index') }}" class="button button-primary">Lihat data barang</a>
    </div>
    <div class="stat-grid">
        <article class="stat-card"><span class="stat-icon">▦</span><div><span>Barang aktif</span><strong>{{ number_format($productCount, 0, ',', '.') }}</strong><small>produk terdaftar</small></div></article>
        <article class="stat-card stat-warning"><span class="stat-icon">!</span><div><span>Stok di bawah minimum</span><strong>{{ number_format($lowStockCount, 0, ',', '.') }}</strong><small>perlu dipantau</small></div></article>
        <article class="stat-card"><span class="stat-icon">♧</span><div><span>Supplier</span><strong>{{ number_format($supplierCount, 0, ',', '.') }}</strong><small>supplier terdaftar</small></div></article>
        @if (auth()->user()->role === 'owner')
            <article class="stat-card"><span class="stat-icon">↗</span><div><span>Penjualan hari ini</span><strong>Rp {{ number_format((float) $todaySales, 0, ',', '.') }}</strong><small>{{ number_format($todaySalesCount, 0, ',', '.') }} transaksi hari ini</small></div></article>
            <article class="stat-card"><span class="stat-icon">↙</span><div><span>Pembelian hari ini</span><strong>Rp {{ number_format((float) $todayPurchases, 0, ',', '.') }}</strong><small>{{ number_format($todayPurchaseCount, 0, ',', '.') }} penerimaan hari ini</small></div></article>
        @endif
    </div>
    <div class="content-grid">
        <section class="panel">
            <div class="panel-heading"><div><h3>Peringatan stok</h3><p>Barang yang jumlahnya di bawah batas minimum.</p></div><a href="{{ route('stock.report', ['low_stock' => 1]) }}" class="text-link">Lihat semua</a></div>
            @if ($lowStockProducts->isEmpty())
                <div class="empty-state"><span class="empty-icon">✓</span><strong>Stok aman</strong><p>Tidak ada barang di bawah batas minimum.</p></div>
            @else
                <div class="table-wrap"><table><thead><tr><th>Barang</th><th>Stok</th><th>Batas minimum</th><th>Status</th></tr></thead><tbody>
                    @foreach ($lowStockProducts as $product)
                        <tr><td><strong>{{ $product->name }}</strong><small class="cell-sub">{{ $product->code }}</small></td><td>{{ $product->current_stock }}</td><td>{{ $product->min_stock }}</td><td><span class="badge badge-danger">Stok menipis</span></td></tr>
                    @endforeach
                </tbody></table></div>
            @endif
        </section>
        <section class="panel quick-panel">
            <div class="panel-heading"><div><h3>Akses cepat</h3><p>Pintasan ke aktivitas utama.</p></div></div>
            <a class="quick-link" href="{{ route('products.index') }}"><span class="quick-icon">▦</span><span><strong>Data barang</strong><small>Atur data dan harga barang</small></span><b>→</b></a>
            <a class="quick-link" href="{{ route('purchases.create') }}"><span class="quick-icon">＋</span><span><strong>Catat pembelian</strong><small>Tambahkan stok dari supplier</small></span><b>→</b></a>
            @if (auth()->user()->role === 'owner')
                <a class="quick-link" href="{{ route('sales.report') }}"><span class="quick-icon">▤</span><span><strong>Laporan penjualan</strong><small>Ringkasan transaksi kasir</small></span><b>→</b></a>
            @endif
        </section>
    </div>
@endsection
