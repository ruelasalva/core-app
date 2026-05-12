<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e(isset($title) ? $title : $portal_name); ?> | Core-App</title>
    <?php echo Asset::css('adminlte.min.css'); ?>
    <?php echo Asset::css('bootstrap-icons.css'); ?>
    <style>
        :root {
            --portal-primary: <?php echo $branding ? e($branding->primary_color) : '#0d6efd'; ?>;
            --portal-secondary: <?php echo $branding ? e($branding->secondary_color) : '#343a40'; ?>;
        }
        .portal-header { border-top: 4px solid var(--portal-primary); }
        .portal-title { color: var(--portal-primary); }
        <?php echo $branding ? $branding->custom_css : ''; ?>
    </style>
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand-md navbar-light navbar-white portal-header">
        <div class="container">
            <a href="<?php echo Uri::create($portal_code); ?>" class="navbar-brand">
                <span class="brand-text font-weight-bold portal-title">
                    <?php echo e($branding && $branding->display_name ? $branding->display_name : $portal_name); ?>
                </span>
            </a>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <span class="nav-link"><?php echo e($user_name); ?></span>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="<?php echo Uri::create($portal_code.'/logout'); ?>">Salir</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="content-wrapper">
        <div class="content pt-4">
            <div class="container">
                <?php echo $content; ?>
            </div>
        </div>
    </div>
</div>
<?php echo Asset::js('jquery.min.js'); ?>
<?php echo Asset::js('bootstrap.bundle.min.js'); ?>
<?php echo Asset::js('adminlte.min.js'); ?>
</body>
</html>
