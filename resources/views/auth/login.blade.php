@extends('layouts.guest')
@section('title', 'เข้าสู่ระบบ')

@section('content')
    <div class="auth__welcome">
        <h1>ยินดีต้อนรับกลับ</h1>
        <p>ลงชื่อเข้าใช้ด้วยบัญชีที่ฝ่ายบุคคลออกให้</p>
    </div>

    <form method="POST" action="{{ route('login.store') }}">
        @csrf

        <x-input
            name="email"
            type="email"
            label="อีเมล"
            :value="old('email')"
            :required="true"
            placeholder="you@company.com"
            autocomplete="username"
            autofocus
        />

        <div class="field-gap auth__pw">
            <x-input
                name="password"
                type="password"
                label="รหัสผ่าน"
                :required="true"
                placeholder="••••••••"
                autocomplete="current-password"
            />
            {{-- Typing a password blind is the commonest reason a sign-in fails
                 twice in a row; the field starts masked and never autofills. --}}
            <button type="button" class="auth__pw-toggle" data-toggle="password"
                    aria-label="แสดงรหัสผ่าน" aria-pressed="false" title="แสดงรหัสผ่าน">
                <x-icon name="eye" width="18" height="18" />
            </button>
        </div>

        <div class="flex items-center justify-between" style="margin:18px 0 22px;">
            <label class="checkbox-row">
                <input type="checkbox" name="remember" value="1">
                <span>จดจำการเข้าสู่ระบบ</span>
            </label>
        </div>

        <x-button type="submit" class="btn--block" icon="login">เข้าสู่ระบบ</x-button>
    </form>

    {{-- Only on a local or demo install, and only for accounts still on their
         seeded password (App\Support\DefaultLogin). Production shows nothing. --}}
    @if($defaultLogins)
        @php($sharedPassword = count(array_unique(array_column($defaultLogins, 'password'))) === 1 ? $defaultLogins[0]['password'] : null)
        <section class="auth__demo" aria-labelledby="demo-logins">
            <h2 id="demo-logins"><x-icon name="info" />บัญชีทดลอง — คลิกเพื่อกรอกอัตโนมัติ</h2>
            <ul>
                @foreach($defaultLogins as $login)
                    <li>
                        <button type="button" class="auth__demo-row"
                                data-fill-email="{{ $login['email'] }}" data-fill-password="{{ $login['password'] }}">
                            <span class="auth__demo-role">{{ $login['role'] }}</span>
                            <span class="auth__demo-cred">{{ $login['email'] }}@unless($sharedPassword) · <code>{{ $login['password'] }}</code>@endunless</span>
                        </button>
                    </li>
                @endforeach
            </ul>
            <p>
                @if($sharedPassword)รหัสผ่าน <code>{{ $sharedPassword }}</code> · @endif
                แสดงเฉพาะการติดตั้งแบบทดลอง และหายไปเองเมื่อเปลี่ยนรหัสผ่าน
            </p>
        </section>
    @endif
@endsection

@push('scripts')
<script>
    (function () {
        var btn = document.querySelector('[data-toggle="password"]');
        if (!btn) return;

        var input = document.getElementById('password');
        if (!input) return;

        btn.addEventListener('click', function () {
            var shown = input.type === 'text';
            input.type = shown ? 'password' : 'text';
            btn.setAttribute('aria-pressed', String(!shown));
            btn.setAttribute('aria-label', shown ? 'แสดงรหัสผ่าน' : 'ซ่อนรหัสผ่าน');
            btn.title = btn.getAttribute('aria-label');
            input.focus();
        });
    })();

    (function () {
        document.querySelectorAll('[data-fill-email]').forEach(function (row) {
            row.addEventListener('click', function () {
                document.getElementById('email').value = row.dataset.fillEmail;
                document.getElementById('password').value = row.dataset.fillPassword;
                row.closest('main').querySelector('form button[type="submit"]').focus();
            });
        });
    })();
</script>
@endpush
