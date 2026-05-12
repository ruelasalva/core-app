<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo isset($title) ? $title : 'Pagina no encontrada'; ?> | Core-App</title>

    <?php echo Asset::css('bootstrap.min.css'); ?>
    <?php echo Asset::css('bootstrap-icons.css'); ?>

    <style>
        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f6f9;
            color: #343a40;
            font-family: "Source Sans Pro", Arial, sans-serif;
        }

        .error-page {
            width: min(92vw, 760px);
            padding: 32px;
        }

        .error-shell {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 28px;
            align-items: center;
            padding: 32px;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 10px 28px rgba(52, 58, 64, 0.08);
        }

        .error-code {
            display: flex;
            width: 150px;
            height: 150px;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #343a40;
            color: #fff;
            font-size: 46px;
            font-weight: 700;
        }

        .brand {
            margin-bottom: 8px;
            color: #007bff;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0;
        }

        h1 {
            margin: 0 0 10px;
            font-size: 28px;
            font-weight: 700;
        }

        p {
            margin: 0 0 22px;
            color: #6c757d;
            font-size: 16px;
            line-height: 1.5;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn {
            border-radius: 6px;
        }

        @media (max-width: 640px) {
            .error-page {
                padding: 18px;
            }

            .error-shell {
                grid-template-columns: 1fr;
                padding: 24px;
                text-align: center;
            }

            .error-code {
                width: 100%;
                height: 110px;
                font-size: 42px;
            }

            .actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <main class="error-page">
        <section class="error-shell">
            <div class="error-code">404</div>

            <div>
                <div class="brand">CORE-APP ERP</div>
                <h1>Pagina no encontrada</h1>
                <p>La ruta que intentaste abrir no existe, fue movida o no esta disponible para tu sesion actual.</p>

                <div class="actions">
                    <button type="button" class="btn btn-primary" onclick="history.length > 1 ? history.back() : window.location.href='<?php echo Uri::create('/'); ?>';">
                        <i class="bi bi-arrow-left"></i> Regresar
                    </button>
                    <?php if (Auth::check()): ?>
                        <a class="btn btn-outline-secondary" href="<?php echo Uri::create('/'); ?>">
                            <i class="bi bi-house"></i> Inicio
                        </a>
                    <?php else: ?>
                        <a class="btn btn-outline-secondary" href="<?php echo Uri::create('login'); ?>">
                            <i class="bi bi-box-arrow-in-right"></i> Iniciar sesion
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
