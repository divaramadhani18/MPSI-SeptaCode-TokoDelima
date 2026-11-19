@extends('layouts.app')

@section('title', 'Catat pembelian')
@section('heading', 'Catat pembelian')

@section('content')
    <div class="page-lead"><div><h2>Catat pembelian dan penerimaan</h2><p>Catat barang yang benar-benar diterima.</p></div></div>
    @if ($suppliers->isEmpty() || $products->isEmpty())
        <div class="stock-alert"><span class="alert-symbol">!</span><span><strong>Data supplier dan barang diperlukan</strong><small>Pastikan setidaknya ada satu supplier dan barang aktif sebelum mencatat pembelian.</small></span><a href="{{ route('suppliers.create') }}">Tambah supplier</a></div>
    @endif
    <form method="POST" action="{{ route('purchases.store') }}" class="form-stack" id="purchase-form">
        @csrf
        <section class="panel">
            <div class="panel-heading"><div><h3>Informasi penerimaan</h3></div></div>
            <div class="purchase-info-grid">
                <label>Supplier <span class="required">*</span><select name="supplier_id" required><option value="">Pilih supplier</option>@foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->code }} · {{ $supplier->name }}</option>@endforeach</select></label>
                <label>Tanggal penerimaan <span class="required">*</span><input type="date" name="purchased_at" value="{{ old('purchased_at', now()->format('Y-m-d')) }}" required></label>
                <label>Nota supplier (opsional)<input name="supplier_note" value="{{ old('supplier_note') }}" maxlength="100" placeholder="Contoh: SP-301026-018"></label>
            </div>
        </section>
        <section class="panel purchase-items-panel">
            <div class="purchase-search-row">
                <label for="purchase-product-search">Cari barang untuk diterima</label>
                <div class="purchase-search-controls">
                    <input id="purchase-product-search" type="search" list="purchase-product-options" placeholder="Cari kode atau nama barang..." autocomplete="off" @disabled($products->isEmpty())>
                    <datalist id="purchase-product-options">
                        @foreach ($productOptions as $product)
                            <option value="{{ $product['code'] }} — {{ $product['name'] }}"></option>
                        @endforeach
                    </datalist>
                    <button type="button" class="button button-light" id="add-purchase-row" @disabled($products->isEmpty())>＋ Tambah ke daftar</button>
                </div>
                <span class="purchase-search-error" id="purchase-search-error" role="status" aria-live="polite"></span>
            </div>
            <div class="table-wrap"><table class="entry-table">
                <thead><tr><th>Kode / barang</th><th>Satuan</th><th>Jumlah diterima</th><th>Harga beli</th><th>Subtotal</th><th>Aksi</th></tr></thead>
                <tbody id="purchase-items"></tbody>
            </table></div>
        </section>
        <label class="purchase-note">Catatan (opsional)<textarea name="note" rows="2" placeholder="Tambahkan catatan penerimaan...">{{ old('note') }}</textarea></label>
        <div class="form-actions purchase-form-actions"><button type="submit" class="button button-primary" @disabled($suppliers->isEmpty() || $products->isEmpty())>Simpan penerimaan</button></div>
    </form>
@endsection

@push('scripts')
    <script>
        (() => {
            const products = @json($productOptions);
            const tbody = document.getElementById('purchase-items');
            const search = document.getElementById('purchase-product-search');
            const searchError = document.getElementById('purchase-search-error');
            const format = (amount) => `Rp ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(amount || 0)}`;
            let rowNumber = 0;

            function recalculate() {
                tbody.querySelectorAll('tr').forEach((row) => {
                    const quantity = Number(row.querySelector('[data-quantity]').value) || 0;
                    const cost = Number(row.querySelector('[data-cost]').value) || 0;
                    const subtotal = quantity * cost;
                    row.querySelector('[data-subtotal]').textContent = format(subtotal);
                });
            }

            function addRow(product) {
                const row = document.createElement('tr');
                const productCell = document.createElement('td');
                const productId = document.createElement('input');
                productId.type = 'hidden';
                productId.name = `items[${rowNumber}][product_id]`;
                productId.dataset.productId = '';
                productId.value = product.id;
                const productName = document.createElement('strong');
                productName.textContent = `${product.code} / ${product.name}`;
                productCell.append(productId, productName);
                const unitCell = document.createElement('td');
                unitCell.textContent = product.unit;
                const quantityCell = document.createElement('td');
                const quantity = document.createElement('input');
                quantity.type = 'number';
                quantity.name = `items[${rowNumber}][quantity]`;
                quantity.min = '1';
                quantity.step = '1';
                quantity.value = '1';
                quantity.required = true;
                quantity.dataset.quantity = '';
                quantityCell.append(quantity);
                const costCell = document.createElement('td');
                const cost = document.createElement('input');
                cost.type = 'number';
                cost.name = `items[${rowNumber}][unit_cost]`;
                cost.min = '0.01';
                cost.step = '0.01';
                cost.value = product.cost;
                cost.required = true;
                cost.dataset.cost = '';
                costCell.append(cost);
                const subtotal = document.createElement('td');
                subtotal.dataset.subtotal = '';
                subtotal.textContent = 'Rp 0';
                const actionCell = document.createElement('td');
                const remove = document.createElement('button');
                remove.className = 'link-button';
                remove.type = 'button';
                remove.textContent = 'Hapus';
                remove.setAttribute('aria-label', 'Hapus baris');
                actionCell.append(remove);
                row.append(productCell, unitCell, quantityCell, costCell, subtotal, actionCell);
                row.addEventListener('input', recalculate);
                remove.addEventListener('click', () => {
                    row.remove();
                    recalculate();
                });
                tbody.append(row);
                rowNumber += 1;
                search.value = '';
                searchError.textContent = '';
                recalculate();
            }

            document.getElementById('add-purchase-row').addEventListener('click', () => {
                const searchTerm = search.value.trim().toLocaleLowerCase('id-ID');
                const product = products.find((item) => [
                    item.code,
                    item.name,
                    `${item.code} — ${item.name}`,
                ].some((value) => value.toLocaleLowerCase('id-ID') === searchTerm));
                if (!product) {
                    searchError.textContent = 'Barang tidak ditemukan. Pilih kode atau nama barang dari daftar.';
                    search.focus();
                    return;
                }
                if ([...tbody.querySelectorAll('[data-product-id]')].some((input) => input.value === String(product.id))) {
                    searchError.textContent = 'Barang tersebut sudah ada di daftar.';
                    search.focus();
                    return;
                }
                addRow(product);
            });
        })();
    </script>
@endpush
