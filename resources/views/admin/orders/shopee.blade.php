@extends('layouts.admin')

@section('title', 'Nhập đơn Shopee')

@section('actions')
    <a href="{{ url('/admin/don-hang?channel=shopee') }}" class="a-btn">Đơn Shopee đã nhập</a>
@endsection

@section('content')
    <livewire:admin.shopee-order-form />
@endsection
