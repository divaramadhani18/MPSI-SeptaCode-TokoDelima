@extends('layouts.app')

@section('title', 'Struk '.$sale->receipt_no)
@section('heading', 'Pratinjau struk')

@section('content')
    <section class="receipt">
        <a class="receipt-close no-print" href="{{ route('sales.create') }}" aria-label="Tutup struk">×</a>
        <header class="receipt-header">
            <img class="receipt-brand-logo" src="{{ asset('images/toko-delima-logo.png') }}" alt="Toko Delima">
            <h2>STRUK PENJUALAN</h2>
        </header>
        <div class="receipt-meta">
            <span>Nota</span><strong>{{ $sale->receipt_no }}</strong>
            <span>Tanggal</span><strong>{{ $sale->sold_at->translatedFormat('d M Y, H.i') }}</strong>
            <span>Petugas</span><strong>{{ $sale->user->name }}</strong>
        </div>
        <div class="receipt-rule"></div>
        <div class="receipt-lines">
            @foreach ($sale->items as $item)
                <div class="receipt-item">
                    <strong>{{ $item->product_name }}</strong>
                    <span class="receipt-item-detail">{{ $item->quantity }} × Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</span>
                    <span class="receipt-item-total">Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</span>
                </div>
            @endforeach
        </div>
        <div class="receipt-rule"></div>
        <div class="receipt-amount">
            <span class="receipt-total-label">TOTAL</span><strong class="receipt-total-value">Rp {{ number_format((float) $sale->total_amount, 0, ',', '.') }}</strong>
            <span>{{ $sale->payment_method === 'cash' ? 'Tunai' : 'Transfer' }}</span><strong>Rp {{ number_format((float) $sale->amount_received, 0, ',', '.') }}</strong>
            @if ($sale->payment_method === 'cash')
                <span class="receipt-change-label">Kembalian</span><strong class="receipt-change-value">Rp {{ number_format((float) $sale->change_amount, 0, ',', '.') }}</strong>
            @endif
        </div>
        <p class="receipt-thanks">Terima kasih telah berbelanja.</p>
    </section>
    <div class="receipt-actions no-print"><a href="{{ route('sales.create') }}" class="button button-light">Transaksi baru</a><button type="button" class="button button-primary" onclick="window.print()">Cetak struk</button></div>
@endsection
