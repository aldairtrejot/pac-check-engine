<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3 "
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
    <div class="collapse navbar-collapse  w-auto " id="sidenav-collapse-main">
        <ul class="navbar-nav">

            <x-button.button-nav-menu active="pac" route="pac" icon="fa fa-id-badge fa-lg" title="Mi plantilla" />

            <x-button.button-modal-menu idModal="#modal_logout" icon="fa fa-power-off fa-lg" tittle="Salir" />

        </ul>
    </div>
</aside>
