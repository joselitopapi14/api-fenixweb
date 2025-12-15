<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Validación de Boleta')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #f97316;
            --primary-dark: #ea580c;
            --primary-light: #fed7aa;
            --secondary-color: #6b7280;
            --success-color: #16a34a;
            --warning-color: #eab308;
            --danger-color: #dc2626;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }

        body {
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            min-height: 100vh;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--gray-800);
        }

        .header-bg {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem 0;
            box-shadow: 0 4px 20px rgba(249, 115, 22, 0.15);
        }        .main-container {
            margin-top: 2rem;
            margin-bottom: 2rem;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            background: white;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            border-bottom: none;
            padding: 1.5rem;
            font-weight: 600;
        }        .status-badge {
            font-size: 1.1rem;
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-success { background-color: var(--success-color); color: white; }
        .status-warning { background-color: var(--warning-color); color: var(--gray-900); }
        .status-danger { background-color: var(--danger-color); color: white; }
        .status-info { background-color: var(--primary-light); color: var(--primary-dark); }
        .status-secondary { background-color: var(--secondary-color); color: white; }

        .info-row {
            padding: 0.875rem 0;
            border-bottom: 1px solid var(--gray-200);
            transition: background-color 0.2s ease;
        }

        .info-row:hover {
            background-color: var(--gray-50);
            margin: 0 -1rem;
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: var(--primary-dark);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 10px;
            padding: 0.875rem 2.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(249, 115, 22, 0.2);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #c2410c 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.3);
        }        .footer {
            background: linear-gradient(135deg, var(--gray-50) 0%, white 100%);
            padding: 1.5rem 0;
            text-align: center;
            color: var(--secondary-color);
            border-top: 1px solid var(--gray-200);
            margin-top: auto;
        }

        .icon-status {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }

        .productos-table {
            margin-top: 1rem;
        }

        .productos-table th {
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            border-top: none;
            color: var(--primary-dark);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        @media (max-width: 768px) {
            .main-container {
                margin-top: 1rem;
                margin-bottom: 1rem;
            }

            .card-header {
                padding: 1rem;
            }

            .info-row {
                flex-direction: column;
            }

            .info-label {
                margin-bottom: 0.25rem;
            }
        }

        /* Animaciones */
        .card {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .icon-status {
            animation: scaleIn 0.5s ease-out 0.3s both;
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.5);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Mejoras visuales */
        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
            border-radius: 10px;
            padding: 0.875rem 2.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.2);
        }
    </style>
</head>
<body class="d-flex flex-column">
    <header class="header-bg">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-shield-alt me-2"></i>
                        Validación de Boleta de Empeño
                    </h1>
                    <p class="mb-0 opacity-75">Sistema de verificación pública</p>
                </div>
            </div>
        </div>
    </header>

    <main class="main-container flex-grow-1">
        <div class="container">
            @yield('content')
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p class="mb-0">
                <small>
                    <i class="fas fa-lock me-1"></i>
                    Sistema seguro de validación - Todos los derechos reservados
                </small>
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
