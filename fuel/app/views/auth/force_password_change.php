<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cambio de contraseña requerido</title>
    <?php echo Asset::css('css/bootstrap.min.css'); ?>
    <?php echo Asset::css('css/adminlte.min.css'); ?>
    <style>
        .password-policy-page {
            min-height: 100vh;
            background: #eef3f8;
        }
        .password-policy-card {
            width: min(100%, 460px);
            border: 0;
            border-radius: 10px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .16);
        }
        .password-policy-brand {
            border-bottom: 1px solid #e5e9f0;
            padding: 24px 28px 18px;
        }
        .password-policy-body {
            padding: 26px 28px 28px;
        }
        .password-rule-list {
            margin: 0;
            padding-left: 18px;
        }
        .password-rule-list li {
            margin-bottom: 4px;
        }
    </style>
</head>
<body class="hold-transition password-policy-page d-flex align-items-center justify-content-center">
<div class="password-policy-card card">
    <div class="password-policy-brand text-center">
        <div class="h4 mb-1 font-weight-bold">CORE-APP</div>
        <div class="text-muted">Seguridad de acceso</div>
    </div>
    <div class="password-policy-body">
        <div class="mb-3">
            <h1 class="h5 mb-2">Cambio de contraseña requerido</h1>
            <p class="text-muted mb-0">
                Por seguridad debes crear una nueva contraseña antes de continuar al sistema.
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo e($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo e($success); ?></div>
        <?php endif; ?>

        <div class="alert alert-light border small">
            <strong>Reglas de contraseña</strong>
            <ul class="password-rule-list mt-2">
                <li>Minimo 12 caracteres.</li>
                <li>No compartas esta contraseña con otros usuarios.</li>
                <li>Evita usar datos obvios como nombre, empresa o telefono.</li>
            </ul>
        </div>

        <form method="post" action="<?php echo Uri::create('auth/force_password_change'); ?>">
            <?php echo \Form::csrf(); ?>

            <div class="form-group">
                <label>Nueva contraseña</label>
                <input type="password" name="password" class="form-control" autocomplete="new-password" required autofocus>
            </div>

            <div class="form-group">
                <label>Confirmar contraseña</label>
                <input type="password" name="password_confirm" class="form-control" autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Guardar nueva contraseña</button>
        </form>

        <div class="text-center mt-3">
            <a class="text-muted" href="<?php echo !empty($logout_url) ? e($logout_url) : Uri::create('logout'); ?>">Cancelar y cerrar sesion</a>
        </div>
    </div>
</div>
<?php echo Asset::js('js/jquery.min.js'); ?>
<?php echo Asset::js('js/bootstrap.bundle.min.js'); ?>
<?php echo Asset::js('js/adminlte.min.js'); ?>
</body>
</html>
