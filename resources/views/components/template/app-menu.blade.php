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
        // Correos que pueden ver "Agregar empleado", Acción, Temática e Instancias
        $allowedEmails = [
            'soporte_rh@imssbienestar.gob.mx',
            'yessica.colorado@imssbienestar.gob.mx',
            'reforzamientorh012@imssbienestar.gob.mx',
        ];

        $canSeeCatalogos = auth()->check()
            && in_array(auth()->user()->email, $allowedEmails, true);
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

            {{-- Sólo para correos autorizados 
            @if($canSeeCatalogos)

                <x-button.button-nav-menu
                    active="empleado"
                    route="empleado"
                    icon="fa fa-user-plus fa-lg"
                    title="Agregar empleado"
                />--}}

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

            @endif

            {{-- Siempre visible --}}
            <x-button.button-nav-menu
                active="logout"
                route="logout"
                icon="fa fa-power-off fa-lg"
                title="Salir"
            />

        </ul>
    </div>
</aside>

{{-- SweetAlert2 sólo para este menú (CDN) --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const logoutLink = document.querySelector('a[href="{{ route('logout') }}"]');

    if (!logoutLink) return;

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
});
</script>
