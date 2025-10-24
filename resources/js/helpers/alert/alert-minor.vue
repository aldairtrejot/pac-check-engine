<template>
    <div class="alert alert-minor alert-info alert-dismissible" role="alert">
        <div class="alert-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="icon alert-icon icon-2">
                <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                <path d="M12 9h.01"></path>
                <path d="M11 12h1v4h1"></path>
            </svg>
        </div>
        <div>
            <h4 :style="{ color: titleColor }" class="alert-heading fw-bold">{{ title }}</h4>
            <div class="alert-description">
                <slot></slot> <!-- Slot para el contenido dinámico -->
            </div>
        </div>
        <a v-if="dismissible" class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
    </div>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        default: '📌 Importante'
    },
    titleColor: {
        type: String,
        default: '#BC955C'
    },
    dismissible: {
        type: Boolean,
        default: true
    }
})
</script>