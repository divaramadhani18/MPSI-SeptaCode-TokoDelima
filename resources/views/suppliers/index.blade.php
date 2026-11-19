@extends('layouts.app')

@section('title', 'Data supplier')
@section('heading', 'Data supplier')

@section('content')
    <div class="page-lead"><div><h2>Data supplier</h2><p>Simpan kontak pemasok agar pencatatan pembelian lebih cepat.</p></div><a href="{{ route('suppliers.create') }}" class="button button-primary">＋ Tambah supplier</a></div>
    <section class="panel">
        <form method="GET" class="filter-row supplier-filter">
            <label class="filter-title" for="supplier-search">Cari supplier</label>
            <label class="search-field"><span>⌕</span><input id="supplier-search" type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama, kode, atau kontak supplier..."></label>
            <button class="button button-light" type="submit">Cari</button>
        </form>
        <div class="table-wrap"><table>
            <thead><tr><th>Kode</th><th>Nama supplier</th><th>Kontak</th><th>Telepon</th><th>Alamat</th><th>Aksi</th></tr></thead>
            <tbody>
                @forelse ($suppliers as $supplier)
                    <tr><td>{{ $supplier->code }}</td><td><strong>{{ $supplier->name }}</strong></td><td>{{ $supplier->contact_name ?: '—' }}</td><td>{{ $supplier->phone ?: '—' }}</td><td>{{ $supplier->address ?: '—' }}</td><td><a class="text-link" href="{{ route('suppliers.edit', $supplier) }}">Edit</a></td></tr>
                @empty
                    <tr><td colspan="6" class="empty-table">Belum ada supplier yang sesuai dengan pencarian.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="pagination-wrap">{{ $suppliers->links('vendor.pagination.toko', ['label' => 'supplier']) }}</div>
    </section>
@endsection
