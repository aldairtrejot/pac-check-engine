<template>
    <div class="modal fade" :id="modalId" tabindex="-1" aria-hidden="true">
        <div :class="dialogClass">
            <div class="modal-content rounded-0 border-0">
                <div class="modal-body text-center pb-1">
                    <slot />
                </div>

                <div class="modal-footer justify-content-end border-0 pt-1 px-3 pb-2">
                    <button type="button" class="btn btn-text-only btn-cancel" style="font-size: 15px !important"
                        data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="button" class="btn btn-text-only btn-confirm"
                        style="font-size: 15px !important; color: #BC955C !important;" @click="onConfirm">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    modalId: {
        type: String,
        required: true,
    },
    onConfirm: {
        type: Function,
        required: true,
    },
    dialogClass: {
        type: String,
        default: 'modal-dialog modal-dialog-centered modal-sm',
    },
});
</script>
