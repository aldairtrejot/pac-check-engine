<template>
    <button type="button" class="btn btn-6 btn-icon shadow-sm btn-tooltip" :style="`background-color: ${props.color};`"
        aria-label="Editar" data-bs-toggle="modal" :data-bs-target="props.modalId" @click="handleClick">
        <i :class="props.icon" style="font-size: 1.2rem; color: white;"></i>
        <span class="tooltip-text">{{ props.tooltip }}</span>
    </button>
</template>

<script setup>
const props = defineProps({
    color: String,
    icon: String,
    tooltip: String,
    modalId: String,
    clickEventPayload: [String, Number]
})

const emit = defineEmits(['click'])

function handleClick() {
    emit('click', props.clickEventPayload)
}
</script>
