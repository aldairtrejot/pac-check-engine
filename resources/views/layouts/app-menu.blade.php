<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3 bg-white">
    <div class="sidenav-header">
        <a class="navbar-brand m-0" href="{{ route('pac') }}">
            <span class="ms-1 font-weight-bold">PAC CHECK</span>
        </a>
    </div>

    <hr class="horizontal dark mt-0">

    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav">

            <x-button.button-nav-menu active="pac" route="pac" icon="ni ni-tv-2" title="PAC" />
            <x-button.button-nav-menu active="instancia" route="instancia" icon="ni ni-building" title="Instancias" />
            <x-button.button-nav-menu active="tematica" route="tematica" icon="ni ni-tag" title="Temáticas" />
            <x-button.button-nav-menu active="action" route="action" icon="ni ni-bullet-list-67" title="Acciones" />

            @if(auth()->check() && auth()->user()?->role === 'admin_oc')
                <x-button.button-nav-menu active="usuarios" route="usuarios" icon="ni ni-single-02" title="Usuarios" />
            @endif

        </ul>
    </div>
</aside>
