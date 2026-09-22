@extends('home')

@section('title', 'Roles y permisos')
@section('content_header_title', 'Roles y permisos')
@section('content_header_subtitle', 'Configuración de accesos del sistema')
@section('plugins.Sweetalert2', true)

@section('content_body')
    <livewire:role-management />
@stop

@push('js')
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('role-notification', (event) => {
                Swal.fire({
                    background: getComputedStyle(document.documentElement).getPropertyValue('--bs-body-bg').trim(),
                    color: getComputedStyle(document.documentElement).getPropertyValue('--bs-body-color').trim(),
                    icon: event.icon,
                    title: 'Operación realizada',
                    text: event.message,
                    timer: 2500,
                    showConfirmButton: false,
                });
            });
        });
    </script>
@endpush