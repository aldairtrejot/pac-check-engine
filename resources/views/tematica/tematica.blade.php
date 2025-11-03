<x-template.app-page>

    <x-template.app-header tittle="Temática">
        <x-button.button-header-action
            route="{{ route('tematica.create') }}"
            icon="fa fa-plus me-sm-1"
            tittle="Agregar"
        />
    </x-template.app-header>

    <x-template.app-card>
        <div id="blade_table_tematica"></div>
    </x-template.app-card>

</x-template.app-page>
