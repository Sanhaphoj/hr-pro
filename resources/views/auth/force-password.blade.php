@extends('layouts.guest')
@section('title', 'ตั้งรหัสผ่านใหม่')

@section('content')
    <div class="auth__welcome">
        <h1>ตั้งรหัสผ่านใหม่</h1>
        <p>บัญชี <b>{{ $user->email }}</b> ยังใช้รหัสผ่านชั่วคราวอยู่ ตั้งรหัสผ่านของคุณเองก่อนเริ่มใช้งาน</p>
    </div>

    <form method="POST" action="{{ route('password.force.update') }}">
        @csrf
        @method('PUT')

        <x-input
            name="password"
            type="password"
            label="รหัสผ่านใหม่"
            :required="true"
            :hint="\App\Support\PasswordPolicy::hint()"
            autocomplete="new-password"
            autofocus
        />

        <div class="field-gap">
            <x-input
                name="password_confirmation"
                type="password"
                label="ยืนยันรหัสผ่านใหม่"
                :required="true"
                autocomplete="new-password"
            />
        </div>

        <x-button type="submit" class="btn--block" icon="lock" style="margin-top:22px;">บันทึกและเริ่มใช้งาน</x-button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="auth__alt">
        @csrf
        <button type="submit" class="btn btn--ghost btn--sm"><x-icon name="logout" />ออกจากระบบ</button>
    </form>
@endsection
