<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso <?php echo e($portal->name); ?> | Core-App</title>
    <?php echo Asset::css('bootstrap.min.css'); ?>
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f6f9;
        }
        .portal-login {
            width: 100%;
            max-width: 420px;
            border: 0;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .12);
        }
        .portal-accent {
            height: 5px;
            background: #0d6efd;
            border-radius: .375rem .375rem 0 0;
        }
    </style>
</head>
<body>
<div class="card portal-login">
    <div class="portal-accent"></div>
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <h1 class="h4 mb-1"><?php echo e($portal->name); ?></h1>
            <span class="text-muted small"><?php echo e($portal->description); ?></span>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 small"><?php echo e($error); ?></div>
        <?php endif; ?>

        <?php echo Form::open(['action' => $action, 'method' => 'post']); ?>
            <?php echo Form::csrf(); ?>
            <div class="mb-3">
                <label class="form-label small fw-bold">Usuario</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">Contrasena</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">Ingresar</button>
        <?php echo Form::close(); ?>
    </div>
</div>
<?php echo Asset::js('bootstrap.bundle.min.js'); ?>
</body>
</html>
