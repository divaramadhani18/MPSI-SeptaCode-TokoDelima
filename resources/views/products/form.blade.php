@extends('layouts.app')

@section('title', $product->exists ? 'Edit barang' : 'Tambah barang')
@section('heading', $product->exists ? 'Edit barang' : 'Tambah barang')

@section('content')
    <div class="page-lead"><div><h2>{{ $product->exists ? 'Edit barang' : 'Panel Tambah Barang' }}</h2></div><a href="{{ route('products.index') }}" class="button button-light">← Kembali</a></div>
    <section class="panel form-panel">
        <div class="panel-heading"><h3>{{ $product->exists ? 'Edit barang' : 'Tambah barang' }}</h3></div>
        <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" class="form-grid product-form-grid">
            @csrf @if ($product->exists) @method('PUT') @endif
            <label class="product-form-code">Kode barang <span class="required">*</span><input name="code" value="{{ old('code', $product->code) }}" required maxlength="40"></label>
            <label class="product-form-name">Nama barang <span class="required">*</span><input name="name" value="{{ old('name', $product->name) }}" placeholder="Masukkan nama barang" required></label>
            <label class="product-form-category">Kategori <span class="required">*</span>
                <select name="category" required>
                    <option value="">Pilih kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected(old('category', $product->category) === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </label>
            <label class="product-form-unit">Satuan <span class="required">*</span>
                <select name="unit" required>
                    <option value="">Pilih satuan</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit }}" @selected(old('unit', $product->unit) === $unit)>{{ $unit }}</option>
                    @endforeach
                </select>
            </label>
            <label class="product-form-purchase">Harga beli (Rp) <span class="required">*</span><input type="number" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}" min="0.01" step="0.01" required></label>
            <label class="product-form-selling">Harga jual (Rp) <span class="required">*</span><input type="number" name="selling_price" value="{{ old('selling_price', $product->selling_price) }}" min="0.01" step="0.01" required></label>
            <label class="product-form-minimum">Batas stok minimum <span class="required">*</span><input type="number" name="min_stock" value="{{ old('min_stock', $product->min_stock ?? 0) }}" min="0" step="1" required></label>
            <div class="form-actions"><a href="{{ route('products.index') }}" class="button button-light">Batal</a><button type="submit" class="button button-primary">Simpan barang</button></div>
        </form>
    </section>
@endsection
