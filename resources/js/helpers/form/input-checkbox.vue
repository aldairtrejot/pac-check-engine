<template>
    <div :class="grid">
        <div class="form-check form-switch form-switch-3" style="margin-top:1.6rem;">
            <input class="form-check-input" type="checkbox" :id="id" :name="id" :checked="modelValue"
                @change="$emit('update:modelValue', $event.target.checked)" :true-value="true" :false-value="false" />
            <label class="form-check-label" :for="id">{{ label }}</label>
        </div>
    </div>
</template>

<script setup>
defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    label: {
        type: String,
        default: 'Estatus',
    },
    id: {
        type: String,
        default: 'status',
    },
    grid: {
        type: String,
        default: 'col-md-6',
    }
})
defineEmits(['update:modelValue'])
</script>
