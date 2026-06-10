<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cambio de contrasena requerido</title>
    <?php echo Asset::css('css/bootstrap.min.css'); ?>
    <?php echo Asset::css('css/adminlte.min.css'); ?>
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <strong>CORE-APP</strong>
        </div>
        <div class="card-body">
            <p class="login-box-msg">Debes cambiar tu contrasena antes de continuar.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="post" action="<?php echo Uri::create('auth/force_password_change'); ?>">
                <?php echo \Form::csrf(); ?>

                <div class="form-group">
                    <label>Nueva contrasena</label>
                    <input type="password" name="password" class="form-control" autocomplete="new-password" required>
                    <small class="form-text text-muted">Minimo 12 caracteres.</small>
                </div>

                <div class="form-group">
                    <label>Confirmar contrasena</label>
                    <input type="password" name="password_confirm" class="form-control" autocomplete="new-password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Cambiar contrasena</button>
            </form>

            <div class="text-center mt-3">
                <a href="<?php echo Uri::create('logout'); ?>">Cerrar sesion</a>
            </div>
        </div>
    </div>
</div>
<?php echo Asset::js('js/jquery.min.js'); ?>
<?php echo Asset::js('js/bootstrap.bundle.min.js'); ?>
<?php echo Asset::js('js/adminlte.min.js'); ?>
</body>
</html>
