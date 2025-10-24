<template>
    <!-- Table header cell styled with center alignment, secondary color, very small bold text, and low opacity -->
    <th style="color:black" class="text-center text-secondary text-xs font-weight-bold">
        {{ value }}
    </th>
</template>

<script setup>
/* Define 'value' prop as a required string to display inside the table header cell */
defineProps({
    value: {
        type: String,
        required: true
    }
})
</script>
