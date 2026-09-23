@extends('home')

@section('title', 'Carteras')
@section('content_header_title', 'Carteras')
@section('content_header_subtitle', 'Administración de carteras por empresa')
@section('plugins.Sweetalert2', true)

@section('content_body')
    <livewire:cartera-management />
@stop

@push('js')
    <script>
        document.addEventListener('livewire:init', () => {
            const themeOptions = () => ({
                background: getComputedStyle(document.documentElement).getPropertyValue('--bs-body-bg').trim(),
                color: getComputedStyle(document.documentElement).getPropertyValue('--bs-body-color').trim(),
            });

            Livewire.on('cartera-notification', (event) => {
                // Livewire 3 puede entregar:
                //   - event.detail = { icon, message }         (cuando usas argumentos nombrados)
                //   - event.detail = [{ icon, message }]       (cuando usas array asociativo)
                //   - event.detail = { 0: {...} }              (versiones intermedias)
                let payload = event?.detail ?? event;

                if (Array.isArray(payload)) {
                    payload = payload[0] ?? {};
                } else if (payload && typeof payload === 'object' && payload[0]) {
                    payload = payload[0];
                }

                const icon    = payload?.icon ?? 'info';
                const message = payload?.message ?? 'Operación sin mensaje';

                Swal.fire({
                    ...themeOptions(),
                    icon,
                    title: icon === 'success' ? 'Operación realizada' : 'No se pudo completar',
                    text: message,
                    timer: icon === 'success' ? 2500 : undefined,
                    showConfirmButton: icon !== 'success',
                });
            });
        });
    </script>
@endpush