@extends('layouts.app')

@section('title', 'Laporan pembelian')
@section('heading', 'Laporan pembelian')

@section('content')
    <div class="page-lead purchase-report-lead">
        <div><h2>Laporan pembelian</h2><p>Rekap pembelian berdasarkan tanggal penerimaan barang yang sudah disimpan.</p></div>
        <button class="button button-light no-print" type="button" data-print-purchases>
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 8V3h10v5M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2m-10-4h10v8H7z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Cetak laporan
        </button>
    </div>

    <section class="panel purchase-report-filter-panel no-print">
        <div class="panel-heading"><h3>Filter laporan</h3></div>
        <form method="GET" class="purchase-report-filters">
            <label>Tanggal mulai<input type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
            <label>Tanggal akhir<input type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
            <label>Supplier
                <select name="supplier_id">
                    <option value="">Semua supplier</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string) ($filters['supplier_id'] ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </label>
            <button class="button button-primary" type="submit">Tampilkan</button>
        </form>
    </section>

    <section class="purchase-report-summary" aria-label="Ringkasan laporan pembelian">
        <article class="purchase-report-card"><span>Total pembelian</span><strong>Rp {{ number_format((float) $total, 0, ',', '.') }}</strong><small>Penerimaan tersimpan</small></article>
        <article class="purchase-report-card"><span>Jumlah transaksi</span><strong>{{ number_format($transactionCount, 0, ',', '.') }}</strong><small>Dari {{ number_format($supplierCount, 0, ',', '.') }} supplier</small></article>
        <article class="purchase-report-card"><span>Unit diterima</span><strong>{{ $unitSummary }}</strong><small>{{ $purchases->total() ? $purchases->total().' transaksi tersimpan' : 'Belum ada barang diterima' }}</small></article>
    </section>

    @php
        $periodStart = isset($filters['from'])
            ? \Illuminate\Support\Carbon::parse($filters['from'])->translatedFormat('d F Y')
            : null;
        $periodEnd = isset($filters['to'])
            ? \Illuminate\Support\Carbon::parse($filters['to'])->translatedFormat('d F Y')
            : null;
        $periodLabel = $periodStart && $periodEnd
            ? ($periodStart === $periodEnd ? $periodStart : $periodStart.' – '.$periodEnd)
            : ($periodStart ? $periodStart.' – sekarang' : ($periodEnd ? 'Sampai '.$periodEnd : 'Semua tanggal'));
    @endphp
    <div class="purchase-report-meta">
        <strong>Periode {{ $periodLabel }}</strong>
        <span>Diperbarui {{ $reportUpdatedAt->translatedFormat('d F Y, H.i') }}</span>
    </div>

    <section class="panel purchase-report-table-panel">
        <div class="table-wrap">
            <table class="purchase-report-table">
                <thead><tr><th>Tanggal / waktu</th><th>Nomor pembelian</th><th>Supplier</th><th>Jumlah barang</th><th>Unit diterima</th><th>Total pembelian</th></tr></thead>
                <tbody>
                    @forelse ($purchases as $purchase)
                        @php
                            $purchaseNumber = 'PB-'.$purchase->purchased_at->format('dmy').'-'.str_pad((string) $purchase->id, 3, '0', STR_PAD_LEFT);
                            $purchaseUnits = $purchase->items
                                ->groupBy(fn ($item) => $item->product->unit)
                                ->map(fn ($items, $unit) => number_format($items->sum('quantity'), 0, ',', '.').' '.$unit)
                                ->join(', ');
                        @endphp
                        <tr>
                            <td>{{ $purchase->purchased_at->format('d/m/Y H:i') }}</td>
                            <td><strong>{{ $purchaseNumber }}</strong></td>
                            <td><strong>{{ $purchase->supplier->name }}</strong><small class="cell-sub">{{ $purchase->supplier->code }}</small></td>
                            <td>{{ $purchase->items->count() }} jenis</td>
                            <td>{{ $purchaseUnits }}</td>
                            <td><strong>Rp {{ number_format((float) $purchase->total_amount, 0, ',', '.') }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-table">Belum ada pembelian yang sesuai dengan filter.</td></tr>
                    @endforelse
                </tbody>
                @if ($purchases->total())
                    <tfoot><tr><td><strong>Total periode</strong></td><td>{{ number_format($transactionCount, 0, ',', '.') }} transaksi</td><td>{{ number_format($supplierCount, 0, ',', '.') }} supplier</td><td>{{ number_format($itemTypeCount, 0, ',', '.') }} jenis</td><td>{{ $unitSummary }}</td><td><strong>Rp {{ number_format((float) $total, 0, ',', '.') }}</strong></td></tr></tfoot>
                @endif
            </table>
        </div>
        @php
            $supplierNotes = $purchases->getCollection()->pluck('supplier_note')->filter()->unique()->values();
        @endphp
        <p class="purchase-report-footnote">
            Menampilkan {{ number_format($transactionCount, 0, ',', '.') }} transaksi tersimpan
            @if ($supplierNotes->isNotEmpty())
                • Nota supplier: {{ $supplierNotes->join(', ') }}
            @endif
        </p>
        @if ($purchases->hasPages())
            <div class="pagination-wrap">{{ $purchases->links('vendor.pagination.toko', ['label' => 'transaksi']) }}</div>
        @endif
    </section>

    <section class="purchase-report-details" aria-label="Rincian transaksi">
        @forelse ($purchases as $purchase)
            @php
                $purchaseNumber = 'PB-'.$purchase->purchased_at->format('dmy').'-'.str_pad((string) $purchase->id, 3, '0', STR_PAD_LEFT);
            @endphp
            <article class="panel purchase-detail-card">
                <h3>Rincian transaksi {{ $purchaseNumber }}</h3>
                <p class="purchase-detail-lines">
                    @foreach ($purchase->items as $item)
                        @if (! $loop->first) • @endif
                        {{ $item->product->code }} — {{ $item->product->name }} • {{ number_format($item->quantity, 0, ',', '.') }} {{ $item->product->unit }} × Rp {{ number_format((float) $item->unit_cost, 0, ',', '.') }} = <strong>Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</strong>
                    @endforeach
                    • Petugas: {{ $purchase->user->name }}
                </p>
                @if ($purchase->note)<p class="purchase-detail-note">Catatan: {{ $purchase->note }}</p>@endif
            </article>
        @empty
            <article class="panel purchase-detail-card"><h3>Rincian transaksi</h3><p>Rincian akan tampil setelah ada transaksi penerimaan.</p></article>
        @endforelse
    </section>
@endsection

@push('scripts')
    <script>
        document.querySelector('[data-print-purchases]')?.addEventListener('click', () => window.print());
    </script>
@endpush
