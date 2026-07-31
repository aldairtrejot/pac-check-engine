<template>
    <div class="modal fade" :id="modalId" tabindex="-1" role="dialog" :aria-labelledby="modalId + '_label'"
        aria-hidden="false">
        <div :class="['modal-dialog', modalSizeClass, 'modal-dialog-centered']" role="document">
            <div class="modal-content cap-modal">
                <div class="modal-header cap-modal-header">
                    <div class="cap-modal-title-wrap">
                        <span class="cap-modal-icon">
                            <i class="fa fa-clipboard-check"></i>
                        </span>
                        <h5 class="modal-title cap-modal-title" :id="modalId + '_label'">
                            {{ title }}
                        </h5>
                    </div>
                </div>

                <div v-if="$slots.default" class="modal-body cap-modal-body">
                    <slot />
                </div>

                <div class="modal-footer cap-modal-footer">
                    <button type="button" class="btn btn-sm cap-btn-outline" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-sm cap-btn-primary" @click="onConfirm">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    modalId: { type: String, required: true }, // Unique modal ID
    title: { type: String, default: 'title' }, // Modal title
    onConfirm: { type: Function, required: true }, // Function called on confirm
    size: { type: String, default: 'md' } // Modal size: 'sm', 'md', 'lg', 'xl'
})

// Modal size class
const modalSizeClass = {
    sm: 'modal-sm', // Small modal
    md: '', // Default/medium modal
    lg: 'modal-lg', // Large modal
    xl: 'modal-xl' // Extra large modal
}[__props.size] || '' // Fallback to empty string if size not matched
</script>
