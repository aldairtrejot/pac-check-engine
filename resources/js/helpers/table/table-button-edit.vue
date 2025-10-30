<template>
    <!-- Link styled as an icon button with a custom background color -->
    <a :href="href" class="icon-button" :style="{ backgroundColor: bgColor }">
        <!-- Font Awesome edit icon -->
        <i :class="icon"></i>
        <!-- Tooltip text shown on hover -->
        <span class="custom-tooltip">{{ label }}</span>
    </a>
</template>

<script setup>
/* Define a required prop 'href' of type String for the link destination */
defineProps({
    href: {
        type: String,
        required: true
    },
    icon: {
        type: String,
        required: true,
        default: 'fa fa-edit'
    }, label: {
        type: String,
        default: 'Modificar'
    },
    bgColor: {
        type: String,
        default: '#10312B'
    }
});
</script>
