<template>
    <div class="table-footer cap-table-footer d-flex justify-content-between align-items-center mt-3">
        <div class="d-flex align-items-center cap-table-footer-left">
            <div class="rows-info text-muted">
                Total <label>{{ row }}</label> de <label>{{ rowsAll }}</label>
            </div>
            <select name="footer-filter" id="footer-filter" class="cap-table-select" :style="customStyle">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="30">30</option>
                <option value="50">50</option>
            </select>
        </div>
        <div class="paginador d-flex">
            <button class="paginador-btn" id="less_one_iterator" type="button" aria-label="Página anterior"><i class="fa fa-angle-left"></i></button>
            <button class="paginador-btn" id="less_five_iterator" type="button" aria-label="Retroceder cinco páginas"><i class="fa fa-angle-double-left"></i></button>
            <button class="paginador-btn" id="plus_five_iterator" type="button" aria-label="Avanzar cinco páginas"><i class="fa fa-angle-double-right"></i></button>
            <button class="paginador-btn" id="plus_one_iterator" type="button" aria-label="Página siguiente"><i class="fa fa-angle-right"></i></button>
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
            default: ''
        }
    }
}
</script>
