<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · Toko Delima</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-page">
    <main class="login-card">
        <div class="login-body">
            <section class="login-form-panel">
                <a class="brand login-brand" href="{{ route('login') }}">
                    <img class="login-brand-logo" src="{{ asset('images/toko-delima-logo.png') }}" alt="Toko Delima">
                </a>
                <div class="login-intro">
                    <h1>Masuk ke Toko Delima</h1>
                    <p>Gunakan akun yang diberikan.</p>
                </div>
                @if ($errors->any())
                    <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="{{ route('login.store') }}" class="form-stack">
                    @csrf
                    <label>Username
                        <input type="text" name="username" value="{{ old('username') }}" autocomplete="username" required>
                    </label>
                    <div class="password-control">
                        <label for="login-password">Password</label>
                        <span class="password-field">
                            <input id="login-password" type="password" name="password" autocomplete="current-password" data-password-input required>
                            <button type="button" class="password-toggle" data-password-toggle aria-label="Tampilkan password" aria-pressed="false">
                                <svg data-password-show-icon viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                                    <circle cx="12" cy="12" r="2.7" stroke="currentColor" stroke-width="1.8"/>
                                </svg>
                                <svg data-password-hide-icon viewBox="0 0 24 24" fill="none" aria-hidden="true" hidden>
                                    <path d="M3 3l18 18M10.6 6.2A10.7 10.7 0 0 1 12 6c6.1 0 9.5 6 9.5 6a16.5 16.5 0 0 1-3.1 3.7M6.2 6.8C3.9 8.3 2.5 12 2.5 12s3.4 6 9.5 6c1.2 0 2.3-.3 3.3-.7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </span>
                    </div>
                    <button type="submit" class="button button-primary button-wide">Masuk</button>
                </form>
            </section>
            <aside class="login-art" aria-hidden="true"></aside>
        </div>
    </main>
    <script>
        document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
            const input = toggle.closest('.password-field').querySelector('[data-password-input]');
            const showIcon = toggle.querySelector('[data-password-show-icon]');
            const hideIcon = toggle.querySelector('[data-password-hide-icon]');

            toggle.addEventListener('click', () => {
                const isVisible = input.type === 'text';
                input.type = isVisible ? 'password' : 'text';
                toggle.setAttribute('aria-pressed', String(!isVisible));
                toggle.setAttribute('aria-label', isVisible ? 'Tampilkan password' : 'Sembunyikan password');
                showIcon.hidden = !isVisible;
                hideIcon.hidden = isVisible;
            });
        });
    </script>
</body>
</html>
