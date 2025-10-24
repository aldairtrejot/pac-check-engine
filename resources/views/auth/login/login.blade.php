<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Validación de Plantillas PAC</title>
    <link id="pagestyle" href="{{ asset('assets/app/css/soft-ui-dashboard.css') }}" rel="stylesheet" />
    <link rel="shortcut icon" href="{{ asset('assets/images/bienestar/favicon.png') }}" />
    <link rel="stylesheet" href="{{ asset('assets/icons/fontawesome/css/all.min.css') }}">
</head>

@include('components.Helpers.spinner')
@include('components.message.error-messages')

<body class="" style="margin:0; padding:0;">
    <main class="main-content" style="padding:0; margin:0;">
        <section style="padding:0; margin:0;">
            <div class="page-header d-flex justify-content-center align-items-center"
                 style="padding:0; margin:0; min-height:100vh; overflow:visible;">
                <div class="col-xl-4 col-lg-5 col-md-6" style="padding:0;">
                    <div class="card card-plain"
                         style="background-color: transparent !important; 
                                box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4) !important; 
                                border-radius: 12px !important;">
                        <div class="card-header pb-0 text-left bg-transparent">
                            <h3 class="font-weight-bolder text-info text-gradient text-center">
                                Validación de Plantillas PAC
                            </h3>
                            <p class="mb-0 text-center">Introduce tu usuario y contraseña para iniciar sesión.</p>
                        </div>
                        <div class="card-body">
                            <div id="vue_form_app_is_not_payroll">
                                <form role="form" id="form_login_is_not_payroll" enctype="multipart/form-data">
                                    @csrf

                                    <label for="email">Usuario</label>
                                    <div class="mb-3">
                                        <input type="email" class="form-control" placeholder="Email"
                                               aria-label="Email" aria-describedby="email-addon" id="email"
                                               name="email" autocomplete="username">
                                        <div id="error-email" class="text-danger text-error"
                                             style="margin-top: 5px;"></div>
                                    </div>

                                    <label for="password">Contraseña</label>
                                    <div class="mb-3">
                                        <input type="password" class="form-control" placeholder="Password"
                                               aria-label="Password" aria-describedby="password-addon"
                                               id="password" name="password" autocomplete="current-password">
                                        <div id="error-password" class="text-danger text-error"
                                             style="margin-top: 5px;"></div>
                                    </div>

                                    <div class="text-center">
                                        <button type="button" @click="send_data_form_is_not_payroll"
                                                class="btn bg-gradient-info w-100 mt-4 mb-0">Ingresar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="card-footer text-center pt-0 px-lg-2 px-1">
                            <p class="mb-4 text-sm mx-auto">
                                ¿Olvidaste tu contraseña?
                                <a href="{{ route('login') }}"
                                   class="text-info text-gradient font-weight-bold">accede aqui</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>

<!-- Include axios -->
@vite('resources/js/web-app.js')
{{-- end include axios --}}
</html>
