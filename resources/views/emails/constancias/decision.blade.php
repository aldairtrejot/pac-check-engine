@php
    $decisionNormalizada = strtoupper(trim((string) ($decision ?? '')));
    $esAceptado = in_array($decisionNormalizada, ['ACEPTADO', 'ACEPTADA'], true);

    $nombrePersonaSafe = trim((string) ($nombrePersona ?? ''));
    $nombreCursoSafe   = trim((string) ($nombreCurso ?? ''));
    $folioSafe         = trim((string) ($folio ?? ''));
    $fechaHoraSafe     = trim((string) ($fechaHora ?? ''));
    $motivoSafe        = trim((string) ($motivo ?? ''));

    $nombrePersonaSafe = $nombrePersonaSafe !== '' ? $nombrePersonaSafe : 'Usuario';
    $nombreCursoSafe   = $nombreCursoSafe !== '' ? $nombreCursoSafe : 'No especificado';
    $folioSafe         = $folioSafe !== '' ? $folioSafe : 'S/F';
    $fechaHoraSafe     = $fechaHoraSafe !== '' ? $fechaHoraSafe : 'No especificada';

    $colorVino = '#611232';
    $colorDorado = '#d6bd74';

    $colorPrincipal = $esAceptado ? '#1e5b4f' : '#b3261e';
    $colorFondoEstatus = $esAceptado ? '#eef8f5' : '#fff1f1';
    $colorBordeEstatus = $esAceptado ? '#b8d8cf' : '#f0c5c5';

    $tituloDecision = $esAceptado ? 'Constancia aceptada' : 'Constancia rechazada';
    $estatusTexto = $esAceptado ? 'ACEPTADA' : 'RECHAZADA';

    $textoDecision = $esAceptado
        ? 'Te informamos que tu constancia fue aceptada y procesada correctamente.'
        : 'Te informamos que tu constancia fue rechazada.';

    /*
     |--------------------------------------------------------------------------
     | Logo incrustado para correo
     |--------------------------------------------------------------------------
     | Archivo esperado:
     | public/assets/images/bienestar/logo-v-color.png
     |
     | Si la vista se abre desde navegador y no existe $message,
     | se usa asset() como respaldo.
     */
    $logoPath = public_path('assets/images/bienestar/logo-v-color.png');

    $logoSrc = isset($message) && file_exists($logoPath)
        ? $message->embed($logoPath)
        : asset('assets/images/bienestar/logo-v-color.png');
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $tituloDecision }}</title>
</head>

<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:Segoe UI, Arial, Helvetica, sans-serif; color:#252525;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f3f4f6; padding:24px 10px; border-collapse:collapse;">
        <tr>
            <td align="center">

                <table width="760" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:760px; background-color:#ffffff; border-collapse:collapse; border:1px solid #dedede; box-shadow:0 8px 28px rgba(0,0,0,0.08);">

                    <!-- ENCABEZADO -->
                    <tr>
                        <td style="background-color:{{ $colorVino }}; padding:15px 22px; border-bottom:4px solid #4d0e27;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                                <tr>
                                    <td align="left" style="vertical-align:middle;">
                                        <img
                                            src="{{ $logoSrc }}"
                                            alt="IMSS Bienestar"
                                            width="220"
                                            style="display:block; width:220px; max-width:220px; height:auto; border:0; outline:none; text-decoration:none;"
                                        >
                                    </td>

                                    <td align="right" style="vertical-align:middle;">
                                        <p style="margin:0; color:#ffffff; font-size:18px; font-weight:700; line-height:1.3;">
                                            Sistema de Constancias
                                        </p>
                                        <p style="margin:3px 0 0; color:{{ $colorDorado }}; font-size:12px; font-weight:600; line-height:1.3;">
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
                            <p style="margin:0; color:#7a7a7a; font-size:11px; font-weight:800; letter-spacing:0.6px; text-transform:uppercase;">
                                Destinatario
                            </p>

                            <p style="margin:4px 0 20px; color:#1f1f1f; font-size:24px; font-weight:800; line-height:1.25; text-transform:uppercase;">
                                {{ $nombrePersonaSafe }}
                            </p>

                            <!-- ESTATUS GENERAL -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; margin-bottom:18px;">
                                <tr>
                                    <td style="background-color:{{ $colorFondoEstatus }}; border:1px solid {{ $colorBordeEstatus }}; border-radius:8px; padding:14px 16px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                                            <tr>
                                                <td width="34" valign="top" style="width:34px;">
                                                    <div style="width:26px; height:26px; line-height:26px; border-radius:50%; background-color:{{ $colorPrincipal }}; color:#ffffff; text-align:center; font-size:15px; font-weight:800;">
                                                        {{ $esAceptado ? '✓' : '!' }}
                                                    </div>
                                                </td>

                                                <td valign="middle">
                                                    <p style="margin:0; color:{{ $colorPrincipal }}; font-size:16px; font-weight:800; line-height:1.4;">
                                                        {{ $tituloDecision }}
                                                    </p>

                                                    <p style="margin:5px 0 0; color:#3f3f3f; font-size:13.5px; line-height:1.6;">
                                                        {{ $textoDecision }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- BLOQUES DETALLE / OBSERVACIONES -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                                <tr>

                                    <!-- DETALLE -->
                                    <td width="49%" valign="top" style="padding-right:8px; padding-bottom:16px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; border:1px solid #dedede; background-color:#ffffff;">
                                            <tr>
                                                <td style="padding:11px 14px; background-color:#f6f6f6; border-bottom:1px solid #dedede;">
                                                    <p style="margin:0; color:#5b5b5b; font-size:11px; font-weight:800; letter-spacing:0.3px; text-transform:uppercase;">
                                                        Detalle de constancia
                                                    </p>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="padding:15px 14px;">

                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                                                        <tr>
                                                            <td width="50%" valign="top" style="padding:0 8px 14px 0;">
                                                                <p style="margin:0; color:#777777; font-size:11px; font-weight:800;">
                                                                    Folio / ID
                                                                </p>
                                                                <p style="margin:4px 0 0; color:#222222; font-size:19px; font-weight:800;">
                                                                    {{ $folioSafe }}
                                                                </p>
                                                            </td>

                                                            <td width="50%" valign="top" style="padding:0 0 14px 8px;">
                                                                <p style="margin:0; color:#777777; font-size:11px; font-weight:800;">
                                                                    Fecha y hora
                                                                </p>
                                                                <p style="margin:6px 0 0; color:#222222; font-size:14px; font-weight:800;">
                                                                    {{ $fechaHoraSafe }}
                                                                </p>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td colspan="2" style="padding:13px 0; border-top:1px solid #eeeeee;">
                                                                <p style="margin:0; color:#777777; font-size:11px; font-weight:800;">
                                                                    Nombre del curso
                                                                </p>
                                                                <p style="margin:6px 0 0; color:#222222; font-size:15px; font-weight:800; line-height:1.4; text-transform:uppercase;">
                                                                    {{ $nombreCursoSafe }}
                                                                </p>
                                                            </td>
                                                        </tr>

                                                        <tr>
                                                            <td colspan="2" style="padding:13px 0 0; border-top:1px solid #eeeeee;">
                                                                <p style="margin:0; color:#777777; font-size:11px; font-weight:800;">
                                                                    Estatus actual
                                                                </p>
                                                                <p style="margin:6px 0 0; color:{{ $colorPrincipal }}; font-size:15px; font-weight:900;">
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
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; border:1px solid #dedede; background-color:#ffffff;">
                                            <tr>
                                                <td style="padding:11px 14px; background-color:#fff7f7; border-bottom:1px solid #dedede;">
                                                    <p style="margin:0; color:#9b2247; font-size:11px; font-weight:800; letter-spacing:0.3px; text-transform:uppercase;">
                                                        Observaciones
                                                    </p>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td style="padding:15px 14px;">

                                                    @if (! $esAceptado)
                                                        <p style="margin:0 0 8px; color:#9b2247; font-size:11.5px; font-weight:800;">
                                                            Motivo del rechazo:
                                                        </p>

                                                        <div style="background-color:#fffafa; border-left:4px solid #d7a0a0; padding:12px 14px; color:#444444; font-size:13px; line-height:1.6; font-style:italic;">
                                                            {!! nl2br(e($motivoSafe !== '' ? $motivoSafe : 'Sin motivo especificado.')) !!}
                                                        </div>

                                                        <div style="margin-top:14px; background-color:#fafafa; border:1px solid #eeeeee; padding:12px 14px; color:#555555; font-size:13px; line-height:1.6;">
                                                            Te solicitamos revisar la observación indicada y realizar las correcciones correspondientes.
                                                            Una vez realizadas, deberás registrar nuevamente tu constancia.
                                                        </div>
                                                    @else
                                                        <div style="background-color:#f4fbf8; border-left:4px solid #1e5b4f; padding:12px 14px; color:#35584f; font-size:13px; line-height:1.6;">
                                                            Tu registro fue validado correctamente y no presenta observaciones pendientes.
                                                        </div>

                                                        <div style="margin-top:14px; background-color:#fafafa; border:1px solid #eeeeee; padding:12px 14px; color:#555555; font-size:13px; line-height:1.6;">
                                                            La constancia fue procesada en el sistema de capacitación.
                                                        </div>
                                                    @endif

                                                </td>
                                            </tr>
                                        </table>
                                    </td>

                                </tr>
                            </table>

                            <!-- NOTA -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; margin-top:2px; margin-bottom:20px;">
                                <tr>
                                    <td style="background-color:#fafafa; border:1px solid #e5e5e5; padding:13px 15px; color:#555555; font-size:13px; line-height:1.6;">
                                        Este mensaje corresponde a una notificación automática generada por el
                                        <strong>Sistema de Constancias IMSS-BIENESTAR</strong>.
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 4px; color:#333333; font-size:14px; line-height:1.6;">
                                Atentamente,
                            </p>

                            <p style="margin:0 0 18px; color:#1e5b4f; font-size:14px; font-weight:800; line-height:1.6;">
                                Sistema de Constancias IMSS-BIENESTAR
                            </p>

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
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                                <tr>
                                    <td align="left" valign="top" style="color:#666666; font-size:11.5px; line-height:1.5;">
                                        <strong>IMSS-BIENESTAR</strong><br>
                                        Sistema de Constancias
                                    </td>

                                    <td align="right" valign="top" style="color:#777777; font-size:11.5px; line-height:1.5;">
                                        Este es un correo de notificación automática.<br>
                                        Por favor, no responda a este mensaje.
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