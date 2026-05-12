<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso | Core-App</title>
    <?php echo Asset::css('bootstrap.min.css'); ?>
    <style>
        body { background-color: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card-login { width: 100%; max-width: 400px; padding: 20px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="card card-login bg-white">
    <div class="text-center mb-4">
        <h2 class="fw-bold text-primary">Core-App</h2>
        <span class="text-muted">Panel Administrativo</span>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php echo Form::open(['action' => 'login', 'method' => 'post']); ?>
        <?php echo Form::csrf(); ?>
        <div class="mb-3">
            <label class="form-label small fw-bold">Usuario</label>
            <input type="text" name="username" class="form-control" required autofocus>
        </div>
        <div class="mb-4">
            <label class="form-label small fw-bold">Contraseña</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2">Ingresar</button>
    <?php echo Form::close(); ?>
</div>

<?php echo Asset::js('bootstrap.bundle.min.js'); ?>
</body>
</html>
