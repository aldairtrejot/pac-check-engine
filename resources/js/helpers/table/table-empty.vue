<template>
    <tr>
        <!-- Table cell spanning multiple columns, centered and vertically aligned -->
        <td :colspan="colspan" class="align-middle text-center">
            <!-- Message displayed in secondary color, small font, and bold weight -->
            <span class="text-secondary text-xs font-weight-bold">
                {{ message }}
            </span>
        </td>
    </tr>
</template>

<script setup>
/* Define props for the component */
defineProps({
    // Number of columns the cell should span, required
    colspan: {
        type: Number,
        required: true
    },
    // Message to display, default is 'No results found.'
    message: {
        type: String,
        default: 'No se encontraron resultados.'
    }
});
</script>
