@extends('layouts.app')

@section('title', $supplier->exists ? 'Edit supplier' : 'Tambah supplier')
@section('heading', $supplier->exists ? 'Edit supplier' : 'Tambah supplier')

@section('content')
    <section class="panel form-panel">
        <div class="panel-heading"><h3>{{ $supplier->exists ? 'Edit supplier' : 'Tambah supplier' }}</h3></div>
        <form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}" class="form-grid supplier-form-grid">
            @csrf @if ($supplier->exists) @method('PUT') @endif
            <label>Kode supplier <span class="required">*</span><input name="code" value="{{ old('code', $supplier->code) }}" required maxlength="40"></label>
            <label>Nama supplier <span class="required">*</span><input name="name" value="{{ old('name', $supplier->name) }}" required></label>
            <label>Nama kontak<input name="contact_name" value="{{ old('contact_name', $supplier->contact_name) }}"></label>
            <label class="supplier-form-phone">Nomor telepon<input name="phone" value="{{ old('phone', $supplier->phone) }}" inputmode="tel"></label>
            <label class="full-span">Alamat<textarea name="address" rows="3">{{ old('address', $supplier->address) }}</textarea></label>
            <div class="form-actions"><a href="{{ route('suppliers.index') }}" class="button button-light">Batal</a><button type="submit" class="button button-primary">Simpan supplier</button></div>
        </form>
    </section>
@endsection
