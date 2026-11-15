@extends('layouts.admin')

@section('title', $product ? 'Sửa: '.$product->name : 'Thêm sản phẩm')

@section('actions')
    @if ($product)
        <a href="{{ url('/admin/san-pham/'.$product->id.'/in-tem') }}" class="a-btn"><x-admin.icon name="print" class="size-4" /> In tem</a>
    @endif
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
