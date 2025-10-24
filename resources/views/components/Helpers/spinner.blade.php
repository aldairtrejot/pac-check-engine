<!-- 
 Create the spinner with the name of the JS code functions found at the end. 
 -->

<style>
    #loader-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .lds-dual-ring,
    .lds-dual-ring:after {
        box-sizing: border-box;
    }

    .lds-dual-ring {
        display: inline-block;
        width: 80px;
        height: 80px;
        color: white;
    }

    .lds-dual-ring:after {
        content: " ";
        display: block;
        width: 64px;
        height: 64px;
        margin: 8px;
        border-radius: 50%;
        border: 8px solid currentColor;
        border-color: currentColor transparent currentColor transparent;
        animation: lds-dual-ring 1.2s linear infinite;
    }

    @keyframes lds-dual-ring {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>

<div id="loader-overlay">
    <div class="lds-dual-ring"></div>
</div>

<script>
    function startLoader() {
        document.getElementById('loader-overlay').style.display = 'flex';
    }

    function stopLoader() {
        document.getElementById('loader-overlay').style.display = 'none';
    }
</script>