<x-template.app-page>

    <x-template.app-header tittle="Instancias">
        <x-button.button-header-action
            route="{{ route('instancia.create') }}"
            icon="fa fa-plus me-sm-1"
            tittle="Agregar"
        />
    </x-template.app-header>

    <x-template.app-card>
        <div id="blade_table_instancia"></div>
    </x-template.app-card>

</x-template.app-page>
