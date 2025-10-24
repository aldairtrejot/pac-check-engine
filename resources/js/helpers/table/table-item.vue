<template>
    <!-- Table header cell with centered, secondary colored, extra extra small, bold text and reduced opacity -->
    <td class="text-center text-secondary">
        <div>{{ value }}</div>
    </td>
</template>

<script setup>
/* Define a required prop 'value' of type String to display inside the header cell */
defineProps({
    value: {
        type: String,
        required: true
    }
})
</script>
