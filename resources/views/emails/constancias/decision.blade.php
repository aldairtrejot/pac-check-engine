@php
    $decisionNormalizada = strtoupper(trim((string) ($decision ?? '')));
    $esAceptado = in_array($decisionNormalizada, ['ACEPTADO', 'ACEPTADA'], true);
    $esDuplicada = in_array($decisionNormalizada, ['DUPLICADA', 'DUPLICADO', 'CONSTANCIA DUPLICADA'], true);

    $nombrePersonaSafe = trim((string) ($nombrePersona ?? ''));
    $nombreCursoSafe = trim((string) ($nombreCurso ?? ''));
    $folioSafe = trim((string) ($folio ?? ''));
    $fechaHoraSafe = trim((string) ($fechaHora ?? ''));
    $motivoSafe = trim((string) ($motivo ?? ''));

    $nombrePersonaSafe = $nombrePersonaSafe !== '' ? $nombrePersonaSafe : 'Usuario';
    $nombreCursoSafe = $nombreCursoSafe !== '' ? $nombreCursoSafe : 'No especificado';
    $folioSafe = $folioSafe !== '' ? $folioSafe : 'S/F';
    $fechaHoraSafe = $fechaHoraSafe !== '' ? $fechaHoraSafe : 'No especificada';

    $colorVino = '#611232';
    $colorDorado = '#d6bd74';

    $colorPrincipal = $esAceptado ? '#1e5b4f' : ($esDuplicada ? '#9b6a00' : '#b3261e');
    $colorFondoEstatus = $esAceptado ? '#eef8f5' : ($esDuplicada ? '#fff8e6' : '#fff1f1');
    $colorBordeEstatus = $esAceptado ? '#b8d8cf' : ($esDuplicada ? '#e7c76f' : '#f0c5c5');

    $tituloDecision = $esAceptado ? 'Constancia aceptada' : ($esDuplicada ? 'Constancia duplicada' : 'Constancia rechazada');
    $estatusTexto = $esAceptado ? 'ACEPTADA' : ($esDuplicada ? 'DUPLICADA' : 'RECHAZADA');


    /*
     |--------------------------------------------------------------------------
     | Historial de capacitación
     |--------------------------------------------------------------------------
     | Esta vista espera recibir $historialCapacitacion desde el Mailable.
     | Puede venir como array, colección o lista de objetos.
     */
    $historialRows = collect($historialCapacitacion ?? []);
    $historialTotal = $historialRows->count();

    $formatFecha = function ($value) {
        $value = trim((string) ($value ?? ''));

        if ($value === '' || strtolower($value) === 'null') {
            return '-';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable $e) {
            return $value;
        }
    };

    $formatNumero = function ($value) {
        $value = trim((string) ($value ?? ''));

        if ($value === '' || strtolower($value) === 'null') {
            return '-';
        }

        $value = str_replace(',', '.', $value);

        if (is_numeric($value)) {
            return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
        }

        return $value;
    };

    $parseHorasNumero = function ($value): float {
        $value = trim((string) ($value ?? ''));

        if ($value === '' || strtolower($value) === 'null') {
            return 0.0;
        }

        $value = str_replace(',', '.', $value);

        if (!is_numeric($value)) {
            return 0.0;
        }

        return max(0.0, round((float) $value, 2));
    };


    /*
     |--------------------------------------------------------------------------
     | Logo incrustado para correo
     |--------------------------------------------------------------------------
     | Archivo esperado:
     | public/assets/images/bienestar/logo_imss_blanco.png
     |
     | Si la vista se abre desde navegador y no existe $message,
     | se usa asset() como respaldo.
     */
    $logoPath = public_path('assets/images/bienestar/logo_imss_blanco.png');

    $logoSrc = isset($message) && file_exists($logoPath)
        ? $message->embed($logoPath)
        : asset('assets/images/bienestar/logo_imss_blanco.png');
@endphp

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $tituloDecision }}</title>
</head>

<body
    style="margin:0; padding:0; background-color:#f3f4f6; font-family:Segoe UI, Arial, Helvetica, sans-serif; color:#252525;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0"
        style="background-color:#f3f4f6; padding:24px 10px; border-collapse:collapse;">
        <tr>
            <td align="center">

                <table width="820" cellpadding="0" cellspacing="0" border="0"
                    style="width:100%; max-width:820px; background-color:#ffffff; border-collapse:collapse; border:1px solid #dedede; box-shadow:0 8px 28px rgba(0,0,0,0.08);">

                    <!-- ENCABEZADO -->
                    <tr>
                        <td
                            style="background-color:{{ $colorVino }}; padding:15px 22px; border-bottom:4px solid #4d0e27;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="border-collapse:collapse;">
                                <tr>
                                    <td align="left" style="vertical-align:middle;">
                                        <img src="{{ $logoSrc }}" alt="IMSS Bienestar" width="220"
                                            style="display:block; width:220px; max-width:220px; height:auto; border:0; outline:none; text-decoration:none;">
                                    </td>

                                    <td align="right" style="vertical-align:middle;">
                                        <p
                                            style="margin:0; color:#ffffff; font-size:18px; font-weight:700; line-height:1.3;">
                                            Sistema de Constancias
                                        </p>
                                        <p
                                            style="margin:3px 0 0; color:{{ $colorDorado }}; font-size:12px; font-weight:600; line-height:1.3;">
                                            IMSS-BIENESTAR
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- CONTENIDO -->
                    <tr>
                        <td style="padding:24px 22px 18px;">

                            <!-- DESTINATARIO -->
                            <p
                                style="margin:0; color:#7a7a7a; font-size:11px; font-weight:800; letter-spacing:0.6px; text-transform:uppercase;">
                                Destinatario
                            </p>

                            <p
                                style="margin:4px 0 20px; color:#1f1f1f; font-size:24px; font-weight:800; line-height:1.25; text-transform:uppercase;">
                                {{ $nombrePersonaSafe }}
                            </p>

                            <!-- ESTATUS GENERAL -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="border-collapse:collapse; margin-bottom:18px;">
                                <tr>
                                    <td
                                        style="background-color:{{ $colorFondoEstatus }}; border:1px solid {{ $colorBordeEstatus }}; border-radius:8px; padding:14px 16px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                            style="border-collapse:collapse;">
                                            <tr>
                                                <td width="34" valign="top" style="width:34px;">
                                                    <div
                                                        style="width:26px; height:26px; line-height:26px; border-radius:50%; background-color:{{ $colorPrincipal }}; color:#ffffff; text-align:center; font-size:15px; font-weight:800;">
                                                        {{ $esAceptado ? '✓' : '!' }}
                                                    </div>
                                                </td>

                                                <td valign="middle">
                                                    <p
                                                        style="margin:0; color:{{ $colorPrincipal }}; font-size:16px; font-weight:800; line-height:1.4;">
                                                        {{ $tituloDecision }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- BLOQUES DETALLE / OBSERVACIONES -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="border-collapse:collapse;">
                                <tr>

                                    <!-- DETALLE -->
                                    <td width="49%" valign="top" style="padding-right:8px; padding-bottom:16px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                            style="border-collapse:collapse; border:1px solid #dedede; background-color:#ffffff;">
                                            <tr>
                                                <td
                                                    style="padding:11px 14px; background-color:#f6f6f6; border-bottom:1px solid #dedede;">
                                                    <p
                                                        style="margin:0; color:#5b5b5b; font-size:11px; font-weight:800; letter-spacing:0.3px; text-transform:uppercase;">
                                                        Detalle de constancia
                                                    </p>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="padding:15px 14px;">

                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                                        style="border-collapse:collapse;">
                                                        <tr>
                                                            <td width="50%" valign="top" style="padding:0 8px 14px 0;">
                                                                <p
                                                                    style="margin:0; color:#777777; font-size:11px; font-weight:800;">
                                                                    Folio / ID
                                                                </p>
                                                                <p
                                                                    style="margin:4px 0 0; color:#222222; font-size:19px; font-weight:800;">
                                                                    {{ $folioSafe }}
                                                                </p>
                                                            </td>

                                                            <td width="50%" valign="top" style="padding:0 0 14px 8px;">
                                                                <p
                                                                    style="margin:0; color:#777777; font-size:11px; font-weight:800;">
                                                                    Fecha y hora
                                                                </p>
                                                                <p
                                                                    style="margin:6px 0 0; color:#222222; font-size:14px; font-weight:800;">
                                                                    {{ $fechaHoraSafe }}
                                                                </p>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td colspan="2"
                                                                style="padding:13px 0; border-top:1px solid #eeeeee;">
                                                                <p
                                                                    style="margin:0; color:#777777; font-size:11px; font-weight:800;">
                                                                    Nombre del curso
                                                                </p>
                                                                <p
                                                                    style="margin:6px 0 0; color:#222222; font-size:15px; font-weight:800; line-height:1.4; text-transform:uppercase;">
                                                                    {{ $nombreCursoSafe }}
                                                                </p>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td colspan="2"
                                                                style="padding:13px 0 0; border-top:1px solid #eeeeee;">
                                                                <p
                                                                    style="margin:0; color:#777777; font-size:11px; font-weight:800;">
                                                                    Estatus actual
                                                                </p>
                                                                <p
                                                                    style="margin:6px 0 0; color:{{ $colorPrincipal }}; font-size:15px; font-weight:900;">
                                                                    {{ $estatusTexto }}
                                                                </p>
                                                            </td>
                                                        </tr>
                                                    </table>

                                                </td>
                                            </tr>
                                        </table>
                                    </td>

                                    <!-- OBSERVACIONES -->
                                    <td width="51%" valign="top" style="padding-left:8px; padding-bottom:16px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                            style="border-collapse:collapse; border:1px solid #dedede; background-color:#ffffff;">
                                            <tr>
                                                <td
                                                    style="padding:11px 14px; background-color:#fff7f7; border-bottom:1px solid #dedede;">
                                                    <p
                                                        style="margin:0; color:#9b2247; font-size:11px; font-weight:800; letter-spacing:0.3px; text-transform:uppercase;">
                                                        Observaciones
                                                    </p>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="padding:15px 14px;">

                                                    @if (!$esAceptado)
                                                        <p
                                                            style="margin:0 0 8px; color:#9b2247; font-size:11.5px; font-weight:800;">
                                                            Motivo del rechazo:
                                                        </p>

                                                        <div
                                                            style="background-color:#fffafa; border-left:4px solid #d7a0a0; padding:12px 14px; color:#444444; font-size:13px; line-height:1.6; font-style:italic;">
                                                            {!! nl2br(e($motivoSafe !== '' ? $motivoSafe : 'Sin motivo especificado.')) !!}
                                                        </div>

                                                        <div
                                                            style="margin-top:14px; background-color:#fafafa; border:1px solid #eeeeee; padding:12px 14px; color:#555555; font-size:13px; line-height:1.6;">
                                                            Te solicitamos revisar la observación indicada y realizar las
                                                            correcciones correspondientes.
                                                            Una vez realizadas, deberás registrar nuevamente tu constancia.
                                                        </div>
                                                    @else
                                                        <div
                                                            style="background-color:#f4fbf8; border-left:4px solid #1e5b4f; padding:12px 14px; color:#35584f; font-size:13px; line-height:1.6; font-weight:800;">
                                                            Tu registro fue validado correctamente y no presenta
                                                            observaciones pendientes.
                                                        </div>
                                                    @endif

                                                </td>
                                            </tr>
                                        </table>
                                    </td>

                                </tr>
                            </table>

                            <!-- HISTORIAL DE CAPACITACIÓN -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="border-collapse:collapse; border:1px solid #dedede; background-color:#ffffff; margin-bottom:20px;">
                                <tr>
                                    <td
                                        style="padding:12px 14px; background-color:#f6f6f6; border-bottom:1px solid #dedede;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                            style="border-collapse:collapse;">
                                            <tr>
                                                <td align="left" style="vertical-align:middle;">
                                                    <p
                                                        style="margin:0; color:#333333; font-size:14px; font-weight:800; line-height:1.3;">
                                                        Historial de Capacitación
                                                    </p>
                                                </td>

                                                <td align="right" style="vertical-align:middle;">
                                                    <span
                                                        style="display:inline-block; background-color:#1e5b4f; color:#ffffff; font-size:10px; font-weight:800; padding:5px 8px; border-radius:12px; text-transform:uppercase;">
                                                        {{ $historialTotal }}
                                                        {{ $historialTotal === 1 ? 'registro' : 'registros' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>


                                <tr>
                                    <td style="padding:0;">
                                        @if ($historialTotal > 0)
                                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                                style="border-collapse:collapse; font-size:10.5px;">
                                                <thead>
                                                    <tr>
                                                        <th align="left"
                                                            style="padding:9px 8px; background-color:#eeeeee; border-bottom:1px solid #d8d8d8; color:#666666; font-size:9.5px; font-weight:800; text-transform:uppercase;">
                                                            Nombre de acción
                                                        </th>
                                                        <th align="center"
                                                            style="padding:9px 6px; background-color:#eeeeee; border-bottom:1px solid #d8d8d8; color:#666666; font-size:9.5px; font-weight:800; text-transform:uppercase; width:45px;">
                                                            Horas reales
                                                        </th>
                                                        <th align="center"
                                                            style="padding:9px 6px; background-color:#eeeeee; border-bottom:1px solid #d8d8d8; color:#666666; font-size:9.5px; font-weight:800; text-transform:uppercase; width:70px;">
                                                            Inicio
                                                        </th>
                                                        <th align="center"
                                                            style="padding:9px 6px; background-color:#eeeeee; border-bottom:1px solid #d8d8d8; color:#666666; font-size:9.5px; font-weight:800; text-transform:uppercase; width:70px;">
                                                            Fin
                                                        </th>
                                                        <th align="center"
                                                            style="padding:9px 6px; background-color:#eeeeee; border-bottom:1px solid #d8d8d8; color:#666666; font-size:9.5px; font-weight:800; text-transform:uppercase; width:50px;">
                                                            Calif.
                                                        </th>
                                                        <th align="center"
                                                            style="padding:9px 6px; background-color:#eeeeee; border-bottom:1px solid #d8d8d8; color:#666666; font-size:9.5px; font-weight:800; text-transform:uppercase; width:70px;">
                                                            Estatus
                                                        </th>
                                                        <th align="left"
                                                            style="padding:9px 8px; background-color:#eeeeee; border-bottom:1px solid #d8d8d8; color:#666666; font-size:9.5px; font-weight:800; text-transform:uppercase; width:110px;">
                                                            Observaciones
                                                        </th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    @foreach ($historialRows as $historial)
                                                        @php
                                                            $hNombre = trim((string) data_get($historial, 'nombre_accion', ''));
                                                            $hHorasReal = data_get($historial, 'horas_real', null);
                                                            $hInicio = data_get($historial, 'fecha_ini', null);
                                                            $hFin = data_get($historial, 'fecha_fin', null);
                                                            $hCalificacion = data_get($historial, 'calificacion', null);
                                                            $hObservaciones = trim((string) data_get($historial, 'observaciones', ''));
                                                            $hEstatus = trim((string) data_get($historial, 'estatus_accion', ''));

                                                            if ($hEstatus === '') {
                                                                $hEstatus = !empty($hFin) ? 'CONCLUIDO' : 'PENDIENTE';
                                                            }

                                                            $hEstatusNormalizado = strtoupper($hEstatus);
                                                            $hEstatusColor = $hEstatusNormalizado === 'CONCLUIDO' ? '#1e5b4f' : '#777777';
                                                            $hEstatusFondo = $hEstatusNormalizado === 'CONCLUIDO' ? '#e5f4ef' : '#f2f2f2';

                                                            // Para registros pendientes no se muestran horas reales acumuladas ni calificación.
                                                            // Se muestra 0 horas para dejar claro que aún no aporta al total.
                                                            $hHorasVista = $hEstatusNormalizado === 'CONCLUIDO'
                                                                ? $formatNumero($parseHorasNumero($hHorasReal))
                                                                : '0';

                                                            $hCalificacionVista = $hEstatusNormalizado === 'CONCLUIDO'
                                                                ? $formatNumero($hCalificacion)
                                                                : '';
                                                        @endphp

                                                        <tr>
                                                            <td valign="top"
                                                                style="padding:9px 8px; border-bottom:1px solid #eeeeee; color:#333333; font-size:10.5px; font-weight:400; line-height:1.4; text-transform:uppercase;">
                                                                {{ $hNombre !== '' ? $hNombre : 'SIN NOMBRE' }}
                                                            </td>
                                                            <td align="center" valign="top"
                                                                style="padding:9px 6px; border-bottom:1px solid #eeeeee; color:#333333; font-size:10.5px; font-weight:700;">
                                                                {{ $hHorasVista }}
                                                            </td>
                                                            <td align="center" valign="top"
                                                                style="padding:9px 6px; border-bottom:1px solid #eeeeee; color:#444444; font-size:10px;">
                                                                {{ $formatFecha($hInicio) }}
                                                            </td>
                                                            <td align="center" valign="top"
                                                                style="padding:9px 6px; border-bottom:1px solid #eeeeee; color:#444444; font-size:10px;">
                                                                {{ $formatFecha($hFin) }}
                                                            </td>
                                                            <td align="center" valign="top"
                                                                style="padding:9px 6px; border-bottom:1px solid #eeeeee; color:#9b2247; font-size:10.5px; font-weight:800;">
                                                                {{ $hCalificacionVista }}
                                                            </td>
                                                            <td align="center" valign="top"
                                                                style="padding:9px 6px; border-bottom:1px solid #eeeeee;">
                                                                <span
                                                                    style="display:inline-block; background-color:{{ $hEstatusFondo }}; color:{{ $hEstatusColor }}; font-size:8.8px; font-weight:800; padding:4px 6px; border-radius:4px; text-transform:uppercase;">
                                                                    {{ $hEstatusNormalizado }}
                                                                </span>
                                                            </td>
                                                            <td valign="top"
                                                                style="padding:9px 8px; border-bottom:1px solid #eeeeee; color:#555555; font-size:10px; line-height:1.4; font-style:italic; text-transform:uppercase;">
                                                                {{ $hObservaciones !== '' ? $hObservaciones : '-' }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @else
                                            <div style="padding:14px 16px; color:#666666; font-size:13px; line-height:1.6;">
                                                No se encontraron registros previos de capacitación para este empleado.
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            </table>



                        </td>
                    </tr>

                    <!-- FRANJA INFERIOR -->
                    <tr>
                        <td style="height:10px; background-color:#1e5b4f; font-size:0; line-height:0;">
                            &nbsp;
                        </td>
                    </tr>

                    <!-- PIE -->
                    <tr>
                        <td style="background-color:#eeeeee; padding:16px 22px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="border-collapse:collapse;">
                                <tr>
                                    <td align="left" valign="top"
                                        style="color:#666666; font-size:11.5px; line-height:1.5;">
                                        <strong>IMSS-BIENESTAR</strong><br>
                                        <p style="margin:4px 0 0; color:#666666; font-size:11px; line-height:1.4;">
                                            UNIDAD DE ADMINISTRACIÓN Y FINANZAS</p>
                                        <p style="margin:0; color:#666666; font-size:11px; line-height:1.4;">
                                            COORDINACIÓN DE RECURSOS HUMANOS</p>
                                        <p style="margin:0; color:#666666; font-size:11px; line-height:1.4;">
                                            COORDINACIÓN TÉCNICA DE CAPACITACIÓN Y EVALUACIÓN</p>
                                    </td>

                                    <td align="right" valign="top"
                                        style="color:#777777; font-size:11.5px; line-height:1.5;">
                                        <strong style="color:#611232;">CAPACITACIÓN</strong><br>
                                        <strong>RECURSOS HUMANOS</strong><br>
                                        Calle Gustavo E. Campa 54, piso 3, Guadalupe Inn.<br>
                                        Álvaro Obregón, 01020, Ciudad de México.<br> 
                                        Para cualquier duda o aclaración, comunicate al: <br>
                                        Tel: 01(55) 9160 8100 Ext. 111106<br>
                                        Correo: capacitacionrh@imssbienestar.gob.mx
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
