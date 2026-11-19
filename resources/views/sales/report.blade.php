@extends('layouts.app')

@section('title', 'Laporan penjualan')
@section('heading', 'Laporan penjualan')

@section('content')
    <div class="page-lead sales-report-lead">
        <div><h2>Laporan penjualan</h2><p>Pantau penjualan harian atau bulanan dari transaksi yang telah dibayar.</p></div>
        <button class="button button-light no-print" type="button" onclick="window.print()">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 8V3h10v5M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2M7 14h10v7H7z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Cetak laporan
        </button>
    </div>

    <section class="panel sales-report-filter-panel">
        <h3>Filter laporan</h3>
        <form method="GET" class="sales-report-filter-form">
            <input type="hidden" name="period" id="report-period" value="{{ $period }}">
            <div class="sales-report-period-tabs" role="group" aria-label="Periode laporan">
                <button type="button" class="sales-report-period-tab {{ $period === 'day' ? 'active' : '' }}" data-report-period="day" aria-pressed="{{ $period === 'day' ? 'true' : 'false' }}">Harian</button>
                <button type="button" class="sales-report-period-tab {{ $period === 'month' ? 'active' : '' }}" data-report-period="month" aria-pressed="{{ $period === 'month' ? 'true' : 'false' }}">Bulanan</button>
            </div>
            <div class="sales-report-filter-fields">
                <div id="daily-filter" class="sales-report-date-range" @if ($period !== 'day') hidden @endif>
                    <label>Tanggal mulai<input type="date" name="date_start" value="{{ $dateStart }}"></label>
                    <label>Tanggal akhir<input type="date" name="date_end" value="{{ $dateEnd }}"></label>
                </div>
                <label id="monthly-filter" @if ($period !== 'month') hidden @endif>Bulan<input type="month" name="month" value="{{ $month }}"></label>
                <button class="button button-primary" type="submit">Tampilkan</button>
            </div>
        </form>
    </section>

    <section class="sales-report-summary" aria-label="Ringkasan penjualan">
        <article class="sales-report-card"><span>Omzet tunai</span><strong>Rp{{ number_format((float) $total, 0, ',', '.') }}</strong></article>
        <article class="sales-report-card"><span>Jumlah transaksi</span><strong>{{ number_format($transactionCount, 0, ',', '.') }}</strong></article>
        <article class="sales-report-card"><span>Item terjual</span><strong>{{ number_format((float) $itemCount, 0, ',', '.') }}</strong></article>
    </section>

    <section class="panel sales-report-table-panel">
        <h3>{{ $period === 'day' ? 'Rekap harian' : 'Rekap Bulanan' }}@if ($period === 'day') • {{ $dateStart === $dateEnd ? \Illuminate\Support\Carbon::parse($dateStart)->translatedFormat('d F Y') : \Illuminate\Support\Carbon::parse($dateStart)->translatedFormat('d F Y').' – '.\Illuminate\Support\Carbon::parse($dateEnd)->translatedFormat('d F Y') }}@endif</h3>
        <div class="table-wrap">
            <table class="sales-report-table">
                <thead><tr>
                    <th>{{ $period === 'day' ? 'Tanggal / waktu' : 'Bulan' }}</th>
                    <th>Nomor nota</th>
                    <th>Petugas</th>
                    <th>Jumlah item</th>
                    <th>Total tunai</th>
                </tr></thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td>{{ $period === 'day' ? $sale->sold_at->format('d M Y / H:i') : $sale->sold_at->translatedFormat('F') }}</td>
                            <td><a class="text-link" href="{{ route('sales.receipt', $sale) }}">{{ $sale->receipt_no }}</a></td>
                            <td>{{ $sale->user->name }}</td>
                            <td>{{ number_format((float) $sale->items->sum('quantity'), 0, ',', '.') }}</td>
                            <td>Rp{{ number_format((float) $sale->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-table">Belum ada transaksi pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="sales-report-print-table">
            <table class="sales-report-table">
                <thead><tr>
                    <th>{{ $period === 'day' ? 'Tanggal / waktu' : 'Bulan' }}</th>
                    <th>Nomor nota</th>
                    <th>Petugas</th>
                    <th>Jumlah item</th>
                    <th>Total tunai</th>
                </tr></thead>
                <tbody>
                    @forelse ($printSales as $sale)
                        <tr>
                            <td>{{ $period === 'day' ? $sale->sold_at->format('d M Y / H:i') : $sale->sold_at->translatedFormat('F') }}</td>
                            <td>{{ $sale->receipt_no }}</td>
                            <td>{{ $sale->user->name }}</td>
                            <td>{{ number_format((float) $sale->items->sum('quantity'), 0, ',', '.') }}</td>
                            <td>Rp{{ number_format((float) $sale->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-table">Belum ada transaksi pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="sales-report-footer">
            <span>Menampilkan {{ number_format($sales->total(), 0, ',', '.') }} transaksi</span>
            <div class="pagination-wrap">{{ $sales->links() }}</div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        const period = document.getElementById('report-period');
        const daily = document.getElementById('daily-filter');
        const monthly = document.getElementById('monthly-filter');
        document.querySelectorAll('[data-report-period]').forEach((button) => {
            button.addEventListener('click', () => {
                period.value = button.dataset.reportPeriod;
                const isMonth = period.value === 'month';
                daily.hidden = isMonth;
                monthly.hidden = !isMonth;
                document.querySelectorAll('[data-report-period]').forEach((tab) => {
                    const selected = tab === button;
                    tab.classList.toggle('active', selected);
                    tab.setAttribute('aria-pressed', selected ? 'true' : 'false');
                });
            });
        });
    </script>
@endpush
