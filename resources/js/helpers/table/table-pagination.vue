<script>
// This function handles pagination logic based on the action triggered by the user
export function handlePagination(action, currentPage, fetchTableData) {
    switch (action) {
        // Go back one page if not already on the first page
        case 'less_one_iterator':
            if (currentPage.value > 1) currentPage.value--;
            break;

        // Go forward one page
        case 'plus_one_iterator':
            currentPage.value++;
            break;

        // Go back five pages, but don't go below page 1
        case 'less_five_iterator':
            currentPage.value = Math.max(1, currentPage.value - 5);
            break;

        // Go forward five pages
        case 'plus_five_iterator':
            currentPage.value += 5;
            break;
    }

    // After updating the page, fetch the updated table data
    fetchTableData();
}
</script>
