<template>
    <div class="row align-items-center mb-3 cap-table-title-row">
        <div class="col-md-6 col-12">
            <h5 class="card-title mb-0 cap-page-title">{{ value }}</h5>
        </div>
    </div>
</template>

<script>
export default {
    name: 'TableTitle',
    props: {
        // The title text displayed in the header, required string
        value: {
            type: String,
            required: true
        }
    }
}
</script>
