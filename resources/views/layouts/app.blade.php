<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Toko Delima') · Toko Delima</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="topbar">
        <a class="header-brand" href="{{ route('dashboard') }}" aria-label="Toko Delima">
            <img src="{{ asset('images/toko-delima-logo.png') }}" alt="">
        </a>
        <div class="topbar-user">
            <span class="user-role">{{ ucfirst(auth()->user()->role) }}</span>
            <span class="topbar-name">{{ auth()->user()->name }}</span>
            <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
        </div>
    </header>
    <div class="app-shell">
        <aside class="sidebar">
            <nav class="navigation" aria-label="Navigasi utama">
                @if (in_array(auth()->user()->role, ['owner', 'supervisor'], true))
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" title="Ringkasan"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-6v-7h-4v7H4a1 1 0 0 1-1-1z"/></svg></span> Ringkasan</a>
                    <div class="nav-group-label"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5z"/><path d="m3 12 9 5 9-5m-18 4 9 5 9-5"/></svg></span> Stok</div>
                    <a class="nav-link nav-sub-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}" title="Data barang">Data barang</a>
                    <a class="nav-link nav-sub-link {{ request()->routeIs('stock.movements') ? 'active' : '' }}" href="{{ route('stock.movements') }}" title="Kartu stok">Kartu stok</a>
                    <a class="nav-link nav-sub-link {{ request()->routeIs('stock.report') ? 'active' : '' }}" href="{{ route('stock.report') }}" title="Laporan stok saat ini">Laporan stok saat ini</a>
                    <div class="nav-group-label"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 15V6a1 1 0 0 1 1-1h10v10M14 9h4l3 3v3h-7z"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg></span> Pembelian</div>
                    <a class="nav-link nav-sub-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}" title="Data supplier">Data supplier</a>
                    <a class="nav-link nav-sub-link {{ request()->routeIs('purchases.create') ? 'active' : '' }}" href="{{ route('purchases.create') }}" title="Catat pembelian">Catat pembelian</a>
                    @if (auth()->user()->role === 'owner')
                        <a class="nav-link nav-sub-link {{ request()->routeIs('purchases.report') ? 'active' : '' }}" href="{{ route('purchases.report') }}" title="Laporan pembelian">Laporan pembelian</a>
                    @endif
                @endif
                @if (in_array(auth()->user()->role, ['owner', 'kasir'], true))
                    <div class="nav-group-label"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.2 11h10.9l2-8H6"/><circle cx="9" cy="19" r="1"/><circle cx="17" cy="19" r="1"/></svg></span> Kasir</div>
                    <a class="nav-link nav-sub-link {{ request()->routeIs('sales.create') ? 'active' : '' }}" href="{{ route('sales.create') }}" title="Transaksi tunai">Transaksi tunai</a>
                    @if (auth()->user()->role === 'owner')
                        <a class="nav-link nav-sub-link {{ request()->routeIs('sales.report') ? 'active' : '' }}" href="{{ route('sales.report') }}" title="Laporan penjualan">Laporan penjualan</a>
                    @endif
                @endif
            </nav>
            <div class="sidebar-footer">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="logout-button" type="submit"><span class="nav-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5m5 5H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg></span> Keluar</button>
                </form>
            </div>
        </aside>

        <main class="main-area">
            <section class="page-content">
                @if (session('success'))
                    <div class="success-toast" role="status" aria-live="polite" data-success-toast>
                        <button class="success-toast-close" type="button" aria-label="Tutup notifikasi" data-success-toast-close>×</button>
                        <span class="success-toast-message">Data Berhasil Disimpan</span>
                        <span class="success-toast-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><path d="m6 12.5 4 4L18.5 8" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-error" role="alert">
                        <strong>Periksa kembali data yang dimasukkan.</strong>
                        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                @yield('content')
            </section>
        </main>
    </div>
    @stack('scripts')
</body>
</html>
