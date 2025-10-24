<template>
    <a :href="href" class="btn btn-6 btn-icon shadow-sm btn-tooltip" :style="`background-color: ${color};`"
        aria-label="Editar">
        <i :class="icon" style="font-size: 1.2rem; color: white;"></i>
        <span class="tooltip-text">{{ tooltip }}</span>
    </a>
</template>

<script setup>
defineProps({
    href: String,
    color: String,
    icon: String,
    tooltip: String
})
</script>
