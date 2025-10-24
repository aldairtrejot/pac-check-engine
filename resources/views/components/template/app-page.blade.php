<x-template.app-head />

<body class="g-sidenav-show  bg-gray-100">
    @include('components.message.error-messages')
    @vite(['resources/js/app.js'])

    <div id="spinnerOverlay" class="spinner-overlay">
        <div class="spinner"></div>
    </div>

    <div id="blade_app_logout"></div>
    <aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3 "
        id="sidenav-main">
        <x-style.style-select />
        <x-template.app-menu />
    </aside>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
        {{ $slot }}
    </main>
</body>

<x-template.app-footer />
