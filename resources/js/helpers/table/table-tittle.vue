<template>
    <!-- Card header container with responsive flex layout and spacing -->
    <div class="row align-items-center mb-3">
        <div class="col-md-6 col-12">
            <h5 class="card-title mb-0">{{ value }}</h5>
        </div>
        <div class="col-md-6 col-12 text-md-end mt-2 mt-md-0">
            <div class="w-50 ms-auto">
                <input type="text" class="form-control" name="table-search" id="table-search" placeholder="Buscar">
            </div>
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
