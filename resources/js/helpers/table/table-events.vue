<script>
// This function sets up event listeners for table interactions like pagination, filtering, and searching
export function setupTableEvents({ fetchTableData, searchTerm, currentPage, limit, handlePagination }) {

    // Event listener for the "rows per page" selector (dropdown)
    const footerFilter = document.getElementById('footer-filter');
    if (footerFilter) {
        footerFilter.addEventListener('change', () => {
            // Update the 'limit' reactive variable with the selected value
            limit.value = parseInt(footerFilter.value);
            // Reset the current page to the first page
            currentPage.value = 1;
            // Fetch the updated table data
            fetchTableData();
        });
    }

    // IDs of the pagination buttons
    const paginatorButtons = [
        'less_one_iterator',  // Go back one page
        'plus_one_iterator',  // Go forward one page
        'less_five_iterator', // Go back five pages
        'plus_five_iterator'  // Go forward five pages
    ];

    // Add event listeners to each pagination button
    paginatorButtons.forEach(id => {
        const button = document.getElementById(id);
        if (button) {
            button.addEventListener('click', () => {
                // Call the pagination handler with the button ID and current pagination state
                handlePagination(id, currentPage, fetchTableData);
            });
        }
    });

    // Event listener for the search input field
    const searchInput = document.getElementById('table-search');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            // Update the search term reactive variable
            searchTerm.value = searchInput.value.trim();
            // Reset to the first page when a new search is entered
            currentPage.value = 1;
            // Fetch the filtered data based on search input
            fetchTableData();
        });
    }
}
</script>
