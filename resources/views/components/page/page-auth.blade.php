<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Validación de Plantillas PAC</title>
    <link id="pagestyle" href="{{ asset('assets/app/css/soft-ui-dashboard.css') }}" rel="stylesheet" />
    <link id="pagestyle" href="{{ asset('assets/app/css/spinner.css') }}" rel="stylesheet" />
    <link rel="shortcut icon" href="{{ asset('assets/images/bienestar/favicon.png') }}" />
    <link rel="stylesheet" href="{{ asset('assets/icons/fontawesome/css/all.min.css') }}">
</head>

@include('components.message.error-messages')
@vite(['resources/js/app.js'])

<div id="spinnerOverlay" class="spinner-overlay">
    <div class="spinner"></div>
</div>

{{ $slot }}

</html>
