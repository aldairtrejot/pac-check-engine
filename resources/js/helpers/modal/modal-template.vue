<template>
    <div class="modal fade" :id="modalId" tabindex="-1" role="dialog" :aria-labelledby="modalId + '_label'"
        aria-hidden="false"> <!-- Modal container with dynamic ID and ARIA attributes -->
        <div :class="['modal-dialog', modalSizeClass, 'modal-dialog-centered']" role="document">
            <!-- Modal dialog with size and centering -->
            <div class="modal-content" style="border-radius: 0; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);">
                <!-- Modal box with custom styling -->
                <div class="modal-header" style="border-bottom: none;"> <!-- Header without border -->
                    <h5 class="modal-title" :id="modalId + '_label'" style="color:black"> <!-- Modal title -->
                        {{ title }} <!-- Display the title passed via props -->
                    </h5>
                </div>

                <!-- Optional content slot -->
                <div v-if="$slots.default" class="modal-body" style="margin-top: 0; padding-top: 0;">
                    <!-- Only render body if slot is passed -->
                    <slot /> <!-- Slot content -->
                </div>

                <div class="modal-footer" style="border-top: none;"> <!-- Footer without border -->
                    <button type="button" class="btn btn-sm btn" data-bs-dismiss="modal">Cancelar</button>
                    <!-- Cancel button -->
                    <button type="button" class="btn btn-sm btn-secondary" @click="onConfirm">Confirmar</button>
                    <!-- Confirm button with event -->
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
