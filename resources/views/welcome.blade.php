<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel') }}</title>

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont,
                "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at 15% 20%, rgba(13, 110, 253, .16), transparent 30%),
                radial-gradient(circle at 85% 80%, rgba(111, 66, 193, .15), transparent 30%),
                #07111f;
            color: #fff;
            overflow-x: hidden;
        }

        .background-grid {
            position: fixed;
            inset: 0;
            pointer-events: none;
            opacity: .25;
            background-image:
                linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px);
            background-size: 45px 45px;
        }

        .navbar-custom {
            position: relative;
            z-index: 10;
            padding: 25px 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.15rem;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0d6efd, #6610f2);
            box-shadow: 0 8px 25px rgba(13, 110, 253, .3);
        }

        .auth-links {
            display: flex;
            gap: 10px;
        }

        .auth-links a {
            text-decoration: none;
            color: #dbe7f5;
            padding: 9px 16px;
            border-radius: 9px;
            transition: .25s ease;
        }

        .auth-links a:hover {
            color: white;
            background: rgba(255,255,255,.08);
        }

        .btn-register {
            background: #0d6efd !important;
            color: white !important;
            box-shadow: 0 8px 25px rgba(13,110,253,.25);
        }

        .btn-register:hover {
            background: #0b5ed7 !important;
            transform: translateY(-2px);
        }

        .hero {
            position: relative;
            z-index: 2;
            min-height: calc(100vh - 100px);
            display: flex;
            align-items: center;
            padding: 60px 0 100px;
        }

        .hero-content {
            max-width: 760px;
        }

        .badge-tech {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border: 1px solid rgba(13,110,253,.35);
            border-radius: 50px;
            background: rgba(13,110,253,.08);
            color: #8dbaff;
            font-size: .85rem;
            margin-bottom: 25px;
        }

        .hero h1 {
            font-size: clamp(2.8rem, 6vw, 5.2rem);
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: -2px;
            margin-bottom: 25px;
        }

        .hero h1 span {
            background: linear-gradient(90deg, #5aa2ff, #9b6cff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            color: #aebdce;
            font-size: 1.15rem;
            line-height: 1.8;
            max-width: 650px;
            margin-bottom: 35px;
        }

        .hero-buttons {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }

        .btn-main {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 13px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: .25s ease;
        }

        .btn-primary-custom {
            color: white;
            background: linear-gradient(135deg, #0d6efd, #6610f2);
            box-shadow: 0 12px 35px rgba(13,110,253,.25);
        }

        .btn-primary-custom:hover {
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 16px 40px rgba(13,110,253,.35);
        }

        .btn-outline-custom {
            color: #dce8f5;
            border: 1px solid rgba(255,255,255,.15);
            background: rgba(255,255,255,.03);
        }

        .btn-outline-custom:hover {
            color: white;
            background: rgba(255,255,255,.08);
            transform: translateY(-3px);
        }

        .tech-card {
            position: relative;
            padding: 35px;
            border-radius: 24px;
            background: rgba(255,255,255,.045);
            border: 1px solid rgba(255,255,255,.09);
            backdrop-filter: blur(12px);
            box-shadow: 0 25px 80px rgba(0,0,0,.25);
        }

        .tech-card::before {
            content: "";
            position: absolute;
            width: 120px;
            height: 120px;
            background: #0d6efd;
            filter: blur(80px);
            opacity: .35;
            top: -40px;
            right: -30px;
        }

        .icon-box {
            width: 55px;
            height: 55px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(13,110,253,.12);
            color: #69a7ff;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        .tech-card h3 {
            font-size: 1.25rem;
            margin-bottom: 10px;
        }

        .tech-card p {
            font-size: .95rem;
            margin: 0;
            line-height: 1.6;
        }

        .status {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 25px;
            color: #9fb0c2;
            font-size: .85rem;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #20c997;
            border-radius: 50%;
            box-shadow: 0 0 12px #20c997;
        }

        footer {
            position: absolute;
            bottom: 20px;
            width: 100%;
            text-align: center;
            color: #68788a;
            font-size: .8rem;
            z-index: 2;
        }

        @media (max-width: 991px) {
            .hero {
                padding-top: 30px;
            }

            .tech-card {
                margin-top: 50px;
            }
        }

        @media (max-width: 576px) {
            .navbar-custom {
                padding: 18px 0;
            }

            .auth-links a:not(.btn-register) {
                display: none;
            }

            .hero h1 {
                letter-spacing: -1px;
            }

            .hero p {
                font-size: 1rem;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .btn-main {
                justify-content: center;
            }
        }
    </style>
</head>

<body>

    <div class="background-grid"></div>

    {{-- NAVBAR --}}
    <nav class="navbar-custom">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">

                <a href="{{ url('/') }}" class="brand">
                    <div class="brand-icon">
                        <i class="bi bi-cpu"></i>
                    </div>

                    <span>{{ config('app.name', 'Laravel') }}</span>
                </a>

                <div class="auth-links">

                    @auth
                        <a href="{{ url('/dashboard') }}">
                            <i class="bi bi-grid me-1"></i>
                            Dashboard
                        </a>
                    @else

                        <a href="{{ route('login') }}">
                            Iniciar sesión
                        </a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                               class="btn-register">
                                Registrarse
                            </a>
                        @endif

                    @endauth

                </div>

            </div>
        </div>
    </nav>


    {{-- HERO --}}
    <main class="hero">

        <div class="container">

            <div class="row align-items-center g-5">

                <div class="col-lg-7">

                    <div class="hero-content">

                        <div class="badge-tech">
                            <i class="bi bi-stars"></i>
                            Tecnología · Innovación · Futuro
                        </div>

                        <h1>
                            Construye algo
                            <span>extraordinario.</span>
                        </h1>

                        <p>
                            Una plataforma moderna, segura y preparada
                            para llevar tus proyectos al siguiente nivel.
                            Simple por fuera. Potente por dentro.
                        </p>

                        <div class="hero-buttons">

                            @auth

                                <a href="{{ url('/dashboard') }}"
                                   class="btn-main btn-primary-custom">
                                    <i class="bi bi-speedometer2"></i>
                                    Ir al Dashboard
                                </a>

                            @else

                                <a href="{{ route('login') }}"
                                   class="btn-main btn-primary-custom">
                                    <i class="bi bi-box-arrow-in-right"></i>
                                    Iniciar sesión
                                </a>

                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}"
                                       class="btn-main btn-outline-custom">
                                        <i class="bi bi-person-plus"></i>
                                        Crear cuenta
                                    </a>
                                @endif

                            @endauth

                        </div>

                    </div>

                </div>


                {{-- CARD --}}
                <div class="col-lg-5">

                    <div class="tech-card">

                        <div class="icon-box">
                            <i class="bi bi-cpu"></i>
                        </div>

                        <h3>
                            Plataforma inteligente
                        </h3>

                        <p>
                            Diseñada para ofrecer una experiencia
                            rápida, moderna y adaptable a las necesidades
                            de tu organización.
                        </p>

                        <div class="status">
                            <span class="status-dot"></span>
                            Sistema operativo
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </main>


    <footer>
        © {{ date('Y') }} {{ config('app.name', 'Laravel') }}
        · Todos los derechos reservados
    </footer>

</body>
</html>
```
