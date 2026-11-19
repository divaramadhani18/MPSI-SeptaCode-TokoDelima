@extends('layouts.app')

@section('title', 'Transaksi')
@section('heading', 'Transaksi')

@section('content')
    <div class="page-lead"><div><h2>Transaksi</h2></div></div>
    <section class="panel sales-products">
        <label class="sales-search-label" for="product-search">Cari nama atau kode barang</label>
        <div class="sales-search-row">
            <label class="search-field sales-search"><span aria-hidden="true">⌕</span><input id="product-search" type="search" placeholder="Ketik kode atau nama barang" autocomplete="off"></label>
            <button type="button" class="button button-light sales-search-button" id="search-products">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                Cari
            </button>
        </div>
        <div id="search-results" class="search-results" aria-live="polite"><p class="search-hint">Hasil pencarian akan muncul di sini.</p></div>
    </section>
    <form class="sales-checkout" method="POST" action="{{ route('sales.store') }}" id="sale-form">
        @csrf
        <section class="panel cart-panel">
            <div class="panel-heading"><div><h3>Keranjang <span aria-hidden="true">•</span> <span id="cart-count">0 item</span></h3></div><button type="button" class="link-button" id="clear-cart">Kosongkan</button></div>
            <div class="table-wrap"><table class="cart-table">
                <thead><tr><th>Barang / kode</th><th>Harga jual</th><th>Qty</th><th>Subtotal</th><th>Aksi</th></tr></thead>
                <tbody id="cart-items"><tr><td colspan="5" class="empty-table">Keranjang masih kosong.</td></tr></tbody>
            </table></div>
        </section>
        <section class="panel checkout-panel">
            <div class="cart-summary">
                <div class="cart-total"><span id="checkout-count">Total • 0 item</span><strong id="cart-total">Rp 0</strong></div>
                <div class="checkout-fields">
                    <label>Metode pembayaran
                        <select name="payment_method" id="payment-method" required><option value="" selected disabled>Metode Pembayaran</option><option value="cash">Tunai</option><option value="transfer">Transfer</option></select>
                    </label>
                    <label id="received-label">Uang diterima (Rp) <span class="required">*</span>
                        <input type="number" name="amount_received" id="amount-received" min="0" step="0.01" value="0" required>
                    </label>
                </div>
                <div class="change-line"><span>Kembalian</span><strong id="change-amount">Rp 0</strong></div>
                <div class="checkout-actions">
                    <small class="cart-disclaimer">Transaksi yang sudah selesai tidak dapat diubah atau dibatalkan.</small>
                    <button class="button button-primary" id="checkout-button" type="submit" disabled>
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 8V3h10v5M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2m-10-4h10v8H7z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Bayar &amp; cetak struk
                    </button>
                </div>
            </div>
        </section>
    </form>
@endsection

@push('scripts')
    <script>
        (() => {
            const search = document.getElementById('product-search');
            const results = document.getElementById('search-results');
            const body = document.getElementById('cart-items');
            const totalText = document.getElementById('cart-total');
            const countText = document.getElementById('cart-count');
            const payment = document.getElementById('payment-method');
            const received = document.getElementById('amount-received');
            const changeText = document.getElementById('change-amount');
            const submit = document.getElementById('checkout-button');
            const checkoutCount = document.getElementById('checkout-count');
            const cart = new Map();
            let timer;
            const format = (amount) => `Rp ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(amount || 0)}`;

            function make(tag, className, text) {
                const element = document.createElement(tag);
                if (className) element.className = className;
                if (text !== undefined) element.textContent = text;
                return element;
            }

            function total() {
                return [...cart.values()].reduce((sum, item) => sum + Number(item.selling_price) * item.quantity, 0);
            }

            function renderCart() {
                body.replaceChildren();
                if (cart.size === 0) {
                    const row = make('tr');
                    const cell = make('td', 'empty-table', 'Keranjang masih kosong.');
                    cell.colSpan = 5;
                    row.append(cell);
                    body.append(row);
                }

                let index = 0;
                for (const item of cart.values()) {
                    const row = make('tr');
                    const name = make('td');
                    name.append(make('strong', '', item.name), make('small', 'cell-sub', item.code));
                    const price = make('td', '', format(Number(item.selling_price)));
                    const quantityCell = make('td');
                    const quantity = make('input', 'quantity-input');
                    quantity.type = 'number';
                    quantity.min = '1';
                    quantity.max = String(item.current_stock);
                    quantity.value = String(item.quantity);
                    quantity.setAttribute('aria-label', `Jumlah ${item.name}`);
                    quantity.addEventListener('change', () => {
                        item.quantity = Math.max(1, Math.min(item.current_stock, Number(quantity.value) || 1));
                        renderCart();
                    });
                    quantityCell.append(quantity);
                    const subtotal = make('td', '', format(Number(item.selling_price) * item.quantity));
                    const action = make('td');
                    const remove = make('button', 'link-button', 'Hapus');
                    remove.type = 'button';
                    remove.addEventListener('click', () => {
                        cart.delete(item.id);
                        renderCart();
                    });
                    action.append(remove);
                    row.append(name, price, quantityCell, subtotal, action);
                    body.append(row);
                    index += 1;
                }

                const currentTotal = total();
                totalText.textContent = format(currentTotal);
                const itemCount = [...cart.values()].reduce((sum, item) => sum + item.quantity, 0);
                countText.textContent = `${itemCount} item`;
                checkoutCount.textContent = `Total • ${itemCount} item`;
                submit.disabled = cart.size === 0 || !payment.value;
                if (payment.value === 'transfer') received.value = currentTotal.toFixed(2);
                const amount = Number(received.value) || 0;
                changeText.textContent = format(payment.value === 'cash' ? Math.max(0, amount - currentTotal) : 0);
            }

            function addProduct(product) {
                if (product.current_stock < 1) {
                    results.replaceChildren(make('p', 'search-hint', 'Stok barang ini habis dan tidak dapat dijual.'));
                    return;
                }
                const existing = cart.get(product.id);
                if (existing) existing.quantity = Math.min(existing.current_stock, existing.quantity + 1);
                else cart.set(product.id, { ...product, quantity: 1 });
                search.value = '';
                results.replaceChildren(make('p', 'search-hint', 'Barang berhasil ditambahkan ke keranjang.'));
                renderCart();
            }

            async function findProducts(term) {
                results.replaceChildren(make('p', 'search-hint', 'Mencari barang…'));
                try {
                    const response = await fetch(`{{ route('sales.search') }}?q=${encodeURIComponent(term)}`, {
                        headers: { Accept: 'application/json' },
                    });
                    if (!response.ok) throw new Error('Pencarian barang gagal. Coba lagi.');
                    const products = await response.json();
                    results.replaceChildren();
                    if (products.length === 0) {
                        results.append(make('p', 'search-hint', 'Barang tidak ditemukan.'));
                        return;
                    }
                    for (const product of products) {
                        const button = make('div', 'product-result');
                        const details = make('span', 'product-result-details');
                        details.append(make('strong', '', `${product.code} · ${product.name}`));
                        details.append(make('small', '', `${format(Number(product.selling_price))} / ${product.unit} · ${product.current_stock} tersedia`));
                        const add = make('button', 'button button-light product-result-add', '＋ Tambah');
                        add.type = 'button';
                        button.append(details, add);
                        add.disabled = product.current_stock < 1;
                        add.addEventListener('click', () => addProduct(product));
                        results.append(button);
                    }
                } catch (error) {
                    results.replaceChildren(make('p', 'search-hint error-text', error.message));
                }
            }

            search.addEventListener('input', () => {
                clearTimeout(timer);
                const term = search.value.trim();
                if (!term) {
                    results.replaceChildren(make('p', 'search-hint', 'Hasil pencarian akan muncul di sini.'));
                    return;
                }
                timer = setTimeout(() => findProducts(term), 250);
            });
            document.getElementById('search-products').addEventListener('click', () => {
                clearTimeout(timer);
                const term = search.value.trim();
                if (term) findProducts(term);
            });
            search.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    clearTimeout(timer);
                    const term = search.value.trim();
                    if (term) findProducts(term);
                }
            });

            document.getElementById('clear-cart').addEventListener('click', () => {
                cart.clear();
                renderCart();
            });
            payment.addEventListener('change', () => {
                const transfer = payment.value === 'transfer';
                received.readOnly = transfer;
                document.getElementById('received-label').firstChild.textContent = transfer ? 'Jumlah transfer (Rp) ' : 'Uang diterima (Rp) ';
                if (transfer) received.value = total().toFixed(2);
                renderCart();
            });
            received.addEventListener('input', renderCart);
            document.getElementById('sale-form').addEventListener('submit', (event) => {
                event.currentTarget.querySelectorAll('.cart-hidden-input').forEach((input) => input.remove());
                if (cart.size === 0) {
                    event.preventDefault();
                    return;
                }
                let index = 0;
                for (const item of cart.values()) {
                    for (const [field, value] of [['product_id', item.id], ['quantity', item.quantity]]) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `items[${index}][${field}]`;
                        input.value = String(value);
                        input.className = 'cart-hidden-input';
                        event.currentTarget.append(input);
                    }
                    index += 1;
                }
            });
        })();
    </script>
@endpush
