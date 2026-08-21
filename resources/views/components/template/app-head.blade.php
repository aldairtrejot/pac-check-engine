<!DOCTYPE html>
<html lang="es">

<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons@latest/iconfont/tabler-icons.min.css">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sistema de Capacitación</title>
    <link rel="shortcut icon" href="{{ asset('assets/images/bienestar/favicon.png') }}" />
    <link id="pagestyle" href="{{ asset('assets/app/css/soft-ui-dashboard.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/icons/fontawesome/css/all.min.css') }}">
    <link id="pagestyle" href="{{ asset('assets/app/css/spinner.css') }}" rel="stylesheet" />
    <script src="{{ asset('assets/app/js/jquery.js') }}"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root {
            --cap-app-bg-image: url('{{ asset('assets/images/bienestar/backgroud_imss_white.png') }}');
        }
    </style>
    @vite(['resources/css/app.css'])
</head>
