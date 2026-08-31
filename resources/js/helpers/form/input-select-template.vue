<template>
    <div class="d-flex align-items-center mb-3"> <!-- Contenedor flex -->
        <label v-if="label" class="form-control-label me-2 mb-0">{{ label }}</label>
        <!-- mb-0: elimina margen inferior -->

        <div :class="['flex-grow-1 multiselect-wrapper', { 'has-error': errorMessage }]"> <!-- Ocupa el espacio restante -->
            <multiselect :model-value="modelValue" @update:modelValue="onUpdateValue" :options="options"
                :multiple="multiple" :close-on-select="!multiple" :clear-on-select="false"
                :placeholder="placeholder" :label="labelKey" :track-by="trackBy" :tag-placeholder="''"
                :select-label="''" :selected-label="''" :deselect-label="''" :name="name" :id="id"
                :disabled="disabled" :searchable="searchable" :internal-search="true"
                :allow-empty="allowEmpty" :loading="loading" :max-height="maxHeight" :options-limit="optionsLimit"
                @search-change="onSearchChange">
                <template #noResult>
                    <span style="padding: 8px; display: block;">No se encontraron resultados.</span>
                </template>
                <template #noOptions>
                    <span style="padding: 8px; display: block;">No hay opciones disponibles.</span>
                </template>
            </multiselect>

            <div :id="`error-${id}`" class="text-danger text-error mt-1">{{ errorMessage }}</div>
        </div>
    </div>
</template>

<script setup>
import Multiselect from 'vue-multiselect'
import { defineProps, defineEmits } from 'vue'

const emit = defineEmits(['update:modelValue', 'onChange', 'onSearchChange', 'search-change']) // ✅ evento opcional

defineProps({
    modelValue: {
        type: [Array, Object],
        default: () => ([]),
    },
    options: {
        type: Array,
        default: () => [],
    },
    id: String,
    name: String,
    label: {
        type: String,
        default: 'Seleccione',
    },
    grid: {
        type: String,
        default: 'col-md-12',
    },
    labelKey: {
        type: String,
        default: 'descripcion',
    },
    trackBy: {
        type: String,
        default: 'id',
    },
    multiple: {
        type: Boolean,
        default: true,
    },
    disabled: {
        type: Boolean,
        default: false
    },
    searchable: {
        type: Boolean,
        default: true
    },
    loading: {
        type: Boolean,
        default: false
    },
    allowEmpty: {
        type: Boolean,
        default: true
    },
    maxHeight: {
        type: Number,
        default: 220
    },
    optionsLimit: {
        type: Number,
        default: 50
    },
    placeholder: {
        type: String,
        default: '-- Seleccione --'
    },
    errorMessage: {
        type: String,
        default: ''
    },
})

// ✅ Detecta cambios y emite si alguien lo escucha
function onUpdateValue(value) {
    emit('update:modelValue', value)
    emit('onChange', value) // Solo si el padre escucha este evento
}

function onSearchChange(value) {
    emit('onSearchChange', value)
    emit('search-change', value)
}
</script>

<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>

<style scoped>
.multiselect-wrapper {
    position: relative;
    min-width: 0;
}

.multiselect-wrapper .multiselect {
    width: 100%;
    min-width: 0;
    border-radius: 0.5rem;
    border: 1px solid #d2d6da;
    font-size: 14px;
}

.multiselect-wrapper .multiselect__tags {
    min-height: 38px;
    border-radius: 0.5rem;
    padding: 6px 10px;
    background-color: #d2d6da;
    overflow: hidden;
}

.multiselect-wrapper.has-error .multiselect {
    border-color: #dc3545;
}
</style>

<style>
.multiselect__option--highlight {
    background: #235B4E;
    outline: 0;
    color: #fff;
}

.multiselect__tag {
    position: relative;
    display: inline-block;
    padding: 4px 26px 4px 10px;
    border-radius: 5px;
    margin-right: 10px;
    color: #fff;
    line-height: 1;
    background: #235B4E;
    margin-bottom: 5px;
    white-space: nowrap;
    overflow: hidden;
    max-width: 100%;
    text-overflow: ellipsis;
}

.multiselect__option--selected.multiselect__option--highlight {
    background: #10312B;
    color: #fff;
}

.multiselect__content-wrapper {
    z-index: 1085;
    width: 100%;
    max-width: 100%;
    max-height: min(220px, calc(100vh - 180px)) !important;
    overflow-x: hidden;
    border-color: #d7dedb;
    box-shadow: 0 12px 24px rgba(16, 49, 43, 0.12);
}

.multiselect__content {
    display: block !important;
    width: 100%;
}

.multiselect__element {
    min-width: 0;
}

.multiselect__input,
.multiselect__single {
    font-size: 14px;
    min-height: 20px;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.multiselect__option {
    min-height: 38px;
    line-height: 1.25;
    white-space: normal;
    max-width: 100%;
    overflow-wrap: anywhere;
}

.multiselect__tag-icon::after {
    content: "×";
    color: #ffffff;
    font-size: 14px;
}
</style>
