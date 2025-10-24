<template>
    <!-- Container div for the spinner bar -->
    <div ref="spinnerContainer" style="height: 4px; background: white; overflow: hidden; margin-bottom: 10px;">
        <!-- Animated inner bar -->
        <div ref="spinnerBar" style="height: 100%; width: 100%; background: linear-gradient(to right, #C0C0C0, #999999, #C0C0C0); 
                    animation: loadingMove 1.5s infinite linear;">
        </div>
    </div>
</template>

<script setup>
import { ref, defineExpose } from 'vue'

// References
const spinnerContainer = ref(null)
const spinnerBar = ref(null)

// Show the spinner
const show = () => {
    if (spinnerContainer.value && spinnerBar.value) {
        spinnerContainer.value.style.display = 'block'
        spinnerBar.value.style.animation = 'loadingMove 1.5s infinite linear'
    }
}

// Hide the spinner
const hide = () => {
    if (spinnerContainer.value && spinnerBar.value) {
        spinnerBar.value.style.animation = 'none'
        spinnerContainer.value.style.display = 'none'
    }
}

// Expose the methods to the parent component
defineExpose({
    show,
    hide
})
</script>

<style>
@keyframes loadingMove {
    0% {
        transform: translateX(-100%);
    }

    100% {
        transform: translateX(100%);
    }
}
</style>
