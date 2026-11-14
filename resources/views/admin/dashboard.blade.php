@extends('layouts.admin')

@section('title', 'Tổng quan')

@section('content')
    <p class="text-base">Chào, {{ auth('admin')->user()->name }}.</p>
@endsection
