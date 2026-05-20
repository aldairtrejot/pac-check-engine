@php
    $esAceptado = $decision === 'ACEPTADO';

    $colorPrincipal = $esAceptado ? '#1e5b4f' : '#9b2247';
    $colorFondo = $esAceptado ? '#eef8f5' : '#fff4f6';
    $tituloDecision = $esAceptado ? 'Constancia aceptada correctamente' : 'Constancia rechazada';
    $estatusTexto = $esAceptado ? 'ACEPTADA' : 'RECHAZADA';

    $textoDecision = $esAceptado
        ? 'Te informamos que tu constancia fue aceptada y procesada correctamente.'
        : 'Te informamos que tu trámite/constancia fue rechazado.';

    /*
     |--------------------------------------------------------------------------
     | Logo incrustado para correo
     |--------------------------------------------------------------------------
     | Archivo esperado:
     | public/assets/imss/logo-v-color.png
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

<body style="margin:0; padding:0; background-color:#f3f5f6; font-family:Segoe UI, Arial, sans-serif; color:#253039;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f3f5f6; padding:24px 12px; border-collapse:collapse;">
        <tr>
            <td align="center">
                <table width="680" cellpadding="0" cellspacing="0" border="0" style="max-width:680px; width:100%; background-color:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 8px 28px rgba(22,26,29,0.12); border-collapse:collapse;">

                    <!-- LOGO SUPERIOR -->
                    <tr>
                        <td style="padding:18px 24px; background-color:#ffffff; border-bottom:1px solid #dde2e5;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                                <tr>
                                    <td align="left" style="vertical-align:middle;">
                                        <img
                                            src="{{ $logoSrc }}"
                                            alt="IMSS Bienestar"
                                            width="260"
                                            style="display:block; width:260px; max-width:260px; height:auto; border:0; outline:none; text-decoration:none;"
                                        >
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- ENCABEZADO COLOR -->
                    <tr>
                        <td style="background-color:#611232; padding:18px 26px;">
                            <h1 style="margin:0; color:#ffffff; font-size:21px; font-weight:700; line-height:1.25;">
                                Sistema de Constancias
                            </h1>

                            <p style="margin:5px 0 0; color:#d6bd74; font-size:14px; font-weight:600; line-height:1.4;">
                                IMSS-BIENESTAR · Notificación de validación
                            </p>
                        </td>
                    </tr>

                    <!-- CONTENIDO -->
                    <tr>
                        <td style="padding:30px 28px 22px;">
                            <p style="margin:0 0 18px; font-size:16px; line-height:1.6;">
                                Estimado(a)
                                <strong>{{ $nombrePersona ?: 'usuario' }}</strong>:
                            </p>

                            <div style="background-color:{{ $colorFondo }}; border-left:5px solid {{ $colorPrincipal }}; border-radius:10px; padding:16px 18px; margin-bottom:22px;">
                                <h2 style="margin:0 0 8px; color:{{ $colorPrincipal }}; font-size:20px; line-height:1.3;">
                                    {{ $tituloDecision }}
                                </h2>

                                <p style="margin:0; font-size:15.5px; line-height:1.6;">
                                    {{ $textoDecision }}
                                </p>
                            </div>

                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; margin:22px 0; font-size:15px;">
                                <tr>
                                    <td style="padding:10px 12px; background-color:#f8f9fa; border:1px solid #dde2e5; color:#611232; font-weight:700; width:35%;">
                                        Folio / ID
                                    </td>
                                    <td style="padding:10px 12px; border:1px solid #dde2e5; color:#253039;">
                                        {{ $folio }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:10px 12px; background-color:#f8f9fa; border:1px solid #dde2e5; color:#611232; font-weight:700;">
                                        Nombre del empleado
                                    </td>
                                    <td style="padding:10px 12px; border:1px solid #dde2e5; color:#253039;">
                                        {{ $nombrePersona }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:10px 12px; background-color:#f8f9fa; border:1px solid #dde2e5; color:#611232; font-weight:700;">
                                        Nombre del curso
                                    </td>
                                    <td style="padding:10px 12px; border:1px solid #dde2e5; color:#253039;">
                                        {{ $nombreCurso }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:10px 12px; background-color:#f8f9fa; border:1px solid #dde2e5; color:#611232; font-weight:700;">
                                        Fecha y hora
                                    </td>
                                    <td style="padding:10px 12px; border:1px solid #dde2e5; color:#253039;">
                                        {{ $fechaHora }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:10px 12px; background-color:#f8f9fa; border:1px solid #dde2e5; color:#611232; font-weight:700;">
                                        Estatus
                                    </td>
                                    <td style="padding:10px 12px; border:1px solid #dde2e5; color:{{ $colorPrincipal }}; font-weight:700;">
                                        {{ $estatusTexto }}
                                    </td>
                                </tr>
                            </table>

                            @if (! $esAceptado)
                                <p style="margin:20px 0 8px; font-size:15.5px; line-height:1.6; font-weight:700; color:#611232;">
                                    Motivo del rechazo:
                                </p>

                                <div style="padding:14px 16px; background-color:#fff4f6; border:1px solid rgba(155,34,71,0.25); border-radius:10px; color:#253039; font-size:15px; line-height:1.6;">
                                    {!! nl2br(e($motivo ?: 'Sin motivo especificado.')) !!}
                                </div>

                                <p style="margin:18px 0 0; font-size:15px; line-height:1.6;">
                                    Te solicitamos revisar la observación indicada para realizar las correcciones correspondientes, una vez realizadas volver a registrar tu constancia.
                                </p>
                            @else
                                <p style="margin:18px 0 0; font-size:15px; line-height:1.6;">
                                    Tu registro fue validado correctamente.
                                </p>
                            @endif

                            <p style="margin:28px 0 0; font-size:15px; line-height:1.6;">
                                Atentamente,<br>
                                <strong style="color:#1e5b4f;">
                                    Sistema de Constancias IMSS-BIENESTAR
                                </strong>
                            </p>
                        </td>
                    </tr>

                    <!-- FRANJA INFERIOR -->
                    <tr>
                        <td style="height:12px; background-color:#1e5b4f; font-size:0; line-height:0;">
                            &nbsp;
                        </td>
                    </tr>

                    <!-- PIE -->
                    <tr>
                        <td style="padding:18px 24px; background-color:#f2f2f2; text-align:center;">
                            <p style="margin:0; font-size:12.5px; color:#777777; line-height:1.5;">
                                Este es un correo de notificación automática. Por favor, no responda a este mensaje.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>