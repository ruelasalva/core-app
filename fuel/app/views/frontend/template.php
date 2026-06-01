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
    $site_name = !empty($site_name)
        ? (string) $site_name
        : (($theme && !empty($theme->site_name)) ? (string) $theme->site_name : 'Core-App');
    $page_title = !empty($title) ? (string) $title : $site_name;
    $title_suffix = ($theme && !empty($theme->seo_title_suffix)) ? (string) $theme->seo_title_suffix : $site_name;
    $full_title = $page_title;
    if ($title_suffix !== '' && strcasecmp($page_title, $title_suffix) !== 0 && stripos($page_title, $title_suffix) === false) {
        $full_title .= ' | '.$title_suffix;
    }
    $seo_description = !empty($seo_description)
        ? (string) $seo_description
        : (($theme && !empty($theme->default_seo_description)) ? (string) $theme->default_seo_description : '');
    $canonical_url = !empty($canonical_url) ? (string) $canonical_url : Uri::current();
    $robots = ($theme && !empty($theme->robots)) ? (string) $theme->robots : 'index,follow';
    $og_image = ($theme && !empty($theme->og_image_path)) ? $theme_asset($theme->og_image_path) : '';
    $layout_key = ($theme && !empty($theme->layout_key)) ? preg_replace('/[^a-z0-9_-]+/i', '-', (string) $theme->layout_key) : 'commerce_default';
    ?>
    <title><?php echo e($full_title); ?></title>
    <meta name="robots" content="<?php echo e($robots); ?>">
    <link rel="canonical" href="<?php echo e($canonical_url); ?>">
    <?php if (!empty($seo_description)): ?>
    <meta name="description" content="<?php echo e($seo_description); ?>">
    <?php endif; ?>
    <meta property="og:site_name" content="<?php echo e($site_name); ?>">
    <meta property="og:title" content="<?php echo e($full_title); ?>">
    <?php if (!empty($seo_description)): ?>
    <meta property="og:description" content="<?php echo e($seo_description); ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo e($canonical_url); ?>">
    <?php if (!empty($og_image)): ?>
    <meta property="og:image" content="<?php echo e($og_image); ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="<?php echo !empty($og_image) ? 'summary_large_image' : 'summary'; ?>">
    <meta name="twitter:title" content="<?php echo e($full_title); ?>">
    <?php if (!empty($seo_description)): ?>
    <meta name="twitter:description" content="<?php echo e($seo_description); ?>">
    <?php endif; ?>
    <?php if ($theme && !empty($theme->favicon_path)): ?>
    <link rel="icon" href="<?php echo e($theme_asset($theme->favicon_path)); ?>">
    <?php endif; ?>
    <?php echo Asset::css('bootstrap-icons.css'); ?>
    <?php echo Asset::css('all.min.css'); ?>
    <?php echo class_exists('Helper_Core_Web') ? Helper_Core_Web::frontend_head() : ''; ?>
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
        .cart-link.bump {
            animation: cartBump .35s ease;
        }
        .core-toast {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 10000;
            max-width: 340px;
            border: 1px solid var(--core-line);
            border-radius: 8px;
            background: #fff;
            color: var(--core-ink);
            box-shadow: 0 18px 42px rgba(15, 23, 42, .16);
            padding: 14px 16px;
            transform: translateY(16px);
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s ease, transform .2s ease;
        }
        .core-toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        .core-toast.error {
            border-color: #fecaca;
            background: #fff7f7;
            color: #991b1b;
        }
        @keyframes cartBump {
            0% { transform: scale(1); }
            45% { transform: scale(1.08); }
            100% { transform: scale(1); }
        }
        .site-main { min-height: 62vh; }
        .site-footer {
            margin-top: 54px;
            background: #0f172a;
            color: #d8dee9;
            border-top: 4px solid var(--core-brand);
        }
        .footer-top {
            padding: 44px 0 34px;
            background:
                linear-gradient(135deg, rgba(255,255,255,.05), rgba(255,255,255,0) 46%),
                #0f172a;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 1.25fr repeat(3, minmax(170px, 1fr));
            gap: 30px;
            align-items: start;
        }
        .footer-grid h3 {
            margin: 0 0 10px;
            font-size: .98rem;
            color: #fff;
        }
        .footer-grid p,
        .footer-rich {
            color: #bcc6d3;
            font-size: .95rem;
        }
        .footer-rich p { margin: 0 0 8px; }
        .footer-rich a,
        .footer-list a,
        .footer-contact a {
            color: #e8eef8;
        }
        .footer-rich a:hover,
        .footer-list a:hover,
        .footer-contact a:hover { color: #fff; }
        .footer-list {
            display: grid;
            gap: 8px;
            margin: 0;
            padding: 0;
            list-style: none;
            color: #bcc6d3;
            font-size: .95rem;
        }
        .footer-contact {
            display: grid;
            gap: 10px;
            color: #bcc6d3;
            font-size: .95rem;
        }
        .footer-contact-item {
            display: flex;
            gap: 9px;
            align-items: flex-start;
        }
        .footer-contact-item i { color: var(--core-accent); margin-top: 3px; }
        .footer-social {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .footer-social a {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 999px;
            color: #fff;
            background: rgba(255,255,255,.06);
        }
        .footer-social a:hover {
            background: var(--core-brand);
            border-color: var(--core-brand);
        }
        .footer-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .footer-badge {
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 8px;
            padding: 8px 10px;
            color: #e8eef8;
            background: rgba(255,255,255,.05);
            font-size: .86rem;
        }
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,.10);
            padding: 16px 0;
            color: #94a3b8;
            font-size: .9rem;
        }
        .footer-bottom .site-shell {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
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
            .footer-grid {
                grid-template-columns: 1fr;
            }
        }
        .layout-commerce_default .front-hero:after {
            background: linear-gradient(0deg, rgba(10, 17, 28, .78), rgba(10, 17, 28, .15));
        }
        .layout-corporate .site-header {
            background: rgba(255, 255, 255, .98);
            box-shadow: 0 10px 30px rgba(15, 23, 42, .06);
        }
        .layout-corporate .site-nav {
            min-height: 82px;
        }
        .layout-corporate .menu a {
            color: var(--core-ink);
            font-weight: 700;
        }
        .layout-corporate .front-hero {
            min-height: 520px;
            background: var(--core-secondary);
        }
        .layout-corporate .front-hero:after {
            background: linear-gradient(90deg, rgba(15, 23, 42, .86), rgba(15, 23, 42, .38) 56%, rgba(15, 23, 42, .12));
        }
        .layout-corporate .front-hero-content {
            padding: 96px 0 86px;
        }
        .layout-corporate .section-band {
            padding: 72px 0;
        }
        .layout-corporate .section-media,
        .layout-corporate .contact-card,
        .layout-corporate .product-card,
        .layout-corporate .catalog-card {
            box-shadow: 0 18px 42px rgba(15, 23, 42, .08);
        }
        .layout-catalog_dense .site-nav {
            min-height: 58px;
        }
        .layout-catalog_dense .brand img {
            max-height: 34px;
        }
        .layout-catalog_dense .menu,
        .layout-catalog_dense .account-menu {
            font-size: .88rem;
            gap: 10px;
        }
        .layout-catalog_dense .front-hero,
        .layout-catalog_dense .catalog-hero {
            min-height: auto;
            padding: 42px 0 30px;
        }
        .layout-catalog_dense .front-hero-content {
            padding: 46px 0 40px;
        }
        .layout-catalog_dense .front-hero h1,
        .layout-catalog_dense .catalog-hero h1 {
            font-size: clamp(1.9rem, 3.4vw, 3.1rem);
        }
        .layout-catalog_dense .section-band,
        .layout-catalog_dense .catalog-section {
            padding: 34px 0;
        }
        .layout-catalog_dense .product-grid,
        .layout-catalog_dense .catalog-grid {
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 12px;
        }
        .layout-catalog_dense .product-card .body,
        .layout-catalog_dense .catalog-card .body,
        .layout-catalog_dense .feature-item,
        .layout-catalog_dense .download-item {
            padding: 12px;
        }
        .layout-editorial_showcase .site-header {
            position: relative;
            background: #fff;
        }
        .layout-editorial_showcase .site-nav {
            min-height: 92px;
            border-bottom: 1px solid var(--core-line);
        }
        .layout-editorial_showcase .brand {
            font-size: 1.25rem;
            color: var(--core-secondary);
        }
        .layout-editorial_showcase .front-hero {
            min-height: 610px;
            align-items: center;
            background: var(--core-soft);
            color: var(--core-ink);
        }
        .layout-editorial_showcase .front-hero img {
            opacity: .28;
            filter: saturate(.9);
        }
        .layout-editorial_showcase .front-hero:after {
            background: linear-gradient(90deg, rgba(255,255,255,.94), rgba(255,255,255,.76) 48%, rgba(255,255,255,.12));
        }
        .layout-editorial_showcase .front-hero p {
            color: var(--core-muted);
            font-size: 1.18rem;
        }
        .layout-editorial_showcase .front-hero .button,
        .layout-editorial_showcase .section-link {
            background: var(--core-ink);
        }
        .layout-editorial_showcase .section-band {
            padding: 86px 0;
        }
        .layout-editorial_showcase .section-copy h2 {
            font-size: clamp(2rem, 4vw, 3.4rem);
        }
        .layout-editorial_showcase .brand-grid,
        .layout-editorial_showcase .feature-grid,
        .layout-editorial_showcase .download-grid {
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }
        .layout-industrial_b2b {
            background: #f8fafc;
        }
        .layout-industrial_b2b .site-header {
            background: var(--core-secondary);
            border-bottom: 0;
        }
        .layout-industrial_b2b .brand,
        .layout-industrial_b2b .menu,
        .layout-industrial_b2b .account-menu {
            color: #e8eef8;
        }
        .layout-industrial_b2b .account-menu a {
            border-color: rgba(255,255,255,.18);
            color: #e8eef8;
        }
        .layout-industrial_b2b .account-menu a.primary {
            background: var(--core-accent);
            border-color: var(--core-accent);
            color: #111827;
        }
        .layout-industrial_b2b .front-hero {
            min-height: 500px;
            background: #111827;
        }
        .layout-industrial_b2b .front-hero:after {
            background: linear-gradient(90deg, rgba(17, 24, 39, .92), rgba(17, 24, 39, .58));
        }
        .layout-industrial_b2b .front-hero .button,
        .layout-industrial_b2b .section-link,
        .layout-industrial_b2b .contact-form button {
            background: var(--core-accent);
            border-color: var(--core-accent);
            color: #111827;
        }
        .layout-industrial_b2b .section-band {
            background: #fff;
        }
        .layout-industrial_b2b .section-band:nth-of-type(even) {
            background: #f1f5f9;
        }
        .layout-industrial_b2b .feature-item,
        .layout-industrial_b2b .download-item,
        .layout-industrial_b2b .brand-item,
        .layout-industrial_b2b .product-card,
        .layout-industrial_b2b .catalog-card,
        .layout-industrial_b2b .contact-card {
            border-radius: 4px;
            border-color: #cbd5e1;
        }
    </style>
    <?php echo Asset::css('frontend-public.css'); ?>
    <?php if ($theme && !empty($theme->custom_css)): ?>
    <style>
        <?php echo $theme->custom_css; ?>
    </style>
    <?php endif; ?>
    <?php
    $public_url = function ($url) {
        if (empty($url) || $url === '/') {
            return Uri::base(false);
        }

        if ($url === '#' || preg_match('/^[a-z][a-z0-9+.-]*:/i', $url)) {
            return $url;
        }

        return Uri::base(false).ltrim($url, '/');
    };
    $footer_settings = function ($column) {
        return (!empty($column->settings) && is_array($column->settings)) ? $column->settings : array();
    };
    $footer_items = function ($column) use ($footer_settings) {
        $settings = $footer_settings($column);
        return !empty($settings['items']) && is_array($settings['items']) ? $settings['items'] : array();
    };
    $footer_icon = function ($icon, $fallback = 'bi bi-chevron-right') {
        $icon = trim((string) $icon);
        return $icon !== '' ? $icon : $fallback;
    };
    $footer_safe_html = function ($html) {
        $html = html_entity_decode((string) $html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = preg_replace('/<\s*script[^>]*>.*?<\s*\/\s*script\s*>/is', '', $html);
        $html = preg_replace('/<\s*iframe[^>]*>.*?<\s*\/\s*iframe\s*>/is', '', $html);
        $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/javascript\s*:/i', '', $html);
        return strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><a><span>');
    };
    ?>
</head>
<body class="frontend-layout layout-<?php echo e($layout_key); ?>">
    <?php echo class_exists('Helper_Core_Web') ? Helper_Core_Web::frontend_body_end() : ''; ?>
    <header class="site-header">
        <div class="site-shell">
            <nav class="site-nav" aria-label="Menu principal">
                <a class="brand" href="<?php echo Uri::base(false); ?>">
                    <?php if ($theme && !empty($theme->logo_path)): ?>
                    <img src="<?php echo e($theme_asset($theme->logo_path)); ?>" alt="<?php echo e($site_name); ?>">
                    <?php else: ?>
                    <span><?php echo e($site_name); ?></span>
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
                    <a class="site-cta primary" href="<?php echo Uri::create('pagina/contacto'); ?>"><i class="bi bi-chat-dots"></i> Solicitar información</a>
                    <?php $cart_count = isset($cart_count) ? (int) $cart_count : 0; ?>
                    <a class="cart-link" data-cart-link href="<?php echo Uri::create('carrito'); ?>"><i class="fas fa-shopping-cart"></i> Carrito<?php echo $cart_count > 0 ? ' ('.$cart_count.')' : ''; ?></a>
                    <?php $frontend_user = !empty($frontend_user) ? $frontend_user : ['logged_in' => false, 'name' => '']; ?>
                    <?php if (!empty($frontend_user['logged_in'])): ?>
                    <a href="<?php echo Uri::create('mi-cuenta'); ?>"><i class="fas fa-user-circle"></i> Mi cuenta</a>
                    <a href="<?php echo Uri::create('salir-cuenta'); ?>"><i class="fas fa-sign-out-alt"></i> Salir</a>
                    <?php else: ?>
                    <a href="<?php echo Uri::create('acceso'); ?>"><i class="fas fa-sign-in-alt"></i> Entrar</a>
                    <a class="primary" href="<?php echo Uri::create('registro'); ?>"><i class="fas fa-user-plus"></i> Registrarse</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <main class="site-main">
        <?php echo $content; ?>
    </main>

    <footer class="site-footer">
        <div class="footer-top">
        <div class="site-shell footer-grid">
            <?php if (!empty($footer_columns)): ?>
                <?php foreach ($footer_columns as $column): ?>
                <?php $type = !empty($column->column_type) ? (string) $column->column_type : 'text'; ?>
                <section>
                    <h3><?php echo e($column->title); ?></h3>
                    <?php if ($type === 'links' || $type === 'legal'): ?>
                        <ul class="footer-list">
                            <?php foreach ($footer_items($column) as $item): ?>
                            <?php if (empty($item['label'])) continue; ?>
                            <li><a href="<?php echo e($public_url(\Arr::get($item, 'url', '#'))); ?>"><?php echo e($item['label']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php elseif ($type === 'contact'): ?>
                        <div class="footer-contact">
                            <?php foreach ($footer_items($column) as $item): ?>
                            <?php if (empty($item['label'])) continue; ?>
                            <div class="footer-contact-item">
                                <i class="<?php echo e($footer_icon(\Arr::get($item, 'icon', ''), 'bi bi-info-circle')); ?>"></i>
                                <?php if (!empty($item['url'])): ?>
                                <a href="<?php echo e($public_url($item['url'])); ?>"><?php echo e($item['label']); ?></a>
                                <?php else: ?>
                                <span><?php echo e($item['label']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                            <?php if (!empty($column->content)): ?><div class="footer-rich"><?php echo $footer_safe_html($column->content); ?></div><?php endif; ?>
                        </div>
                    <?php elseif ($type === 'social'): ?>
                        <div class="footer-social">
                            <?php foreach ($footer_items($column) as $item): ?>
                            <?php if (empty($item['url'])) continue; ?>
                            <a href="<?php echo e($public_url($item['url'])); ?>" target="_blank" rel="noopener" aria-label="<?php echo e(\Arr::get($item, 'label', 'Red social')); ?>">
                                <i class="<?php echo e($footer_icon(\Arr::get($item, 'icon', ''), 'bi bi-share')); ?>"></i>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php if (!empty($column->content)): ?><div class="footer-rich" style="margin-top: 12px;"><?php echo $footer_safe_html($column->content); ?></div><?php endif; ?>
                    <?php elseif ($type === 'badges'): ?>
                        <div class="footer-badges">
                            <?php foreach ($footer_items($column) as $item): ?>
                            <?php if (empty($item['label'])) continue; ?>
                            <span class="footer-badge"><i class="<?php echo e($footer_icon(\Arr::get($item, 'icon', ''), 'bi bi-patch-check')); ?>"></i> <?php echo e($item['label']); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php if (!empty($column->content)): ?><div class="footer-rich" style="margin-top: 12px;"><?php echo $footer_safe_html($column->content); ?></div><?php endif; ?>
                    <?php else: ?>
                        <div class="footer-rich"><?php echo $footer_safe_html($column->content); ?></div>
                    <?php endif; ?>
                </section>
                <?php endforeach; ?>
            <?php else: ?>
                <section>
                    <h3>Core-App ERP</h3>
                    <p>&copy; <?php echo date('Y'); ?> Core-App. Todos los derechos reservados.</p>
                </section>
            <?php endif; ?>
        </div>
        </div>
        <div class="footer-bottom">
            <div class="site-shell">
                <span>&copy; <?php echo date('Y'); ?> <?php echo e($site_name); ?>. Todos los derechos reservados.</span>
                <span>Sitio administrable desde Core-App.</span>
            </div>
        </div>
    </footer>

    <div class="core-toast" data-core-toast></div>
    <?php echo !empty($cookie_banner) ? $cookie_banner : ''; ?>
    <?php echo !empty($frontend_extra_scripts) ? $frontend_extra_scripts : ''; ?>
    <script>
    (function() {
        var toast = document.querySelector('[data-core-toast]');
        var cartLink = document.querySelector('[data-cart-link]');
        var toastTimer = null;

        function showToast(message, type) {
            if (!toast) return;
            toast.textContent = message || '';
            toast.classList.toggle('error', type === 'error');
            toast.classList.add('show');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(function() {
                toast.classList.remove('show');
            }, 3200);
        }

        function updateCartCount(count) {
            if (!cartLink) return;
            count = parseInt(count || 0, 10);
            cartLink.innerHTML = '<i class="fas fa-shopping-cart"></i> ' + (count > 0 ? 'Carrito (' + count + ')' : 'Carrito');
            cartLink.classList.remove('bump');
            void cartLink.offsetWidth;
            cartLink.classList.add('bump');
        }

        document.querySelectorAll('[data-cart-ajax]').forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!window.fetch || !window.FormData) return;
                event.preventDefault();

                var button = form.querySelector('button[type="submit"]');
                var originalText = button ? button.textContent : '';
                if (button) {
                    button.classList.add('is-loading');
                    button.textContent = 'Agregando...';
                }

                fetch(form.getAttribute('action'), {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: new FormData(form),
                    credentials: 'same-origin'
                })
                    .then(function(response) {
                        return response.json().then(function(data) {
                            data.http_ok = response.ok;
                            return data;
                        });
                    })
                    .then(function(data) {
                        if (!data.http_ok || data.error) {
                            showToast(data.error || 'No se pudo agregar el producto.', 'error');
                            if (data.redirect) {
                                setTimeout(function() { window.location.href = data.redirect; }, 900);
                            }
                            return;
                        }
                        updateCartCount(data.cart_count);
                        showToast(data.message || 'Producto agregado al carrito.', 'success');
                        if (button) {
                            button.classList.add('is-added');
                            setTimeout(function() { button.classList.remove('is-added'); }, 400);
                        }
                    })
                    .catch(function() {
                        showToast('No se pudo agregar el producto. Intenta nuevamente.', 'error');
                    })
                    .finally(function() {
                        if (button) {
                            button.classList.remove('is-loading');
                            button.textContent = originalText;
                        }
                    });
            });
        });
    })();
    </script>
</body>
</html>
