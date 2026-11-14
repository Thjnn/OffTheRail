@extends('layouts.admin')

@section('title', 'Nội dung web')

@section('actions')
    <a href="{{ url('/') }}" target="_blank" class="a-btn">Xem trang chủ ↗</a>
@endsection

@section('content')
    <div class="max-w-4xl space-y-5">
        <livewire:admin.banner-manager />
        <livewire:admin.home-section-manager />
        <livewire:admin.about-editor />
        <livewire:admin.size-chart-editor />
    </div>
@endsection
