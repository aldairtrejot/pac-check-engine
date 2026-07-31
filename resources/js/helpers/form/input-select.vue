<template>
    <div :class="grid">
        <label :class="['form-label', required ? 'required' : '']">{{ label }}</label>

        <div class="multiselect-wrapper">
            <multiselect :model-value="modelValue" @update:modelValue="onUpdateValue" :options="options"
                :multiple="multiple" :close-on-select="!multiple" :clear-on-select="false"
                placeholder="-- Seleccione --" :label="labelKey" :track-by="trackBy" :tag-placeholder="''"
                :select-label="''" :selected-label="''" :deselect-label="''" :name="name" :id="id" :disabled="disabled">
                <template #noResult>
                    <span style="padding: 8px; display: block;">No se encontraron resultados.</span>
                </template>
                <template #noOptions>
                    <span style="padding: 8px; display: block;">No hay opciones disponibles.</span>
                </template>
            </multiselect>

            <div :id="`error-${id}`" class="text-danger text-error mt-1"></div>
        </div>
    </div>
</template>

<script setup>
import Multiselect from 'vue-multiselect'
import { defineProps, defineEmits } from 'vue'

const emit = defineEmits(['update:modelValue', 'onChange'])

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
    required: {
        type: Boolean,
        default: false
    },
})

function onUpdateValue(value) {
    emit('update:modelValue', value)
    emit('onChange', value)
}
</script>

<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>

<style scoped>
.multiselect-wrapper .multiselect {
    border-radius: 0.65rem;
    border: 1px solid #d7dedb;
    font-size: 14px;
}

.multiselect-wrapper .multiselect__tags {
    border-radius: 0.65rem;
    padding: 7px 11px;
    background-color: #ffffff;
    border-color: transparent;
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

.multiselect__tag-icon::after {
    content: "×";
    color: #ffffff;
    font-size: 14px;
}
</style>
