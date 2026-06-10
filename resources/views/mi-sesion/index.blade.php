@extends('layouts.app')

@section('content')
@php
    $statusRaw = $usuario->status ?? false;

    $cuentaActiva = in_array($statusRaw, [true, 1, '1', 'true', 'TRUE', 'activo', 'ACTIVO'], true);
    $estatusTexto = $cuentaActiva ? 'Cuenta activa' : 'Cuenta inactiva';
@endphp

<div class="row">
    <div class="col-12">
        <div class="card mb-4 session-page-card">

            <div class="card-header pb-0">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1 text-dark font-weight-bolder">
                            Mi sesión
                        </h5>
                        <p class="text-sm text-secondary mb-0">
                            Consulta de datos esenciales del usuario autenticado.
                        </p>
                    </div>

                    <span class="badge {{ $cuentaActiva ? 'bg-success' : 'bg-danger' }}">
                        {{ $estatusTexto }}
                    </span>
                </div>
            </div>

            <div class="card-body px-4 pt-4 pb-4">
                <div class="session-summary-grid">

                    <div class="session-summary-item">
                        <span class="session-summary-icon">
                            <i class="fa fa-user"></i>
                        </span>

                        <div>
                            <div class="session-summary-label">
                                Nombre
                            </div>
                            <div class="session-summary-value">
                                {{ $usuario->name ?: 'No asignado' }}
                            </div>
                        </div>
                    </div>

                    <div class="session-summary-item">
                        <span class="session-summary-icon">
                            <i class="fa fa-envelope"></i>
                        </span>

                        <div>
                            <div class="session-summary-label">
                                Correo electrónico
                            </div>
                            <div class="session-summary-value">
                                {{ $usuario->email ?: 'No asignado' }}
                            </div>
                        </div>
                    </div>

                    <div class="session-summary-item">
                        <span class="session-summary-icon">
                            <i class="fa fa-map-marker-alt"></i>
                        </span>

                        <div>
                            <div class="session-summary-label">
                                Entidad
                            </div>
                            <div class="session-summary-value">
                                {{ $usuario->entidad_nombre ?: 'No asignado' }}
                            </div>
                        </div>
                    </div>

                    <div class="session-summary-item">
                        <span class="session-summary-icon">
                            <i class="fa fa-check-circle"></i>
                        </span>

                        <div>
                            <div class="session-summary-label">
                                Estatus
                            </div>
                            <div class="session-summary-value">
                                <span class="session-status-pill {{ $cuentaActiva ? 'is-active' : 'is-inactive' }}">
                                    {{ $estatusTexto }}
                                </span>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="alert session-info-alert mt-4 mb-0 text-sm">
                    <i class="fa fa-info-circle me-1"></i>
                    Esta información es únicamente de consulta. Para solicitar correcciones, comunícate con el administrador del sistema.
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .session-page-card {
        border: 1px solid rgba(16, 49, 43, 0.08);
        box-shadow: 0 0.8rem 1.8rem rgba(16, 49, 43, 0.075);
        border-radius: 1rem;
        overflow: hidden;
    }

    .session-page-card .card-header {
        border-bottom: 1px solid rgba(16, 49, 43, 0.06);
        background:
            radial-gradient(circle at 96% 10%, rgba(188, 149, 92, 0.11), transparent 30%),
            linear-gradient(135deg, rgba(35, 91, 78, 0.045), rgba(16, 49, 43, 0.018));
    }

    .session-summary-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .session-summary-item {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        align-items: center;
        gap: 0.85rem;
        min-height: 5.25rem;
        padding: 1rem;
        border-radius: 1rem;
        background: #ffffff;
        border: 1px solid rgba(35, 91, 78, 0.12);
        box-shadow: 0 0.45rem 1.1rem rgba(16, 49, 43, 0.055);
    }

    .session-summary-icon {
        width: 2.45rem;
        height: 2.45rem;
        min-width: 2.45rem;
        border-radius: 0.9rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #235B4E;
        background: rgba(35, 91, 78, 0.08);
        border: 1px solid rgba(35, 91, 78, 0.10);
    }

    .session-summary-icon i {
        color: #235B4E;
        font-size: 0.95rem;
    }

    .session-summary-label {
        color: #667085;
        font-size: 0.72rem;
        font-weight: 850;
        text-transform: uppercase;
        letter-spacing: 0.035rem;
        margin-bottom: 0.2rem;
    }

    .session-summary-value {
        color: #111827;
        font-size: 0.9rem;
        font-weight: 800;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .session-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.38rem;
        padding: 0.34rem 0.65rem;
        border-radius: 999px;
        font-size: 0.76rem;
        font-weight: 850;
        line-height: 1;
        white-space: nowrap;
    }

    .session-status-pill::before {
        content: '';
        width: 0.42rem;
        height: 0.42rem;
        border-radius: 50%;
    }

    .session-status-pill.is-active {
        color: #235B4E;
        background: rgba(35, 91, 78, 0.10);
        border: 1px solid rgba(35, 91, 78, 0.16);
    }

    .session-status-pill.is-active::before {
        background: #235B4E;
    }

    .session-status-pill.is-inactive {
        color: #7f1d1d;
        background: rgba(127, 29, 29, 0.08);
        border: 1px solid rgba(127, 29, 29, 0.12);
    }

    .session-status-pill.is-inactive::before {
        background: #7f1d1d;
    }

    .session-info-alert {
        border-radius: 0.95rem;
        color: #667085;
        background: rgba(188, 149, 92, 0.09);
        border: 1px solid rgba(188, 149, 92, 0.16);
    }

    .session-info-alert i {
        color: #BC955C;
    }

    @media (max-width: 768px) {
        .session-summary-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection