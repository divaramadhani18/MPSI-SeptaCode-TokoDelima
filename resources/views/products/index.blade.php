@extends('layouts.app')

@section('title', 'Data barang')
@section('heading', 'Data barang')

@section('content')
    <div class="page-lead">
        <div><h2>Data barang</h2><p>Kelola identitas, harga, dan batas minimum barang.</p></div>
        <a href="{{ route('products.create') }}" class="button button-primary">＋ Tambah barang</a>
    </div>
    @if ($lowStockCount > 0)
        <div class="stock-alert"><span class="alert-symbol">!</span><span><strong>{{ $lowStockCount }} barang perlu diperhatikan</strong><small>Stok saat ini berada di bawah batas minimum.</small></span><a href="{{ route('stock.report', ['low_stock' => 1]) }}">Lihat barang</a></div>
    @endif
    <section class="panel">
        <form method="GET" class="filter-row product-filter">
            <label class="filter-title" for="product-search">Cari barang</label>
            <label class="search-field"><span aria-hidden="true">⌕</span><input id="product-search" type="search" name="q" value="{{ request('q') }}" placeholder="Cari kode atau nama barang..."></label>
            <select name="category" aria-label="Kategori">
                <option value="">Semua Kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                @endforeach
            </select>
            <button class="button button-light" type="submit">Cari</button>
        </form>
        <div class="product-table-meta">{{ $products->total() }} barang terdaftar</div>
        <div class="table-wrap">
            <table class="products-table">
                <thead><tr><th>Kode</th><th>Nama barang</th><th>Kategori</th><th>Satuan</th><th>Harga beli</th><th>Harga jual</th><th>Stok</th><th>Stok minimum</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>{{ $product->code }}</td>
                            <td><strong>{{ $product->name }}</strong></td>
                            <td>{{ $product->category }}</td><td>{{ $product->unit }}</td>
                            <td>Rp {{ number_format((float) $product->purchase_price, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) $product->selling_price, 0, ',', '.') }}</td>
                            <td>{{ $product->current_stock }}</td><td>{{ $product->min_stock }}</td>
                            <td>@if (!$product->is_active)<span class="badge">Nonaktif</span>@elseif ($product->isLowStock())<span class="badge badge-danger">Di bawah min.</span>@else<span class="badge badge-success">Aman</span>@endif</td>
                            <td class="action-cell">
                                <a class="text-link" href="{{ route('stock.movements', ['product_id' => $product->id]) }}">Kartu stok</a>
                                <span class="action-separator" aria-hidden="true">/</span>
                                <a class="text-link" href="{{ route('products.edit', $product) }}">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="empty-table">Belum ada barang yang sesuai dengan pencarian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $products->links('vendor.pagination.toko') }}</div>
    </section>
@endsection
