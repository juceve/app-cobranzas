
@extends('home')

@section('title', 'Padrón de deudores')
@section('content_header_title', 'Padrón de deudores')
@section('content_header_subtitle', 'Catálogo único de personas registradas')
@section('plugins.Sweetalert2', true)

@section('content_body')
    <livewire:deudor-padron />
@stop