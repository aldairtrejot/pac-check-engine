<template>
    <div class="table-footer d-flex justify-content-between align-items-center mt-3">
        <div class="d-flex align-items-center">
            <!-- Display total rows info with muted text style -->
            <div class="rows-info text-muted">
                Total <label>{{ row }}</label> de <label>{{ rowsAll }}</label>
            </div>
            <!-- Select dropdown to filter number of rows shown, with customizable inline styles -->
            <select name="footer-filter" id="footer-filter" :style="customStyle">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="30">30</option>
                <option value="50">50</option>
            </select>
        </div>
        <div class="paginador d-flex">
            <!-- Pagination buttons with Font Awesome icons for navigation -->
            <button class="paginador-btn" id="less_one_iterator"><i class="fa fa-angle-left"></i></button>
            <button class="paginador-btn" id="less_five_iterator"><i class="fa fa-angle-double-left"></i></button>
            <button class="paginador-btn" id="plus_five_iterator"><i class="fa fa-angle-double-right"></i></button>
            <button class="paginador-btn" id="plus_one_iterator"><i class="fa fa-angle-right"></i></button>
        </div>
    </div>
</template>

<script>
export default {
    name: 'TableFooter',
    props: {
        // Current number of displayed rows
        row: Number,
        // Total number of rows available
        rowsAll: Number,
        // Custom inline CSS style for the select element, with default styling
        customStyle: {
            type: String,
            default: 'border-color: white; margin-left: 10px; font-size: 12px; padding: 3px 7px;'
        }
    }
}
</script>
