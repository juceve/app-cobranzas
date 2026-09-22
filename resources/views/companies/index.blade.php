@extends('home')

@section('title', 'Empresas')
@section('content_header_title', 'Empresas')
@section('content_header_subtitle', 'Administración de empresas')
@section('plugins.Sweetalert2', true)

@section('content_body')
    <livewire:company-management />
@stop

@push('js')
    <script>
        document.addEventListener('livewire:init', () => {
            const themeOptions = () => ({
                background: getComputedStyle(document.documentElement).getPropertyValue('--bs-body-bg').trim(),
                color: getComputedStyle(document.documentElement).getPropertyValue('--bs-body-color').trim(),
            });

            Livewire.on('company-notification', (event) => {
                Swal.fire({
                    ...themeOptions(),
                    icon: event.icon,
                    title: event.icon === 'success' ? 'Operación realizada' : 'No se pudo completar',
                    text: event.message,
                    timer: event.icon === 'success' ? 2500 : undefined,
                    showConfirmButton: event.icon !== 'success',
                });
            });

            Livewire.on('confirm-company-disabling', ({ companyId }) => {
                Swal.fire({
                    ...themeOptions(),
                    icon: 'warning',
                    title: '¿Deshabilitar empresa?',
                    text: 'La empresa permanecerá registrada, pero no podrá utilizarse en nuevos procesos.',
                    showCancelButton: true,
                    confirmButtonText: 'Deshabilitar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545',
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.dispatch('disable-company', { companyId });
                    }
                });
            });

            Livewire.on('confirm-company-enabling', ({ companyId }) => {
                Swal.fire({
                    ...themeOptions(),
                    icon: 'question',
                    title: '¿Habilitar empresa?',
                    text: 'La empresa volverá a estar disponible para nuevos procesos.',
                    showCancelButton: true,
                    confirmButtonText: 'Habilitar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#198754',
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.dispatch('enable-company', { companyId });
                    }
                });
            });
        });
    </script>
@endpush