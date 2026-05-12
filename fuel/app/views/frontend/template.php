<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php
    $theme = !empty($theme) ? $theme : null;
    $theme_asset = function ($path) {
        if (empty($path)) {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        return Uri::base(false).ltrim($path, '/');
    };
    ?>
    <title><?php echo e(!empty($title) ? $title : 'Core-App'); ?></title>
    <?php if (!empty($seo_description)): ?>
    <meta name="description" content="<?php echo e($seo_description); ?>">
    <?php endif; ?>
    <?php if ($theme && !empty($theme->favicon_path)): ?>
    <link rel="icon" href="<?php echo e($theme_asset($theme->favicon_path)); ?>">
    <?php endif; ?>
    <?php echo Asset::css('bootstrap-icons.css'); ?>
    <style>
        :root {
            --core-ink: <?php echo e($theme ? $theme->color_text : '#172033'); ?>;
            --core-muted: <?php echo e($theme ? $theme->color_muted : '#657084'); ?>;
            --core-line: #dde3ea;
            --core-soft: <?php echo e($theme ? $theme->color_surface : '#f4f7fa'); ?>;
            --core-brand: <?php echo e($theme ? $theme->color_primary : '#0f766e'); ?>;
            --core-accent: <?php echo e($theme ? $theme->color_accent : '#b7791f'); ?>;
            --core-bg: <?php echo e($theme ? $theme->color_background : '#ffffff'); ?>;
            --core-secondary: <?php echo e($theme ? $theme->color_secondary : '#172033'); ?>;
            --core-font: <?php echo e($theme ? $theme->font_family : 'Arial, Helvetica, sans-serif'); ?>;
            --core-heading-font: <?php echo e($theme ? $theme->heading_font_family : 'Arial, Helvetica, sans-serif'); ?>;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: var(--core-ink);
            background: var(--core-bg);
            font-family: var(--core-font);
            line-height: 1.5;
        }
        h1, h2, h3, h4, h5, h6 { font-family: var(--core-heading-font); }
        a { color: inherit; text-decoration: none; }
        img { max-width: 100%; display: block; }
        .site-header {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(255, 255, 255, .96);
            border-bottom: 1px solid var(--core-line);
            backdrop-filter: blur(12px);
        }
        .site-shell {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
        }
        .site-nav {
            display: flex;
            min-height: 72px;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: 0;
        }
        .brand img {
            max-height: 44px;
            width: auto;
        }
        .brand span { color: var(--core-brand); }
        .menu {
            display: flex;
            align-items: center;
            gap: 18px;
            color: var(--core-muted);
            font-size: .95rem;
        }
        .menu a:hover { color: var(--core-brand); }
        .account-menu {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--core-muted);
            font-size: .92rem;
        }
        .account-menu a {
            border: 1px solid var(--core-line);
            border-radius: 6px;
            padding: 7px 10px;
        }
        .account-menu a.primary {
            border-color: var(--core-brand);
            background: var(--core-brand);
            color: #fff;
        }
        .site-main { min-height: 62vh; }
        .site-footer {
            margin-top: 54px;
            padding: 36px 0;
            background: #111827;
            color: #d8dee9;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 28px;
        }
        .footer-grid h3 {
            margin: 0 0 10px;
            font-size: 1rem;
            color: #fff;
        }
        .footer-grid p {
            margin: 0;
            color: #bcc6d3;
            font-size: .95rem;
        }
        @media (max-width: 720px) {
            .site-nav {
                align-items: flex-start;
                flex-direction: column;
                justify-content: center;
                padding: 14px 0;
            }
            .menu {
                flex-wrap: wrap;
                gap: 12px;
            }
        }
        <?php if ($theme && !empty($theme->custom_css)): ?>
        <?php echo $theme->custom_css; ?>
        <?php endif; ?>
    </style>
    <?php
    $public_url = function ($url) {
        if (empty($url) || $url === '/') {
            return Uri::base(false);
        }

        if (preg_match('/^https?:\/\//i', $url)) {
            return $url;
        }

        return Uri::base(false).ltrim($url, '/');
    };
    ?>
</head>
<body>
    <header class="site-header">
        <div class="site-shell">
            <nav class="site-nav" aria-label="Menu principal">
                <a class="brand" href="<?php echo Uri::base(false); ?>">
                    <?php if ($theme && !empty($theme->logo_path)): ?>
                    <img src="<?php echo e($theme_asset($theme->logo_path)); ?>" alt="Core-App">
                    <?php else: ?>
                    CORE-APP <span>ERP</span>
                    <?php endif; ?>
                </a>
                <?php if (!empty($menu_items)): ?>
                <div class="menu">
                    <?php foreach ($menu_items as $item): ?>
                    <a href="<?php echo e($public_url($item->url)); ?>"><?php echo e($item->label); ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="account-menu">
                    <?php $frontend_user = !empty($frontend_user) ? $frontend_user : ['logged_in' => false, 'name' => '']; ?>
                    <?php if (!empty($frontend_user['logged_in'])): ?>
                    <a href="<?php echo Uri::create('mi-cuenta'); ?>">Mi cuenta</a>
                    <a href="<?php echo Uri::create('salir-cuenta'); ?>">Salir</a>
                    <?php else: ?>
                    <a href="<?php echo Uri::create('acceso'); ?>">Entrar</a>
                    <a class="primary" href="<?php echo Uri::create('registro'); ?>">Registrarse</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <main class="site-main">
        <?php echo $content; ?>
    </main>

    <footer class="site-footer">
        <div class="site-shell footer-grid">
            <?php if (!empty($footer_columns)): ?>
                <?php foreach ($footer_columns as $column): ?>
                <section>
                    <h3><?php echo e($column->title); ?></h3>
                    <p><?php echo nl2br(e($column->content)); ?></p>
                </section>
                <?php endforeach; ?>
            <?php else: ?>
                <section>
                    <h3>Core-App ERP</h3>
                    <p>&copy; <?php echo date('Y'); ?> Core-App. Todos los derechos reservados.</p>
                </section>
            <?php endif; ?>
        </div>
    </footer>

    <?php echo !empty($cookie_banner) ? $cookie_banner : ''; ?>
</body>
</html>
