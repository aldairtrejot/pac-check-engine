<template>
    <tr>
        <td :colspan="colspan" class="align-middle text-center cap-table-empty-cell">
            <div class="cap-empty-state">
                <span class="cap-empty-icon">
                    <i class="fa fa-magnifying-glass"></i>
                </span>
                <span class="cap-empty-text">{{ message }}</span>
            </div>
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
