<x-template.app-page>
    <x-template.app-header tittle="Usuarios">
        <x-button.button-header-action
            route="{{ route('pac') }}"
            icon="fa fa-arrow-left me-sm-1"
            tittle="Regresar"
        />
    </x-template.app-header>

    <x-template.app-card>
        <div id="blade_table_admin_users"></div>
    </x-template.app-card>
</x-template.app-page>
