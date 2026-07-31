<template>
    <th class="text-center text-xs font-weight-bold cap-table-heading">
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
