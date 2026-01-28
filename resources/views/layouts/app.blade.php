<!doctype html>
<html lang="es">
<head>
    @include('components.template.app-head')
</head>

<body class="g-sidenav-show bg-gray-100">

    {{-- MENU LATERAL --}}
    @include('layouts.app-menu')

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">

        {{-- HEADER --}}
        @include('components.template.app-header')

        <div class="container-fluid py-4">
            @yield('content')
            @include('components.template.app-footer')
        </div>
    </main>

    {{-- JS base --}}
    <script src="{{ asset('assets/app/js/core/popper.min.js') }}"></script>
    <script src="{{ asset('assets/app/js/core/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/app/js/soft-ui-dashboard.min.js') }}"></script>

    @stack('scripts')
</body>
</html>
