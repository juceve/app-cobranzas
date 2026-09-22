@extends('home')

@section('title', 'Usuarios')
@section('content_header_title', 'Usuarios')
@section('content_header_subtitle', 'Administración de accesos')
@section('plugins.Sweetalert2', true)

@section('content_body')
    <livewire:user-management />
@stop

@push('js')
    <script>
        document.addEventListener('livewire:init', () => {
            const themeOptions = () => ({
                background: getComputedStyle(document.documentElement).getPropertyValue('--bs-body-bg').trim(),
                color: getComputedStyle(document.documentElement).getPropertyValue('--bs-body-color').trim(),
            });

            Livewire.on('user-notification', (event) => {
                Swal.fire({
                    ...themeOptions(),
                    icon: event.icon,
                    title: event.icon === 'success' ? 'Operación realizada' : 'No se pudo completar',
                    text: event.message,
                    timer: event.icon === 'success' ? 2500 : undefined,
                    showConfirmButton: event.icon !== 'success',
                });
            });

            Livewire.on('confirm-user-disabling', ({ userId }) => {
                Swal.fire({
                    ...themeOptions(),
                    icon: 'warning',
                    title: '¿Deshabilitar usuario?',
                    text: 'El usuario no podrá iniciar sesión, pero su información se conservará.',
                    showCancelButton: true,
                    confirmButtonText: 'Deshabilitar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545',
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.dispatch('disable-user', { userId });
                    }
                });
            });
        });
    </script>
@endpush