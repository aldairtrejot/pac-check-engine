<x-template.app-page>

    <x-template.app-header tittle="Acción">
        <x-button.button-header-action
            route="{{ route('action.create') }}"
            icon="fa fa-plus me-sm-1"
            tittle="Agregar"
        />
    </x-template.app-header>

    <x-template.app-card>
        {{-- Aquí se monta el componente Vue de la tabla --}}
        <div id="blade_table_action"></div>
    </x-template.app-card>

</x-template.app-page>