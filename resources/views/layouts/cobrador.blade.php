@extends('adminlte::master')

@section('title', 'Cobranza')

@section('body')
    <div class="min-vh-100 bg-body-tertiary" style="padding-bottom: 5.5rem;">
        <header class="navbar bg-body shadow-sm sticky-top px-3 py-2">
            <div class="d-flex align-items-center gap-2 w-100">
                @if(! empty($volver))
                    <a href="{{ $volver }}" class="btn btn-outline-secondary btn-sm px-3" wire:navigate aria-label="Volver">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                @endif
                <div class="flex-grow-1 text-truncate">
                    <div class="fw-semibold text-truncate">{{ $titulo ?? 'Mis lotes' }}</div>
                    @if(! empty($subtitulo))
                        <div class="small text-body-secondary text-truncate">{{ $subtitulo }}</div>
                    @endif
                </div>
                <span class="small text-body-secondary d-none d-sm-inline">{{ auth()->user()->name }}</span>
            </div>
        </header>

        <main class="container-fluid px-2 px-sm-3 py-3" style="max-width: 720px;">
            @yield('content_body')
        </main>

        <nav class="navbar bg-body border-top fixed-bottom p-0" aria-label="Navegación del cobrador">
            <div class="d-flex w-100">
                <a href="{{ route('cobrador.lotes') }}" wire:navigate
                   class="flex-fill text-center py-2 text-decoration-none {{ request()->routeIs('cobrador.*') ? 'text-primary' : 'text-body-secondary' }}">
                    <i class="bi bi-list-check fs-4 d-block"></i>
                    <span class="small">Mis lotes</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="flex-fill text-center">
                    @csrf
                    <button type="submit" class="btn btn-link text-decoration-none text-body-secondary py-2 w-100">
                        <i class="bi bi-box-arrow-right fs-4 d-block"></i>
                        <span class="small">Salir</span>
                    </button>
                </form>
            </div>
        </nav>
    </div>

    <div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 2000;" id="toastContainer"></div>

    <script>
        window.mostrarToast = ({ type = 'info', message = '' }) => {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const colors = { success: 'text-bg-success', danger: 'text-bg-danger', warning: 'text-bg-warning', info: 'text-bg-primary' };

            const el = document.createElement('div');
            el.className = `toast align-items-center ${colors[type] || 'text-bg-secondary'} border-0`;
            el.setAttribute('role', 'alert');
            el.innerHTML = `<div class="d-flex"><div class="toast-body fs-6">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
            container.appendChild(el);

            new bootstrap.Toast(el, { delay: 3500 }).show();
            el.addEventListener('hidden.bs.toast', () => el.remove());
        };

        document.addEventListener('livewire:init', () => {
            Livewire.on('notify', (payload) => window.mostrarToast(Array.isArray(payload) ? payload[0] : payload));
        });
    </script>
@endsection
