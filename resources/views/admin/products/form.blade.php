@extends('layouts.admin')

@section('title', $product ? 'Sửa: '.$product->name : 'Thêm sản phẩm')

@section('actions')
    @if ($product && $product->isVisible())
        <a href="{{ url('/san-pham/'.$product->slug) }}" target="_blank" class="a-btn">Xem trên web ↗</a>
    @endif
@endsection

@section('content')
    <div class="max-w-5xl space-y-5">
        <livewire:admin.product-form :product="$product" />
        @if ($product)
            <livewire:admin.product-media :product="$product" />
        @endif
    </div>
@endsection
