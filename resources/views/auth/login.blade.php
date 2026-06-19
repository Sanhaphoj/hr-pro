@extends('layouts.guest')
@section('title', 'เข้าสู่ระบบ')

@section('content')
    <h1 style="font-size:22px; margin-bottom:4px;">ยินดีต้อนรับกลับ</h1>
    <p class="text-soft" style="margin:0 0 22px;">กรุณาเข้าสู่ระบบเพื่อใช้งาน HR PRO</p>

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

        <div style="margin-top:14px;">
            <x-input
                name="password"
                type="password"
                label="รหัสผ่าน"
                :required="true"
                placeholder="••••••••"
                autocomplete="current-password"
            />
        </div>

        <div class="flex items-center justify-between" style="margin:14px 0 20px;">
            <label class="checkbox-row">
                <input type="checkbox" name="remember" value="1">
                <span>จดจำการเข้าสู่ระบบ</span>
            </label>
        </div>

        <x-button type="submit" class="btn--block" icon="login">เข้าสู่ระบบ</x-button>
    </form>

    <div class="card" style="margin-top:24px; background:#f7f9fc;">
        <div class="card__body" style="padding:14px 16px;">
            <div class="cell-sub" style="margin-bottom:6px; font-weight:600;">บัญชีทดลอง (ข้อมูลตัวอย่าง)</div>
            <div class="cell-sub">ผู้ดูแลระบบ: <b>admin@hrpro.local</b> / <b>password</b></div>
            <div class="cell-sub">ฝ่าย HR: <b>hr@hrpro.local</b> / <b>password</b></div>
            <div class="cell-sub">พนักงาน: <b>employee@hrpro.local</b> / <b>password</b></div>
        </div>
    </div>
@endsection
