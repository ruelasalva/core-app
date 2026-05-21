<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Alta de proveedor | Core-App</title>
    <link rel="stylesheet" href="<?php echo Uri::base(false); ?>assets/adminlte/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo Uri::base(false); ?>assets/adminlte/dist/css/adminlte.min.css">
    <style>
        body { background: #f4f6f9; }
        .supplier-card { max-width: 920px; margin: 36px auto; }
        .accent { height: 4px; background: #0d6efd; }
    </style>
</head>
<body>
    <div class="card supplier-card">
        <div class="accent"></div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h1 class="h4 mb-1">Alta de proveedor</h1>
                    <p class="text-muted mb-0">Registra tus datos fiscales y comerciales para iniciar el proceso de validacion.</p>
                </div>
                <a href="<?php echo Uri::create('proveedores/login'); ?>" class="btn btn-outline-secondary btn-sm">Ya tengo acceso</a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo e($error); ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo e($success); ?></div>
            <?php endif; ?>

            <?php echo Form::open(['action' => $action, 'method' => 'post']); ?>
                <?php echo Form::csrf(); ?>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Nombre comercial</label>
                        <input class="form-control" name="name" value="<?php echo e(\Arr::get($values, 'name', '')); ?>" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Razon social</label>
                        <input class="form-control" name="legal_name" value="<?php echo e(\Arr::get($values, 'legal_name', '')); ?>" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>RFC</label>
                        <input class="form-control text-uppercase" name="rfc" value="<?php echo e(\Arr::get($values, 'rfc', '')); ?>" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Regimen fiscal</label>
                        <select class="form-control" name="sat_tax_regime_code">
                            <option value="">Selecciona regimen</option>
                            <?php foreach (($sat_tax_regimes ?? []) as $option): ?>
                                <option value="<?php echo e($option['value']); ?>" <?php echo \Arr::get($values, 'sat_tax_regime_code', '') === $option['value'] ? 'selected' : ''; ?>>
                                    <?php echo e($option['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Giro / servicio</label>
                        <input class="form-control" name="business_line" value="<?php echo e(\Arr::get($values, 'business_line', '')); ?>">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Correo de contacto</label>
                        <input class="form-control" type="email" name="email" value="<?php echo e(\Arr::get($values, 'email', '')); ?>" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Telefono</label>
                        <input class="form-control" name="phone" value="<?php echo e(\Arr::get($values, 'phone', '')); ?>">
                    </div>
                    <div class="col-md-12 form-group">
                        <label>Notas comerciales</label>
                        <textarea class="form-control" rows="4" name="notes"><?php echo e(\Arr::get($values, 'notes', '')); ?></textarea>
                    </div>
                </div>
                <button class="btn btn-primary">Enviar solicitud</button>
            <?php echo Form::close(); ?>
        </div>
    </div>
</body>
</html>
