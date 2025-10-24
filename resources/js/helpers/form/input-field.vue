<template>
    <div :class="grid">
        <div class="form-group">
            <label :class="['form-label', required ? 'required' : '']">
                {{ label }}
            </label>
            <input :class="['form-control', uppercase ? 'uppercase' : '']" :type="type" :placeholder="label" :name="id"
                :id="id" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)"
                :autocomplete="autocomplete" :disabled="disabled" :required="required" />
            <div :id="`error-${id}`" class="text-danger text-error" style="margin-top: 5px; color: #7a1b32 !important;">
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'

defineProps({
    grid: { type: String, default: 'col-md-6 mb-3' },
    label: { type: String, required: true },
    id: { type: String, required: true },
    modelValue: { type: String, required: false },
    type: { type: String, default: 'text' },
    uppercase: { type: Boolean, default: false },
    autocomplete: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])
</script>
