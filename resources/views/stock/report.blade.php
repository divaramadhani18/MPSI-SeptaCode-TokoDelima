@extends('layouts.app')

@section('title', 'Laporan stok saat ini')
@section('heading', 'Laporan stok saat ini')

@section('content')
    <div class="page-lead stock-report-lead">
        <div>
            <h2>Laporan stok saat ini</h2>
            <p>Ringkasan ketersediaan stok barang.</p>
        </div>
        <button class="button button-light no-print" type="button" data-print-report>
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 8V3h10v5M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2m-10-4h10v8H7z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Cetak laporan
        </button>
    </div>

    <section class="stock-report-summary" aria-label="Ringkasan stok">
        <article class="stock-report-card">
            <span>Jenis barang</span>
            <strong>{{ number_format($summary['category_count'], 0, ',', '.') }}</strong>
            <small>Semua kategori</small>
        </article>
        <article class="stock-report-card">
            <span>Total kuantitas stok</span>
            <strong>{{ number_format($summary['stock_quantity'], 0, ',', '.') }}</strong>
            <small>Akumulasi satuan barang</small>
        </article>
        <article class="stock-report-card {{ $summary['low_stock_count'] ? 'stock-report-card-warning' : '' }}">
            <span>Di bawah minimum</span>
            <strong>{{ number_format($summary['low_stock_count'], 0, ',', '.') }} barang</strong>
            <small>{{ $summary['low_stock_count'] ? 'Perlu pembelian kembali' : 'Semua stok aman' }}</small>
        </article>
        <article class="stock-report-card">
            <span>Pembaruan terakhir</span>
            <strong>{{ $summary['updated_at'] ? \Illuminate\Support\Carbon::parse($summary['updated_at'])->format('H.i').' WIB' : '—' }}</strong>
            <small>{{ $summary['updated_at'] ? \Illuminate\Support\Carbon::parse($summary['updated_at'])->translatedFormat('d F Y') : 'Belum ada data' }}</small>
        </article>
    </section>

    <section class="panel stock-report-panel">
        <form method="GET" class="stock-report-filters no-print">
            <label class="stock-report-search-label" for="stock-report-search">Cari barang</label>
            <label class="search-field stock-report-search">
                <span aria-hidden="true">⌕</span>
                <input id="stock-report-search" type="search" name="q" value="{{ request('q') }}" placeholder="Cari kode atau nama barang...">
            </label>
            <label class="stock-report-filter">
                Kategori
                <select name="category" aria-label="Kategori">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </label>
            <label class="stock-report-filter">
                Status stok
                <select name="status" aria-label="Status stok">
                    <option value="">Semua status</option>
                    <option value="safe" @selected(request('status') === 'safe')>Aman</option>
                    <option value="low_stock" @selected(request('status') === 'low_stock')>Di bawah minimum</option>
                </select>
            </label>
            <div class="stock-report-filter-actions">
                <button class="button button-primary" type="submit">Terapkan</button>
                <a class="button button-light" href="{{ route('stock.report') }}">Reset</a>
            </div>
        </form>

        <p class="stock-position">Posisi stok <span>•</span> {{ $stockPositionAt->translatedFormat('d F Y, H.i') }}</p>

        <div class="table-wrap">
            <table class="stock-report-table stock-report-screen-table">
                <thead>
                    <tr><th>Kode</th><th>Nama barang</th><th>Kategori</th><th>Kuantitas stok</th><th>Satuan</th><th>Batas minimum</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>{{ $product->code }}</td>
                            <td><strong>{{ $product->name }}</strong></td>
                            <td>{{ $product->category }}</td>
                            <td>{{ number_format($product->current_stock, 0, ',', '.') }}</td>
                            <td>{{ $product->unit }}</td>
                            <td>{{ number_format($product->min_stock, 0, ',', '.') }}</td>
                            <td>
                                @if ($product->isLowStock())
                                    <span class="badge badge-danger">Di bawah min.</span>
                                @else
                                    <span class="badge badge-success">Aman</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-table">Tidak ada barang yang sesuai dengan filter.</td></tr>
                    @endforelse
                </tbody>
                <tfoot><tr><td colspan="7">Menampilkan {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} dari {{ $products->total() }} barang</td></tr></tfoot>
            </table>
            <table class="stock-report-table stock-report-print-table">
                <thead>
                    <tr><th>Kode</th><th>Nama barang</th><th>Kategori</th><th>Kuantitas stok</th><th>Satuan</th><th>Batas minimum</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($printProducts as $product)
                        <tr>
                            <td>{{ $product->code }}</td>
                            <td><strong>{{ $product->name }}</strong></td>
                            <td>{{ $product->category }}</td>
                            <td>{{ number_format($product->current_stock, 0, ',', '.') }}</td>
                            <td>{{ $product->unit }}</td>
                            <td>{{ number_format($product->min_stock, 0, ',', '.') }}</td>
                            <td>
                                @if ($product->isLowStock())
                                    <span class="badge badge-danger">Di bawah min.</span>
                                @else
                                    <span class="badge badge-success">Aman</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-table">Tidak ada barang yang sesuai dengan filter.</td></tr>
                    @endforelse
                </tbody>
                <tfoot><tr><td colspan="7">Menampilkan semua {{ $printProducts->count() }} barang</td></tr></tfoot>
            </table>
        </div>

        @if ($products->hasPages())
            {{ $products->links('vendor.pagination.toko', ['label' => 'barang', 'show_summary' => false]) }}
        @endif

        @if ($summary['low_stock_count'] > 0)
            <a class="stock-report-warning no-print" href="{{ route('stock.report', ['status' => 'low_stock']) }}">
                <span aria-hidden="true">⚠</span>
                Prioritaskan {{ $summary['low_stock_count'] }} barang di bawah batas minimum
            </a>
        @endif
    </section>
@endsection

@push('scripts')
    <style>
        @media print {
            @page { size: landscape; }
        }
    </style>
    <script>
        document.querySelector('[data-print-report]')?.addEventListener('click', () => window.print());
    </script>
@endpush
