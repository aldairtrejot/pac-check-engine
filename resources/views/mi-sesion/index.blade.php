@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card mb-4">

            <div class="card-header pb-0">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1">Mi sesión</h5>
                        <p class="text-sm text-secondary mb-0">
                            Consulta de datos esenciales del usuario autenticado.
                        </p>
                    </div>

                    <span class="badge {{ $usuario->status ? 'bg-success' : 'bg-danger' }}">
                        {{ $usuario->status ? 'Cuenta activa' : 'Cuenta inactiva' }}
                    </span>
                </div>
            </div>

            <div class="card-body px-4 pt-4 pb-4">

                <div class="row g-3">

                    {{-- Datos principales --}}
                    <div class="col-12 col-lg-6">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span
                                    class="d-inline-flex align-items-center justify-content-center"
                                    style="width:38px;height:38px;border-radius:12px;background:rgba(8,31,94,.10);color:#081F5E;"
                                >
                                    <i class="ni ni-single-02"></i>
                                </span>

                                <div>
                                    <h6 class="mb-0">Datos del usuario</h6>
                                    <p class="text-xs text-secondary mb-0">
                                        Información básica de la cuenta.
                                    </p>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="text-xs text-secondary font-weight-bold text-uppercase">
                                    Nombre
                                </div>
                                <div class="text-sm font-weight-bold text-dark" style="overflow-wrap:anywhere;">
                                    {{ $usuario->name ?: 'No asignado' }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="text-xs text-secondary font-weight-bold text-uppercase">
                                    Correo electrónico
                                </div>
                                <div class="text-sm font-weight-bold text-dark" style="overflow-wrap:anywhere;">
                                    {{ $usuario->email ?: 'No asignado' }}
                                </div>
                            </div>

                            <div class="mb-0">
                                <div class="text-xs text-secondary font-weight-bold text-uppercase">
                                    Rol(es)
                                </div>

                                @if(!empty($roles))
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        @foreach($roles as $rol)
                                            <span class="badge bg-gradient-secondary">
                                                {{ $rol }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-sm font-weight-bold text-dark">
                                        Sin rol asignado
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Adscripción --}}
                    <div class="col-12 col-lg-6">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span
                                    class="d-inline-flex align-items-center justify-content-center"
                                    style="width:38px;height:38px;border-radius:12px;background:rgba(35,91,78,.10);color:#235B4E;"
                                >
                                    <i class="ni ni-building"></i>
                                </span>

                                <div>
                                    <h6 class="mb-0">Adscripción</h6>
                                    <p class="text-xs text-secondary mb-0">
                                        Información asociada al alcance del usuario.
                                    </p>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="text-xs text-secondary font-weight-bold text-uppercase">
                                    Entidad
                                </div>
                                <div class="text-sm font-weight-bold text-dark" style="overflow-wrap:anywhere;">
                                    {{ $usuario->entidad_nombre ?: 'No asignado' }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="text-xs text-secondary font-weight-bold text-uppercase">
                                    Tipo de nómina
                                </div>
                                <div class="text-sm font-weight-bold text-dark" style="overflow-wrap:anywhere;">
                                    {{ $usuario->tipo_nomina_codigo ?: 'No asignado' }}
                                </div>
                            </div>

                            <div class="mb-0">
                                <div class="text-xs text-secondary font-weight-bold text-uppercase">
                                    CLUES
                                </div>
                                <div class="text-sm font-weight-bold text-dark" style="overflow-wrap:anywhere;">
                                    {{ $usuario->clues_codigo ?: 'No asignado' }}
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="alert alert-light border mt-4 mb-0 text-sm text-secondary">
                    <i class="ni ni-lock-circle-open me-1"></i>
                    Esta información es únicamente de consulta. Para solicitar correcciones, comunícate con el administrador del sistema.
                </div>

            </div>
        </div>
    </div>
</div>
@endsection