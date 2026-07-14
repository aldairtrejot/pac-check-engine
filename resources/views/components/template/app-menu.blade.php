<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3"
    id="sidenav-main">
    <div class="sidenav-header sidebar-brand-header">
        <i class="fas fa-times p-3 cursor-pointer text-secondary opacity-5 position-absolute end-0 top-0 d-none d-xl-none"
            aria-hidden="true" id="iconSidenav"></i>

        <a class="navbar-brand m-0 sidebar-brand-link" href="{{ route('pac') }}" aria-label="Ir a Mi plantilla">
            <span class="sidebar-brand-logo-wrap">
                <img src="{{ asset('assets/images/bienestar/logo_imss_blanco.png') }}" alt="main_logo"
                    class="sidebar-brand-logo">
            </span>
        </a>
    </div>

    <hr class="horizontal dark mt-0 sidebar-soft-divider">

    @php
        $user = auth()->user();

        // Centrales: Admin + Supervisor OC
        $isCentral = auth()->check()
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['admin_oc', 'supervisor_oc']);

        // Solo Admin OC
        $isAdminCentral = auth()->check()
            && method_exists($user, 'hasRole')
            && $user->hasRole('admin_oc');

        // Usuarios que pueden ver y operar constancias
        $canAccessConstancias = auth()->check()
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['admin_oc', 'supervisor_oc', 'revisor_est', 'supervisor_est']);

        /*
        |--------------------------------------------------------------------------
        | Datos de sesión para ventana informativa
        |--------------------------------------------------------------------------
        | Solo se consultan datos esenciales del usuario autenticado.
        | No se expone password, remember_token ni datos sensibles.
        */
        $datosSesion = null;

        if (auth()->check()) {
            try {
                $datosSesion = \Illuminate\Support\Facades\DB::table('administracion.users as u')
                    ->leftJoin('administracion.cat_entidad as ce', 'ce.id_entidad', '=', 'u.id_entidad')
                    ->where('u.id', $user->id)
                    ->select([
                        'u.name',
                        'u.email',
                        'u.status',
                        \Illuminate\Support\Facades\DB::raw("COALESCE(ce.nombre, 'No asignado') as entidad_nombre"),
                    ])
                    ->first();

            } catch (\Throwable $e) {
                $datosSesion = (object) [
                    'name' => $user->name ?? 'No asignado',
                    'email' => $user->email ?? 'No asignado',
                    'status' => $user->status ?? false,
                    'entidad_nombre' => 'No asignado',
                ];
            }
        }

        $avisosPrivacidad = [
            [
                'titulo' => 'Aviso de Privacidad Simplificado',
                'descripcion' => 'Consulta el aviso simplificado sobre el tratamiento de datos personales.',
                'url' => 'https://imssbienestar.gob.mx/assets/doc/transparencia/02_proteccion/01_privacidad/01_dgsimssbienestar/recursos_humanos/Aviso%20de%20Privacidad%20Simplificado%20de%20Reclutamiento%20y%20Sele_.pdf',
            ],
            [
                'titulo' => 'Aviso de Privacidad Integral',
                'descripcion' => 'Consulta el aviso integral con la información completa sobre el tratamiento de datos personales.',
                'url' => 'https://imssbienestar.gob.mx/assets/doc/transparencia/02_proteccion/01_privacidad/01_dgsimssbienestar/recursos_humanos/Aviso%20de%20Privacidad%20Integral%20Reclutamiento%20y%20Selecci%C3%B3n_.pdf',
            ],
        ];
    @endphp

    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav sidebar-menu-list">

            {{-- Mi sesión: primera opción del menú, visible para todo usuario autenticado --}}
            @auth
                <li class="nav-item">
                    <a href="#"
                       id="btnMiSesion"
                       class="nav-link mi-sesion-nav-link"
                       aria-label="Ver información de mi sesión">

                        <div class="icon icon-shape icon-sm shadow border-radius-md text-center me-2 d-flex align-items-center justify-content-center mi-sesion-nav-icon">
                            <i class="fa fa-user-circle"></i>
                        </div>

                        <span class="nav-link-text ms-1">
                            Mi sesión
                        </span>

                        <span class="mi-sesion-nav-indicator" aria-hidden="true"></span>
                    </a>
                </li>

                {{-- Separación visual entre Mi sesión y el menú principal --}}
                <li class="nav-item menu-after-session-spacer" aria-hidden="true"></li>
            @endauth

            {{-- Siempre visible --}}
            <x-button.button-nav-menu
                active="pac"
                route="pac"
                icon="fa fa-id-badge fa-lg"
                title="Mi plantilla"
            />

            {{-- Catálogos: solo Admin OC y Supervisor OC --}}
            @if($isCentral)

                <x-button.button-nav-menu
                    active="empleado"
                    route="empleado"
                    icon="fa fa-user-plus fa-lg"
                    title="Agregar empleado"
                />

                <x-button.button-nav-menu
                    active="action"
                    route="action"
                    icon="fa fa-align-center fa-lg"
                    title="Acción"
                />

                <x-button.button-nav-menu
                    active="tematica"
                    route="tematica"
                    icon="fa fa-list-alt fa-lg"
                    title="Temática"
                />

                <x-button.button-nav-menu
                    active="instancia"
                    route="instancia"
                    icon="fa fa-university fa-lg"
                    title="Instancias"
                />

                {{--
                @if($isAdminCentral)
                    <x-button.button-nav-menu
                        active="usuarios"
                        route="usuarios"
                        icon="fa fa-users-cog fa-lg"
                        title="Usuarios"
                    />
                @endif
                --}}

            @endif

            {{-- Constancias: Admin OC + Supervisor OC + Revisor EST + Supervisor EST --}}
            @if($canAccessConstancias)
                <x-button.button-nav-menu
                    active="constancias"
                    route="constancias"
                    icon="fa fa-file-signature fa-lg"
                    title="Constancias"
                />
            @endif

            {{-- Salir: se queda con los demás botones del menú --}}
            <x-button.button-nav-menu
                active="logout"
                route="logout"
                icon="fa fa-power-off fa-lg"
                title="Salir"
            />

            <form id="logoutForm" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>

            {{-- Espacio automático: solo empuja Aviso de Privacidad hasta abajo --}}
            <li class="nav-item sidebar-bottom-spacer" aria-hidden="true"></li>

            {{-- Aviso de privacidad hasta abajo --}}
            <li class="nav-item privacy-nav-bottom">
                <a href="#"
                   id="btnAvisoPrivacidad"
                   class="nav-link privacy-nav-link"
                   aria-label="Consultar aviso de privacidad">

                    <div class="icon icon-shape icon-sm shadow border-radius-md text-center me-2 d-flex align-items-center justify-content-center privacy-nav-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>

                    <span class="nav-link-text ms-1">
                        Aviso de Privacidad
                    </span>
                </a>
            </li>

        </ul>
    </div>
</aside>

{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /*
    |--------------------------------------------------------------------------
    | Diseño base del menú lateral
    |--------------------------------------------------------------------------
    */

    #sidenav-main {
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        box-shadow: 0 1rem 2.5rem rgba(16, 49, 43, 0.16) !important;
    }

    #sidenav-main .sidebar-brand-header {
        min-height: 5.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.05rem 1rem 0.7rem 1rem;
    }

    #sidenav-main .sidebar-brand-link {
        width: calc(100% - 1.2rem);
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 0.55rem 0.75rem;
        border-radius: 1rem;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.09), rgba(255, 255, 255, 0.03));
        border: 1px solid rgba(255, 255, 255, 0.10);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
        transition: all 0.2s ease-in-out;
    }

    #sidenav-main .sidebar-brand-link:hover {
        transform: translateY(-1px);
        border-color: rgba(255, 255, 255, 0.18);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.13), rgba(255, 255, 255, 0.05));
    }

    #sidenav-main .sidebar-brand-logo-wrap {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
    }

    #sidenav-main .sidebar-brand-logo {
        height: 54px !important;
        width: auto !important;
        max-width: 100%;
        object-fit: contain;
        filter: drop-shadow(0 0.35rem 0.65rem rgba(0, 0, 0, 0.18));
    }

    #sidenav-main .sidebar-soft-divider {
        margin-left: 1rem;
        margin-right: 1rem;
        opacity: 0.18;
        background: rgba(255, 255, 255, 0.55);
    }

    #sidenav-main #sidenav-collapse-main {
        height: calc(100vh - 126px);
        max-height: calc(100vh - 126px);
        overflow-x: hidden !important;
        overflow-y: auto !important;
        padding: 0 0 0.65rem 0;
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.22) transparent;
    }

    #sidenav-main #sidenav-collapse-main::-webkit-scrollbar {
        width: 0.35rem;
    }

    #sidenav-main #sidenav-collapse-main::-webkit-scrollbar-track {
        background: transparent;
    }

    #sidenav-main #sidenav-collapse-main::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.22);
        border-radius: 999px;
    }

    #sidenav-main #sidenav-collapse-main .sidebar-menu-list {
        min-height: 100%;
        display: flex;
        flex-direction: column;
        padding-bottom: 0.1rem;
    }

    #sidenav-main .navbar-nav > .nav-item {
        position: relative;
    }

    #sidenav-main .navbar-nav .nav-link {
        border-radius: 0.85rem;
        transition: background 0.2s ease-in-out, transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    #sidenav-main .navbar-nav .nav-link:not(.mi-sesion-nav-link):not(.privacy-nav-link):hover {
        transform: translateX(2px);
        background: rgba(255, 255, 255, 0.07) !important;
    }

    .sidebar-bottom-spacer {
        margin-top: auto;
        min-height: 0.65rem;
    }

    .privacy-nav-bottom {
        margin-top: 0.35rem;
        padding-bottom: 0.35rem;
    }

    /*
    |--------------------------------------------------------------------------
    | Mi sesión en sidebar
    |--------------------------------------------------------------------------
    */

    .mi-sesion-nav-link {
        position: relative;
        display: flex !important;
        align-items: center !important;
        min-height: 3.05rem;
        padding: 0.675rem 0.95rem !important;
        margin: 0.1rem 0.65rem 0.15rem 0.65rem;
        border-radius: 0.95rem;
        cursor: pointer;
        overflow: hidden;
        isolation: isolate;
        transition: all 0.2s ease-in-out;
        background:
            radial-gradient(circle at 88% 15%, rgba(188, 149, 92, 0.42), transparent 30%),
            linear-gradient(135deg, rgba(35, 91, 78, 0.98) 0%, rgba(16, 49, 43, 0.98) 100%);
        border: 1px solid rgba(255, 255, 255, 0.18);
        box-shadow: 0 0.55rem 1.25rem rgba(16, 49, 43, 0.24);
    }

    .mi-sesion-nav-link::before {
        content: '';
        position: absolute;
        inset: 0;
        z-index: -1;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.14), transparent 45%);
        opacity: 0.55;
        pointer-events: none;
    }

    .mi-sesion-nav-link:hover {
        transform: translateY(-1px);
        border-color: rgba(255, 255, 255, 0.26);
        box-shadow: 0 0.75rem 1.45rem rgba(16, 49, 43, 0.30);
    }

    .mi-sesion-nav-icon {
        width: 2rem !important;
        height: 2rem !important;
        min-width: 2rem !important;
        background: rgba(255, 255, 255, 0.16) !important;
        color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, 0.26);
        box-shadow: none !important;
    }

    .mi-sesion-nav-icon i {
        color: #ffffff !important;
        font-size: 0.95rem;
    }

    .mi-sesion-nav-link .nav-link-text {
        color: #ffffff !important;
        font-size: 0.875rem;
        font-weight: 800;
        line-height: 1;
        letter-spacing: 0.01rem;
    }

    .mi-sesion-nav-indicator {
        width: 0.48rem;
        height: 0.48rem;
        border-radius: 50%;
        background: #BC955C;
        margin-left: auto;
        box-shadow: 0 0 0 0.22rem rgba(188, 149, 92, 0.18);
    }

    .menu-after-session-spacer {
        height: 0.9rem;
        margin: 0 0.9rem 0.45rem 0.9rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    }

    /*
    |--------------------------------------------------------------------------
    | Botón Aviso de Privacidad en sidebar
    |--------------------------------------------------------------------------
    */

    .privacy-nav-link {
        display: flex !important;
        align-items: center !important;
        min-height: 3rem;
        padding: 0.65rem 0.95rem !important;
        margin: 0 0.65rem;
        border-radius: 0.95rem;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        background: rgba(255, 255, 255, 0.045);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .privacy-nav-link:hover {
        transform: translateY(-1px);
        background: rgba(255, 255, 255, 0.09);
        border-color: rgba(255, 255, 255, 0.16);
        box-shadow: 0 0.45rem 1rem rgba(0, 0, 0, 0.10);
    }

    .privacy-nav-icon {
        width: 2rem !important;
        height: 2rem !important;
        min-width: 2rem !important;
        background: rgba(255, 255, 255, 0.14) !important;
        color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, 0.22);
        box-shadow: none !important;
    }

    .privacy-nav-icon i {
        color: #ffffff !important;
        font-size: 0.9rem;
    }

    .privacy-nav-link .nav-link-text {
        color: rgba(255, 255, 255, 0.96) !important;
        font-size: 0.84rem;
        font-weight: 750;
        line-height: 1.15;
    }

    /*
    |--------------------------------------------------------------------------
    | Modales SweetAlert - base compartida
    |--------------------------------------------------------------------------
    */

    .swal2-popup.swal-session-popup,
    .swal2-popup.swal-privacy-popup,
    .swal2-popup.swal-logout-popup {
        border-radius: 1.35rem !important;
        padding: 0 !important;
        overflow: hidden !important;
        box-shadow: 0 1.4rem 3.4rem rgba(0, 0, 0, 0.24) !important;
        border: 1px solid rgba(16, 49, 43, 0.10) !important;
    }

    .swal2-html-container.swal-session-html,
    .swal2-html-container.swal-privacy-html {
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }

    .swal-session-close,
    .swal-privacy-close {
        color: #ffffff !important;
        transition: all 0.2s ease-in-out !important;
    }

    .swal-session-close:hover,
    .swal-privacy-close:hover {
        color: #ffffff !important;
        transform: scale(1.08);
    }

    /*
    |--------------------------------------------------------------------------
    | Modal Mi sesión
    |--------------------------------------------------------------------------
    */

    .session-modal {
        text-align: left;
        background: #f8faf9;
    }

    .session-modal-header {
        position: relative;
        display: flex;
        gap: 0.95rem;
        align-items: center;
        padding: 1.35rem 1.45rem;
        overflow: hidden;
        background:
            radial-gradient(circle at 88% 18%, rgba(188, 149, 92, 0.40), transparent 30%),
            linear-gradient(135deg, #235B4E 0%, #10312B 100%);
        color: #ffffff;
    }

    .session-modal-header::after {
        content: '';
        position: absolute;
        right: -2.7rem;
        bottom: -2.7rem;
        width: 8rem;
        height: 8rem;
        border-radius: 50%;
        border: 1.2rem solid rgba(255, 255, 255, 0.06);
        pointer-events: none;
    }

    .session-modal-badge {
        width: 3rem;
        height: 3rem;
        min-width: 3rem;
        border-radius: 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.23);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.10);
    }

    .session-modal-badge i {
        color: #ffffff;
        font-size: 1.2rem;
    }

    .session-modal-title-wrap {
        position: relative;
        z-index: 1;
    }

    .session-modal-title {
        margin: 0;
        color: #ffffff;
        font-size: 1.18rem;
        font-weight: 850;
        line-height: 1.2;
        letter-spacing: -0.01rem;
    }

    .session-modal-subtitle {
        margin: 0.34rem 0 0 0;
        color: rgba(255, 255, 255, 0.80);
        font-size: 0.85rem;
        line-height: 1.4;
    }

    .session-modal-body {
        padding: 1.15rem;
    }

    .session-info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.82rem;
        align-items: stretch;
    }

    .session-info-item {
        position: relative;
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        gap: 0.72rem;
        align-items: center;
        min-height: 4.55rem;
        padding: 0.9rem 0.95rem;
        border-radius: 1rem;
        background: #ffffff;
        border: 1px solid rgba(35, 91, 78, 0.12);
        box-shadow: 0 0.45rem 1.1rem rgba(16, 49, 43, 0.055);
    }

    .session-info-icon {
        width: 2.15rem;
        height: 2.15rem;
        min-width: 2.15rem;
        border-radius: 0.8rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(35, 91, 78, 0.08);
        color: #235B4E;
    }

    .session-info-icon i {
        color: #235B4E;
        font-size: 0.86rem;
    }

    .session-info-label {
        display: block;
        color: #667085;
        font-size: 0.68rem;
        font-weight: 850;
        text-transform: uppercase;
        letter-spacing: 0.035rem;
        margin-bottom: 0.2rem;
    }

    .session-info-value {
        color: #111827;
        font-size: 0.84rem;
        font-weight: 800;
        line-height: 1.34;
        overflow-wrap: anywhere;
    }

    .session-status-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.38rem;
        padding: 0.32rem 0.58rem;
        border-radius: 999px;
        font-size: 0.74rem;
        font-weight: 850;
        line-height: 1;
        white-space: nowrap;
    }

    .session-status-chip::before {
        content: '';
        width: 0.42rem;
        height: 0.42rem;
        border-radius: 50%;
    }

    .session-status-chip.is-active {
        color: #235B4E;
        background: rgba(35, 91, 78, 0.10);
        border: 1px solid rgba(35, 91, 78, 0.16);
    }

    .session-status-chip.is-active::before {
        background: #235B4E;
    }

    .session-status-chip.is-inactive {
        color: #7f1d1d;
        background: rgba(127, 29, 29, 0.08);
        border: 1px solid rgba(127, 29, 29, 0.12);
    }

    .session-status-chip.is-inactive::before {
        background: #7f1d1d;
    }

    /*
    |--------------------------------------------------------------------------
    | Modal Avisos de Privacidad
    |--------------------------------------------------------------------------
    */

    .privacy-modal {
        text-align: left;
        background: #f8faf9;
    }

    .privacy-modal-header {
        position: relative;
        display: flex;
        gap: 0.95rem;
        align-items: center;
        padding: 1.35rem 1.45rem;
        overflow: hidden;
        background:
            radial-gradient(circle at 88% 18%, rgba(188, 149, 92, 0.40), transparent 30%),
            linear-gradient(135deg, #235B4E 0%, #10312B 100%);
        color: #ffffff;
    }

    .privacy-modal-header::after {
        content: '';
        position: absolute;
        right: -2.7rem;
        bottom: -2.7rem;
        width: 8rem;
        height: 8rem;
        border-radius: 50%;
        border: 1.2rem solid rgba(255, 255, 255, 0.06);
        pointer-events: none;
    }

    .privacy-modal-badge {
        width: 3rem;
        height: 3rem;
        min-width: 3rem;
        border-radius: 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.23);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.10);
    }

    .privacy-modal-badge i {
        color: #ffffff;
        font-size: 1.15rem;
    }

    .privacy-modal-title-wrap {
        position: relative;
        z-index: 1;
    }

    .privacy-modal-title {
        margin: 0;
        color: #ffffff;
        font-size: 1.18rem;
        font-weight: 850;
        line-height: 1.2;
        letter-spacing: -0.01rem;
    }

    .privacy-modal-subtitle {
        margin: 0.34rem 0 0 0;
        color: rgba(255, 255, 255, 0.80);
        font-size: 0.85rem;
        line-height: 1.4;
    }

    .privacy-modal-body {
        padding: 1.12rem;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.9rem;
    }

    .privacy-modal-card {
        position: relative;
        display: flex;
        flex-direction: column;
        min-height: 10.25rem;
        padding: 1rem;
        border-radius: 1.05rem;
        text-decoration: none;
        background: #ffffff;
        border: 1px solid rgba(35, 91, 78, 0.14);
        box-shadow: 0 0.5rem 1.2rem rgba(16, 49, 43, 0.075);
        transition: all 0.2s ease-in-out;
        overflow: hidden;
    }

    .privacy-modal-card::after {
        content: attr(data-document-number);
        position: absolute;
        right: 0.8rem;
        top: 0.55rem;
        color: rgba(35, 91, 78, 0.06);
        font-size: 3rem;
        font-weight: 900;
        line-height: 1;
        pointer-events: none;
    }

    .privacy-modal-card:hover {
        transform: translateY(-3px);
        border-color: rgba(35, 91, 78, 0.30);
        box-shadow: 0 0.8rem 1.55rem rgba(16, 49, 43, 0.13);
        text-decoration: none;
    }

    .privacy-modal-card-icon {
        width: 2.5rem;
        height: 2.5rem;
        min-width: 2.5rem;
        border-radius: 0.9rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(35, 91, 78, 0.09);
        color: #235B4E;
        margin-bottom: 0.8rem;
        border: 1px solid rgba(35, 91, 78, 0.10);
    }

    .privacy-modal-card-icon i {
        color: #235B4E;
        font-size: 1rem;
    }

    .privacy-modal-card-title {
        position: relative;
        z-index: 1;
        color: #10312B;
        font-size: 0.95rem;
        font-weight: 850;
        line-height: 1.25;
        margin-bottom: 0.42rem;
    }

    .privacy-modal-card-description {
        position: relative;
        z-index: 1;
        color: #667085;
        font-size: 0.78rem;
        line-height: 1.42;
        margin-bottom: 0.9rem;
    }

    .privacy-modal-card-action {
        position: relative;
        z-index: 1;
        margin-top: auto;
        display: inline-flex;
        align-items: center;
        gap: 0.42rem;
        color: #235B4E;
        font-size: 0.78rem;
        font-weight: 850;
    }

    .privacy-modal-footer {
        padding: 0 1.12rem 1.12rem 1.12rem;
    }

    .privacy-modal-note {
        display: flex;
        gap: 0.55rem;
        align-items: flex-start;
        margin: 0;
        padding: 0.78rem 0.86rem;
        border-radius: 0.95rem;
        background: rgba(188, 149, 92, 0.12);
        color: #6f4e21;
        font-size: 0.75rem;
        line-height: 1.38;
        border: 1px solid rgba(188, 149, 92, 0.16);
    }

    .privacy-modal-note i {
        color: #BC955C;
        margin-top: 0.12rem;
    }

    /*
    |--------------------------------------------------------------------------
    | Modal cerrar sesión
    |--------------------------------------------------------------------------
    */

    .swal2-popup.swal-logout-popup {
        padding: 1.4rem !important;
    }

    .swal-logout-title {
        color: #10312B !important;
        font-weight: 850 !important;
        letter-spacing: -0.01rem !important;
    }

    .swal-logout-confirm,
    .swal-logout-cancel {
        border-radius: 0.8rem !important;
        font-weight: 800 !important;
        padding: 0.68rem 1rem !important;
    }

    @media (max-width: 768px) {
        .privacy-modal-body,
        .session-info-grid {
            grid-template-columns: 1fr;
        }

        .privacy-modal-card {
            min-height: auto;
        }

        .session-modal-header,
        .privacy-modal-header {
            align-items: flex-start;
            padding-right: 2.7rem;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    const escapeHtml = (value) => {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    };

    /*
    |--------------------------------------------------------------------------
    | Mi sesión
    |--------------------------------------------------------------------------
    */

    const datosSesion = @json($datosSesion);
    const btnMiSesion = document.getElementById('btnMiSesion');

    if (btnMiSesion && datosSesion) {
        btnMiSesion.addEventListener('click', function (e) {
            e.preventDefault();

            const cuentaActiva =
                datosSesion.status === true ||
                datosSesion.status === 1 ||
                datosSesion.status === '1' ||
                datosSesion.status === 'true' ||
                datosSesion.status === 'TRUE' ||
                datosSesion.status === 'activo' ||
                datosSesion.status === 'ACTIVO';

            const estatusTexto = cuentaActiva ? 'Cuenta activa' : 'Cuenta inactiva';
            const estatusClass = cuentaActiva ? 'is-active' : 'is-inactive';

            Swal.fire({
                html: `
                    <div class="session-modal">
                        <div class="session-modal-header">
                            <div class="session-modal-badge">
                                <i class="fa fa-user-circle"></i>
                            </div>

                            <div class="session-modal-title-wrap">
                                <h2 class="session-modal-title">
                                    Mi sesión
                                </h2>

                                <p class="session-modal-subtitle">
                                    Información principal del usuario actualmente autenticado.
                                </p>
                            </div>
                        </div>

                        <div class="session-modal-body">
                            <div class="session-info-grid">
                                <div class="session-info-item">
                                    <span class="session-info-icon">
                                        <i class="fa fa-user"></i>
                                    </span>
                                    <div>
                                        <span class="session-info-label">Nombre</span>
                                        <div class="session-info-value">${escapeHtml(datosSesion.name || 'No asignado')}</div>
                                    </div>
                                </div>

                                <div class="session-info-item">
                                    <span class="session-info-icon">
                                        <i class="fa fa-envelope"></i>
                                    </span>
                                    <div>
                                        <span class="session-info-label">Correo electrónico</span>
                                        <div class="session-info-value">${escapeHtml(datosSesion.email || 'No asignado')}</div>
                                    </div>
                                </div>

                                <div class="session-info-item">
                                    <span class="session-info-icon">
                                        <i class="fa fa-map-marker-alt"></i>
                                    </span>
                                    <div>
                                        <span class="session-info-label">Entidad</span>
                                        <div class="session-info-value">${escapeHtml(datosSesion.entidad_nombre || 'No asignado')}</div>
                                    </div>
                                </div>

                                <div class="session-info-item">
                                    <span class="session-info-icon">
                                        <i class="fa fa-check-circle"></i>
                                    </span>
                                    <div>
                                        <span class="session-info-label">Estatus</span>
                                        <div class="session-info-value">
                                            <span class="session-status-chip ${estatusClass}">${escapeHtml(estatusTexto)}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `,
                width: '39rem',
                padding: 0,
                showConfirmButton: false,
                showCloseButton: true,
                focusConfirm: false,
                customClass: {
                    popup: 'swal-session-popup',
                    htmlContainer: 'swal-session-html',
                    closeButton: 'swal-session-close',
                },
            });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Avisos de privacidad
    |--------------------------------------------------------------------------
    */

    const avisosPrivacidad = @json($avisosPrivacidad);
    const btnAvisoPrivacidad = document.getElementById('btnAvisoPrivacidad');

    if (btnAvisoPrivacidad) {
        btnAvisoPrivacidad.addEventListener('click', function (e) {
            e.preventDefault();

            const cards = avisosPrivacidad.map((aviso, index) => {
                return `
                    <a class="privacy-modal-card"
                       href="${escapeHtml(aviso.url)}"
                       target="_blank"
                       rel="noopener noreferrer"
                       data-document-number="0${index + 1}">

                        <span class="privacy-modal-card-icon">
                            <i class="fa fa-file-pdf"></i>
                        </span>

                        <span class="privacy-modal-card-title">
                            ${escapeHtml(aviso.titulo)}
                        </span>

                        <span class="privacy-modal-card-description">
                            ${escapeHtml(aviso.descripcion)}
                        </span>

                        <span class="privacy-modal-card-action">
                            Ver documento
                            <i class="fa fa-arrow-up-right-from-square"></i>
                        </span>
                    </a>
                `;
            }).join('');

            Swal.fire({
                html: `
                    <div class="privacy-modal">
                        <div class="privacy-modal-header">
                            <div class="privacy-modal-badge">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>

                            <div class="privacy-modal-title-wrap">
                                <h2 class="privacy-modal-title">
                                    Aviso de Privacidad
                                </h2>

                                <p class="privacy-modal-subtitle">
                                    Selecciona el documento que deseas consultar. El archivo se abrirá en una nueva pestaña.
                                </p>
                            </div>
                        </div>

                        <div class="privacy-modal-body">
                            ${cards}
                        </div>

                        <div class="privacy-modal-footer">
                            <p class="privacy-modal-note">
                                <i class="fa fa-info-circle"></i>
                                <span>Los documentos corresponden a los avisos oficiales publicados por IMSS-Bienestar.</span>
                            </p>
                        </div>
                    </div>
                `,
                width: '48rem',
                padding: 0,
                showConfirmButton: false,
                showCloseButton: true,
                focusConfirm: false,
                customClass: {
                    popup: 'swal-privacy-popup',
                    htmlContainer: 'swal-privacy-html',
                    closeButton: 'swal-privacy-close',
                },
            });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Confirmación para cerrar sesión
    |--------------------------------------------------------------------------
    */

    const logoutUrl = @json(route('logout'));
    const logoutLink = document.querySelector(`a[href="${logoutUrl}"]`);
    const logoutForm = document.getElementById('logoutForm');

    if (logoutLink) {
        logoutLink.addEventListener('click', function (e) {
            e.preventDefault();

            Swal.fire({
                title: 'Cerrar sesión',
                text: '¿Deseas salir del sistema?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, salir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#235B4E',
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
                customClass: {
                    popup: 'swal-logout-popup',
                    title: 'swal-logout-title',
                    confirmButton: 'swal-logout-confirm',
                    cancelButton: 'swal-logout-cancel',
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    if (logoutForm) {
                        logoutForm.submit();
                    }
                }
            });
        });
    }
});
</script>
