@extends('errors.layout')
@section('code', '403')
@section('heading', 'ไม่มีสิทธิ์เข้าถึง')
@section('message', $exception?->getMessage() ?: 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้ กรุณาติดต่อผู้ดูแลระบบหากคิดว่าเป็นข้อผิดพลาด')
