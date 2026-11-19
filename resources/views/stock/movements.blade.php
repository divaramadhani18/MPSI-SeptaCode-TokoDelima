@extends('layouts.app')

@section('title', $selectedProduct ? 'Kartu stok · '.$selectedProduct->name : 'Kartu stok')
@section('heading', 'Kartu stok')

@section('content')
    <div class="page-lead">
        <div>
            <h2>{{ $selectedProduct ? 'Kartu stok / '.$selectedProduct->name : 'Kartu stok' }}</h2>
            <p>{{ $selectedProduct ? 'Telusuri riwayat masuk dan keluar per barang.' : 'Riwayat barang masuk dan keluar.' }}</p>
        </div>
        <a href="{{ route('products.index') }}" class="button button-light">Ke data barang</a>
    </div>

    @if ($selectedProduct)
        <section class="stock-summary-grid" aria-label="Ringkasan stok {{ $selectedProduct->name }}">
            <article class="stock-summary-card">
                <span>Stok awal</span>
                <strong>{{ number_format($stockSummary['opening'], 0, ',', '.') }}</strong>
                <small>{{ $selectedProduct->unit }}</small>
            </article>
            <article class="stock-summary-card">
                <span>Total masuk</span>
                <strong class="positive">+{{ number_format($stockSummary['total_in'], 0, ',', '.') }}</strong>
                <small>Dari pembelian</small>
            </article>
            <article class="stock-summary-card">
                <span>Total keluar</span>
                <strong class="negative">−{{ number_format($stockSummary['total_out'], 0, ',', '.') }}</strong>
                <small>Dari penjualan</small>
            </article>
            <article class="stock-summary-card">
                <span>Stok akhir</span>
                <strong>{{ number_format($stockSummary['ending'], 0, ',', '.') }}</strong>
                <small>{{ $selectedProduct->unit }}</small>
            </article>
        </section>
    @else
        <section class="panel empty-stock-card">
            <p>Belum ada barang untuk ditampilkan di kartu stok.</p>
            <a href="{{ route('products.create') }}" class="button button-primary">Tambah barang</a>
        </section>
    @endif

    <section class="panel stock-movements-panel">
        <div class="table-wrap">
            <table class="stock-movements-table">
                <thead><tr><th>Tanggal / waktu</th><th>Referensi</th><th>Keterangan</th><th>Masuk</th><th>Keluar</th><th>Stok</th><th>Petugas</th></tr></thead>
                <tbody>
                    @if ($selectedProduct && $movements->currentPage() === 1)
                        <tr>
                            <td>{{ $stockSummary['opening_date']->startOfDay()->translatedFormat('d F Y / H.i') }}</td>
                            <td>—</td>
                            <td>Stok awal periode</td>
                            <td>—</td>
                            <td>—</td>
                            <td>{{ number_format($stockSummary['opening'], 0, ',', '.') }}</td>
                            <td>—</td>
                        </tr>
                    @endif
                    @forelse ($movements as $movement)
                        <tr>
                            <td>{{ $movement->created_at->translatedFormat('d F Y / H.i') }}</td>
                            <td>
                                @if ($movement->purchaseItem?->purchase)
                                    {{ sprintf('PB-%s-%03d', $movement->purchaseItem->purchase->purchased_at->format('dmy'), $movement->purchaseItem->purchase->id) }}
                                @elseif ($movement->saleItem?->sale)
                                    {{ $movement->saleItem->sale->receipt_no }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($movement->movement_type === 'purchase')
                                    Pembelian · {{ $movement->purchaseItem?->purchase?->supplier?->name ?? 'Penerimaan barang' }}
                                @else
                                    Penjualan tunai
                                @endif
                            </td>
                            <td>{{ $movement->qty_change > 0 ? number_format($movement->qty_change, 0, ',', '.') : '—' }}</td>
                            <td>{{ $movement->qty_change < 0 ? number_format(abs($movement->qty_change), 0, ',', '.') : '—' }}</td>
                            <td>{{ number_format($movement->stock_after, 0, ',', '.') }}</td>
                            <td>{{ $movement->user->name }}</td>
                        </tr>
                    @empty
                        @if ($selectedProduct)
                            <tr><td colspan="7" class="empty-table">Belum ada mutasi untuk barang ini.</td></tr>
                        @else
                            <tr><td colspan="7" class="empty-table">Belum ada data barang.</td></tr>
                        @endif
                    @endforelse
                </tbody>
                @if ($selectedProduct)
                    <tfoot><tr><td colspan="7">{{ $movements->count() + ($movements->currentPage() === 1 ? 1 : 0) }} baris ditampilkan</td></tr></tfoot>
                @endif
            </table>
        </div>
        @if (!$selectedProduct || $movements->hasPages())
            {{ $movements->links('vendor.pagination.toko', ['label' => 'mutasi']) }}
        @endif
    </section>
@endsection
