<template>
    <div class="text-end">
        <a :href="cancelUrl" class="btn btn-1 me-2">Cancelar</a>

        <!-- Si hay saveUrl, redirige; si no, ejecuta acción -->
        <button v-if="!saveUrl" type="button" style="background-color: #55585a;" class="btn btn-primary btn-2"
            @click="handleSave">
            Guardar
        </button>

        <a v-else :href="saveUrl" class="btn btn-primary btn-2" style="background-color: #55585a;">
            Guardar
        </a>
    </div>
</template>

<script setup>
const props = defineProps({
    cancelUrl: {
        type: String,
        required: true,
    },
    onSave: {
        type: Function,
        required: false, // ahora no siempre obligatorio
    },
    saveUrl: {
        type: String,
        required: false, // opcional, si lo mandas usa href
    },
});

const handleSave = () => {
    if (props.onSave) {
        props.onSave();
    }
};
</script>
