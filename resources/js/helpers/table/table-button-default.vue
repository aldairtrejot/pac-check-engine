<template>
    <button
        class="icon-button cap-icon-button"
        :style="{ backgroundColor: color }"
        v-bind="modalAttrs"
        @click="handleClick"
        :aria-label="label"
        type="button"
    >
        <i :class="icon" :style="{ color: color_icon }"></i>
        <span class="custom-tooltip">{{ label }}</span>
    </button>
</template>

<script setup>
import { defineProps, defineEmits, computed } from 'vue'

// Define component props with types and default values
const props = defineProps({
    color: {
        type: String,
        required: true,
        default: '#BC955C',  // Default button background color
    },
    icon: {
        type: String,
        required: true,
        default: 'fa fa-lock',  // Default icon class (FontAwesome lock)
    },
    label: {
        type: String,
        required: true,
        default: 'Password',  // Default tooltip label
    },
    clickEventPayload: {
        required: true,  // Payload emitted when button is clicked
    },
    modalToggle: {
        type: String,
        default: null,   // Optional: value for Bootstrap's data-bs-toggle attribute
    },
    modalTarget: {
        type: String,
        default: null,   // Optional: value for Bootstrap's data-bs-target attribute
    },
    color_icon: {
        type: String,
        default: 'White'
    }
})

// Define emitted events
const emit = defineEmits(['click'])

// Compute attributes related to Bootstrap modal toggling if provided
const modalAttrs = computed(() => {
    const attrs = {}
    if (props.modalToggle) attrs['data-bs-toggle'] = props.modalToggle
    if (props.modalTarget) attrs['data-bs-target'] = props.modalTarget
    return attrs
})

// Emit a click event with the provided payload when button is clicked
function handleClick() {
    emit('click', props.clickEventPayload)
}
</script>
