<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3"
    id="sidenav-main">
    <div class="sidenav-header">
        <i class="fas fa-times p-3 cursor-pointer text-secondary opacity-5 position-absolute end-0 top-0 d-none d-xl-none"
            aria-hidden="true" id="iconSidenav"></i>

        <a class="navbar-brand m-0" href="{{ route('pac') }}">
            <img src="{{ asset('assets/images/bienestar/logo_imss_blanco.png') }}" alt="main_logo"
                style="height: 54px !important; width: auto !important;">
        </a>
    </div>

    <hr class="horizontal dark mt-0">

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
            && $user->hasAnyRole(['admin_oc', 'supervisor_oc', 'revisor_est']);

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
        <ul class="navbar-nav">

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

            {{-- Constancias: Admin OC + Supervisor OC + Revisor EST --}}
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

            {{-- Espacio automático: solo empuja Aviso de Privacidad hasta abajo --}}
            <li class="nav-item sidebar-bottom-spacer" aria-hidden="true"></li>

            {{-- Aviso de privacidad hasta abajo --}}
            <li class="nav-item privacy-nav-bottom">
                <a href="#"
                   id="btnAvisoPrivacidad"
                   class="nav-link privacy-nav-link">

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
    | Acomodo del menú lateral
    |--------------------------------------------------------------------------
    */

    #sidenav-main {
        overflow: hidden;
    }

    #sidenav-main #sidenav-collapse-main {
        height: calc(100vh - 120px);
        max-height: calc(100vh - 120px);
        overflow: hidden !important;
    }

    #sidenav-main #sidenav-collapse-main .navbar-nav {
        min-height: 100%;
        display: flex;
        flex-direction: column;
    }

    .sidebar-bottom-spacer {
        margin-top: auto;
        min-height: 0.5rem;
    }

    .privacy-nav-bottom {
        margin-top: 0.25rem;
        padding-bottom: 0.25rem;
    }

    /*
    |--------------------------------------------------------------------------
    | Botón Aviso de Privacidad en sidebar
    |--------------------------------------------------------------------------
    */

    .privacy-nav-link {
        cursor: pointer;
        transition: all 0.2s ease-in-out;
    }

    .privacy-nav-link:hover {
        background: rgba(255, 255, 255, 0.08);
    }

    .privacy-nav-icon {
        background: rgba(255, 255, 255, 0.14) !important;
        color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, 0.22);
    }

    .privacy-nav-icon i {
        color: #ffffff !important;
        font-size: 0.9rem;
    }

    /*
    |--------------------------------------------------------------------------
    | Modal Avisos de Privacidad
    |--------------------------------------------------------------------------
    */

    .swal2-popup.swal-privacy-popup {
        border-radius: 1.35rem !important;
        padding: 0 !important;
        overflow: hidden !important;
        box-shadow: 0 1.25rem 3rem rgba(0, 0, 0, 0.22) !important;
    }

    .swal2-html-container.swal-privacy-html {
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }

    .privacy-modal {
        text-align: left;
        background: #ffffff;
    }

    .privacy-modal-header {
        padding: 1.35rem 1.45rem;
        background: linear-gradient(135deg, #235B4E 0%, #10312B 100%);
        color: #ffffff;
    }

    .privacy-modal-badge {
        width: 2.7rem;
        height: 2.7rem;
        border-radius: 0.9rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.22);
        margin-bottom: 0.7rem;
    }

    .privacy-modal-badge i {
        color: #ffffff;
        font-size: 1.15rem;
    }

    .privacy-modal-title {
        margin: 0;
        color: #ffffff;
        font-size: 1.15rem;
        font-weight: 800;
        line-height: 1.25;
    }

    .privacy-modal-subtitle {
        margin: 0.35rem 0 0 0;
        color: rgba(255, 255, 255, 0.78);
        font-size: 0.86rem;
        line-height: 1.4;
    }

    .privacy-modal-body {
        padding: 1.15rem;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.9rem;
    }

    .privacy-modal-card {
        position: relative;
        display: flex;
        flex-direction: column;
        min-height: 10rem;
        padding: 1rem;
        border-radius: 1rem;
        text-decoration: none;
        background: #ffffff;
        border: 1px solid rgba(35, 91, 78, 0.16);
        box-shadow: 0 0.45rem 1.1rem rgba(16, 49, 43, 0.08);
        transition: all 0.2s ease-in-out;
    }

    .privacy-modal-card:hover {
        transform: translateY(-3px);
        border-color: rgba(35, 91, 78, 0.35);
        box-shadow: 0 0.75rem 1.45rem rgba(16, 49, 43, 0.14);
    }

    .privacy-modal-card-icon {
        width: 2.45rem;
        height: 2.45rem;
        border-radius: 0.85rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(35, 91, 78, 0.10);
        color: #235B4E;
        margin-bottom: 0.8rem;
    }

    .privacy-modal-card-icon i {
        color: #235B4E;
        font-size: 1rem;
    }

    .privacy-modal-card-title {
        color: #10312B;
        font-size: 0.95rem;
        font-weight: 800;
        line-height: 1.25;
        margin-bottom: 0.4rem;
    }

    .privacy-modal-card-description {
        color: #667085;
        font-size: 0.78rem;
        line-height: 1.4;
        margin-bottom: 0.9rem;
    }

    .privacy-modal-card-action {
        margin-top: auto;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        color: #235B4E;
        font-size: 0.78rem;
        font-weight: 800;
    }

    .privacy-modal-footer {
        padding: 0 1.15rem 1.15rem 1.15rem;
    }

    .privacy-modal-note {
        margin: 0;
        padding: 0.75rem 0.85rem;
        border-radius: 0.85rem;
        background: rgba(188, 149, 92, 0.12);
        color: #6f4e21;
        font-size: 0.75rem;
        line-height: 1.35;
    }

    .swal-privacy-close {
        color: #ffffff !important;
        transition: all 0.2s ease-in-out !important;
    }

    .swal-privacy-close:hover {
        color: #ffffff !important;
        transform: scale(1.08);
    }

    @media (max-width: 768px) {
        .privacy-modal-body {
            grid-template-columns: 1fr;
        }

        .privacy-modal-card {
            min-height: auto;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    /*
    |--------------------------------------------------------------------------
    | Avisos de privacidad
    |--------------------------------------------------------------------------
    */

    const avisosPrivacidad = @json($avisosPrivacidad);
    const btnAvisoPrivacidad = document.getElementById('btnAvisoPrivacidad');

    const escapeHtml = (value) => {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    };

    if (btnAvisoPrivacidad) {
        btnAvisoPrivacidad.addEventListener('click', function (e) {
            e.preventDefault();

            const cards = avisosPrivacidad.map((aviso) => {
                return `
                    <a class="privacy-modal-card"
                       href="${escapeHtml(aviso.url)}"
                       target="_blank"
                       rel="noopener noreferrer">

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

                            <h2 class="privacy-modal-title">
                                Aviso de Privacidad
                            </h2>

                            <p class="privacy-modal-subtitle">
                                Selecciona el documento que deseas consultar. El archivo se abrirá en una nueva pestaña.
                            </p>
                        </div>

                        <div class="privacy-modal-body">
                            ${cards}
                        </div>

                        <div class="privacy-modal-footer">
                            <p class="privacy-modal-note">
                                Los documentos corresponden a los avisos oficiales publicados por IMSS-Bienestar.
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
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = this.href;
                }
            });
        });
    }
});
</script>