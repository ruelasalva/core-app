<?php
$media_url = function ($path) {
    if (empty($path)) {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }

    return Uri::base(false).ltrim($path, '/');
};

$no_image_svg = 'data:image/svg+xml;charset=UTF-8,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="720" height="720" viewBox="0 0 720 720"><rect width="720" height="720" fill="#eef3f7"/><path d="M150 486h420L448 322l-96 120-68-88-134 132z" fill="#cbd5e1"/><circle cx="254" cy="250" r="46" fill="#cbd5e1"/><text x="360" y="604" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="42" fill="#64748b">Sin imagen</text></svg>');

$category_url = function ($slug) {
    return Uri::create('categoria/'.$slug);
};

$tag_url = function ($slug) {
    return Uri::create('tag/'.$slug);
};

$inquiry_url = !empty($product['inquiry_url']) ? (string) $product['inquiry_url'] : Uri::create('pagina/contacto');
$inquiry_target = !empty($product['inquiry_target']) ? (string) $product['inquiry_target'] : '_self';
$conversion_settings = !empty($conversion_settings) && is_array($conversion_settings) ? $conversion_settings : array();
$whatsapp_configured = trim((string) \Arr::get($conversion_settings, 'whatsapp_url', '')) !== '';
$product_url = !empty($product['slug']) ? Uri::create('producto/'.$product['slug']) : '';
$contact_quote_url = Uri::create('pagina/contacto', array(), array(
    'producto' => \Arr::get($product, 'name', ''),
    'sku' => \Arr::get($product, 'sku', ''),
    'url' => $product_url,
));
$primary_inquiry_url = $inquiry_url;
$primary_inquiry_target = $inquiry_target;
$primary_inquiry_label = $whatsapp_configured ? 'Cotizar por WhatsApp' : 'Solicitar cotización';

if ($whatsapp_configured && class_exists('Helper_Core_Web')) {
    $whatsapp_product_url = Helper_Core_Web::whatsapp_url('product', array(
        'name' => \Arr::get($product, 'name', ''),
        'sku' => \Arr::get($product, 'sku', ''),
        'url' => $product_url,
    ));
    if ($whatsapp_product_url !== '') {
        $primary_inquiry_url = $whatsapp_product_url;
        $primary_inquiry_target = '_blank';
    }
} elseif (!$whatsapp_configured) {
    $primary_inquiry_url = $contact_quote_url;
    $primary_inquiry_target = '_self';
}

$normalize_public_text = function ($value) {
    return strtr((string) $value, array(
        'AtenciÃ³n' => 'Atención',
        'FacturaciÃ³n' => 'Facturación',
        'EnvÃ­o' => 'Envío',
        'tÃ©cnico' => 'técnico',
        'TÃ©cnico' => 'Técnico',
        'CatÃ¡logo' => 'Catálogo',
        'informaciÃ³n' => 'información',
    ));
};

$trust_badges = !empty($conversion_settings['trust_badges']) && is_array($conversion_settings['trust_badges'])
    ? $conversion_settings['trust_badges']
    : array(
        array('label' => 'Atención personalizada', 'icon' => 'bi bi-person-check'),
        array('label' => 'Facturación disponible', 'icon' => 'bi bi-receipt'),
        array('label' => 'Envío o entrega', 'icon' => 'bi bi-truck'),
        array('label' => 'Soporte técnico', 'icon' => 'bi bi-headset'),
    );
?>

<style>
    .product-shell {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }
    .product-detail {
        display: grid;
        grid-template-columns: minmax(280px, .95fr) minmax(0, 1.05fr);
        gap: 46px;
        padding: 54px 0;
        align-items: start;
    }
    .product-media {
        border: 1px solid #dde3ea;
        border-radius: 8px;
        overflow: hidden;
        background: #f4f7fa;
    }
    .product-media img {
        width: 100%;
        aspect-ratio: 1 / 1;
        object-fit: cover;
    }
    .product-thumbs {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(88px, 1fr));
        gap: 10px;
        margin-top: 12px;
    }
    .product-thumbs img {
        width: 100%;
        aspect-ratio: 1 / 1;
        object-fit: cover;
        border: 1px solid #dde3ea;
        border-radius: 6px;
        background: #f4f7fa;
    }
    .product-copy .eyebrow {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 14px;
        color: #0f766e;
        font-size: .86rem;
        font-weight: 800;
        text-transform: uppercase;
    }
    .product-copy h1 {
        margin: 0;
        font-size: clamp(2rem, 4vw, 3.8rem);
        line-height: 1;
        letter-spacing: 0;
    }
    .product-copy .short {
        margin: 18px 0 0;
        color: #657084;
        font-size: 1.08rem;
    }
    .product-price {
        margin: 24px 0;
        color: #172033;
        font-size: 1.8rem;
        font-weight: 900;
    }
    .product-login-price {
        margin: 24px 0;
        border: 1px solid #dde3ea;
        border-radius: 8px;
        background: #f8fafc;
        padding: 16px;
        color: #657084;
    }
    .product-login-price a {
        color: #0f766e;
        font-weight: 800;
    }
    .product-cart-form {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        margin: -8px 0 24px;
    }
    .product-cart-form input {
        width: 96px;
        min-height: 42px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 8px 10px;
        font: inherit;
    }
    .product-cart-form button {
        min-height: 42px;
        border: 1px solid #0f766e;
        border-radius: 6px;
        background: #0f766e;
        color: #fff;
        padding: 9px 16px;
        font-weight: 800;
        cursor: pointer;
        transition: transform .16s ease, opacity .16s ease;
    }
    .product-cart-form button.is-loading {
        opacity: .72;
        pointer-events: none;
    }
    .product-cart-form button.is-added {
        transform: scale(1.03);
    }
    .product-description {
        padding-top: 22px;
        border-top: 1px solid #dde3ea;
        color: #334155;
    }
    .product-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 22px;
    }
    .product-tags a {
        display: inline-flex;
        padding: 7px 10px;
        border: 1px solid #dde3ea;
        border-radius: 999px;
        color: #334155;
        font-size: .9rem;
    }
    .product-tags a:hover {
        border-color: #0f766e;
        color: #0f766e;
    }
    .related-section {
        padding: 10px 0 58px;
    }
    .related-section h2 {
        margin: 0 0 20px;
        font-size: clamp(1.5rem, 3vw, 2.25rem);
        line-height: 1.05;
    }
    .related-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 18px;
    }
    .related-card {
        border: 1px solid #dde3ea;
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
        transition: transform .18s ease, box-shadow .18s ease;
    }
    .related-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 34px rgba(15, 23, 42, .08);
    }
    .related-card img {
        width: 100%;
        aspect-ratio: 4 / 3;
        object-fit: cover;
        background: #eef3f7;
    }
    .related-card .body {
        padding: 14px;
    }
    .related-card h3 {
        margin: 0 0 8px;
        font-size: 1rem;
        line-height: 1.2;
    }
    .related-card p {
        margin: 0;
        color: #657084;
        font-size: .92rem;
    }
    @media (max-width: 860px) {
        .product-detail {
            grid-template-columns: 1fr;
        }
    }
</style>

<section class="product-shell product-detail front-hero--product" data-track-impression="product_view">
    <div>
        <div class="product-media<?php echo empty($product['main_image_path']) ? ' product-media--empty' : ''; ?>">
            <img src="<?php echo e(!empty($product['main_image_path']) ? $media_url($product['main_image_path']) : $no_image_svg); ?>" alt="<?php echo e($product['name']); ?>">
        </div>
        <?php if (!empty($images)): ?>
        <div class="product-thumbs">
            <?php foreach ($images as $image): ?>
            <img src="<?php echo e($media_url($image['image_path'])); ?>" alt="<?php echo e($image['alt_text'] ?: $product['name']); ?>">
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="product-copy">
        <div class="eyebrow">
            <?php if (!empty($product['category_name']) && !empty($product['category_slug'])): ?>
            <a href="<?php echo e($category_url($product['category_slug'])); ?>"><?php echo e($product['category_name']); ?></a>
            <?php endif; ?>
            <?php if (!empty($product['brand_name'])): ?>
            <span><?php echo e($product['brand_name']); ?></span>
            <?php endif; ?>
            <?php if (!empty($product['sku'])): ?>
            <span><?php echo e($product['sku']); ?></span>
            <?php endif; ?>
        </div>
        <div class="product-data-strip">
            <?php if (!empty($product['sku'])): ?><span><strong>SKU</strong><?php echo e($product['sku']); ?></span><?php endif; ?>
            <?php if (!empty($product['brand_name'])): ?><span><strong>Marca</strong><?php echo e($product['brand_name']); ?></span><?php endif; ?>
            <?php if (!empty($product['category_name'])): ?><span><strong>Categoría</strong><?php echo e($product['category_name']); ?></span><?php endif; ?>
            <?php if (!empty($product['subcategory_name'])): ?><span><strong>Compatibilidad</strong><?php echo e($product['subcategory_name']); ?></span><?php endif; ?>
        </div>

        <h1><?php echo e($product['name']); ?></h1>

        <?php if (!empty($product['short_description'])): ?>
        <p class="short"><?php echo e($product['short_description']); ?></p>
        <?php endif; ?>

        <?php if (!empty($product['can_view_price']) && (float) \Arr::get($product, 'price', 0) > 0): ?>
        <div class="product-price-stack">
            <div class="product-price-line">
                <span>Precio Lista</span>
                <strong><?php echo e($product['list_currency_code']); ?> <?php echo number_format((float) $product['list_price'], 2); ?></strong>
            </div>
            <?php if (!empty($product['has_customer_price'])): ?>
            <div class="product-price-line preferred">
                <span>Tu Precio</span>
                <strong><?php echo e($product['customer_currency_code']); ?> <?php echo number_format((float) $product['customer_price'], 2); ?></strong>
            </div>
            <?php endif; ?>
            <?php if (!empty($product['has_preferential_price'])): ?>
            <div class="product-savings">Ahorro: <?php echo e($product['list_currency_code']); ?> <?php echo number_format((float) $product['price_savings'], 2); ?></div>
            <?php endif; ?>
        </div>
        <?php echo Form::open(['action' => 'carrito/agregar', 'method' => 'post', 'class' => 'product-cart-form', 'data-cart-ajax' => '1']); ?>
            <?php echo Form::hidden('product_id', (int) $product['id']); ?>
            <label class="product-quote-quantity">Cantidad
                <?php echo Form::input('quantity', 1, ['type' => 'number', 'min' => '1', 'step' => '1', 'data-whatsapp-quantity' => '1']); ?>
            </label>
            <button type="submit">Agregar al carrito</button>
        <?php echo Form::close(); ?>
        <?php else: ?>
        <div class="product-login-price">
            Solicita precio y disponibilidad con un asesor comercial.
        </div>
        <?php endif; ?>

        <?php if (!empty($product['inquiry_enabled'])): ?>
        <div class="product-commercial-actions product-commercial-actions--detail">
            <?php if (empty($product['can_view_price']) || (float) \Arr::get($product, 'list_price', 0) <= 0): ?>
            <label class="product-quote-quantity">Cantidad
                <input type="number" min="1" step="1" value="1" data-whatsapp-quantity>
            </label>
            <?php endif; ?>
            <a class="product-inquiry-link <?php echo (!empty($product['can_view_price']) && (float) \Arr::get($product, 'price', 0) > 0) ? '' : 'product-inquiry-link--primary'; ?> product-inquiry-link--large" data-track-event="quote_click whatsapp_product" data-whatsapp-product-link href="<?php echo e($primary_inquiry_url); ?>" target="<?php echo e($primary_inquiry_target); ?>" rel="noopener noreferrer"><i class="<?php echo $whatsapp_configured ? 'bi bi-whatsapp' : 'bi bi-chat-dots'; ?>"></i> <?php echo e($primary_inquiry_label); ?></a>
            <?php if (empty($product['can_view_price'])): ?>
            <a class="product-secondary-link" href="<?php echo Uri::create('registro'); ?>">Crear cuenta empresarial</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="product-registration-benefits">
            Los clientes registrados pueden acceder a precios preferenciales, historial de cotizaciones, pedidos y portal de clientes.
        </div>

        <?php if (!empty($trust_badges)): ?>
        <div class="product-trust-badges" aria-label="Beneficios comerciales">
            <?php foreach ($trust_badges as $badge): ?>
            <?php $badge_label = trim($normalize_public_text(\Arr::get($badge, 'label', ''))); ?>
            <?php if ($badge_label === '') { continue; } ?>
            <div class="product-trust-badge">
                <i class="<?php echo e(\Arr::get($badge, 'icon', 'bi bi-patch-check')); ?>"></i>
                <span><?php echo e($badge_label); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="product-compatibility-help">
            <h2>¿No estás seguro si este producto es compatible?</h2>
            <p>Te ayudamos a validar modelo, impresora, consumible correcto o alternativa compatible antes de cotizar.</p>
            <div class="product-compatibility-actions">
                <?php if (!empty($product['inquiry_enabled'])): ?>
                <a class="product-inquiry-link product-inquiry-link--primary" data-track-event="quote_click whatsapp_product" href="<?php echo e($primary_inquiry_url); ?>" target="<?php echo e($primary_inquiry_target); ?>" rel="noopener noreferrer"><i class="<?php echo $whatsapp_configured ? 'bi bi-whatsapp' : 'bi bi-chat-dots'; ?>"></i> <?php echo e($whatsapp_configured ? 'Cotizar por WhatsApp' : 'Solicitar cotización'); ?></a>
                <?php endif; ?>
                <a class="product-secondary-link" href="<?php echo Uri::create('productos'); ?>">Ver catálogo</a>
            </div>
        </div>

        <?php if (!empty($product['description'])): ?>
        <div class="product-description">
            <?php echo nl2br(e($product['description'])); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($tags)): ?>
        <div class="product-tags">
            <?php foreach ($tags as $tag): ?>
            <a href="<?php echo e($tag_url($tag['slug'])); ?>"><?php echo e($tag['name']); ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($related_products)): ?>
<section class="product-shell related-section">
    <h2>Productos relacionados</h2>
    <div class="related-grid">
        <?php foreach ($related_products as $related): ?>
        <?php $related_has_purchasable_price = !empty($related['can_view_price']) && (float) \Arr::get($related, 'price', 0) > 0; ?>
        <article class="related-card">
            <a href="<?php echo e(Uri::create('producto/'.$related['slug'])); ?>">
                <img src="<?php echo e(!empty($related['main_image_path']) ? $media_url($related['main_image_path']) : $no_image_svg); ?>" alt="<?php echo e($related['name']); ?>">
            </a>
            <div class="body">
                <div class="product-card-meta">
                    <?php if (!empty($related['sku'])): ?><span>SKU: <?php echo e($related['sku']); ?></span><?php endif; ?>
                    <?php if (!empty($related['brand_name'])): ?><span><?php echo e($related['brand_name']); ?></span><?php endif; ?>
                </div>
                <h3><a href="<?php echo e(Uri::create('producto/'.$related['slug'])); ?>"><?php echo e($related['name']); ?></a></h3>
                <?php if (!empty($related['short_description'])): ?>
                <p><?php echo e($related['short_description']); ?></p>
                <?php endif; ?>
                <?php if (!empty($related['can_view_price']) && (float) \Arr::get($related, 'price', 0) > 0): ?>
                <div class="catalog-price-stack compact">
                    <div class="catalog-price-line"><span>Precio Lista</span><strong><?php echo e($related['list_currency_code']); ?> <?php echo number_format((float) $related['list_price'], 2); ?></strong></div>
                    <?php if (!empty($related['has_customer_price'])): ?>
                    <div class="catalog-price-line preferred"><span>Tu Precio</span><strong><?php echo e($related['customer_currency_code']); ?> <?php echo number_format((float) $related['customer_price'], 2); ?></strong></div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="product-quote-state">Solicita precio y disponibilidad con un asesor comercial.</div>
                <?php endif; ?>
                <div class="product-card-actions">
                    <?php if ($related_has_purchasable_price): ?>
                    <?php echo Form::open(['action' => 'carrito/agregar', 'method' => 'post', 'class' => 'product-card-cart-form', 'data-cart-ajax' => '1']); ?>
                        <?php echo Form::hidden('product_id', (int) $related['id']); ?>
                        <?php echo Form::hidden('quantity', 1); ?>
                        <button class="product-inquiry-link product-inquiry-link--primary" type="submit">Agregar al carrito</button>
                    <?php echo Form::close(); ?>
                    <a class="product-inquiry-link" data-track-event="quote_click whatsapp_product" href="<?php echo e(\Arr::get($related, 'inquiry_url', Uri::create('pagina/contacto', array(), array('producto' => \Arr::get($related, 'name', ''), 'sku' => \Arr::get($related, 'sku', ''))))); ?>" target="<?php echo e(\Arr::get($related, 'inquiry_target', '_self')); ?>" rel="noopener noreferrer">Cotizar por WhatsApp</a>
                    <?php else: ?>
                    <a class="product-inquiry-link product-inquiry-link--primary" data-track-event="quote_click whatsapp_product" href="<?php echo e(\Arr::get($related, 'inquiry_url', Uri::create('pagina/contacto', array(), array('producto' => \Arr::get($related, 'name', ''), 'sku' => \Arr::get($related, 'sku', ''))))); ?>" target="<?php echo e(\Arr::get($related, 'inquiry_target', '_self')); ?>" rel="noopener noreferrer">Cotizar por WhatsApp</a>
                    <?php endif; ?>
                    <a class="card-action secondary" href="<?php echo e(Uri::create('producto/'.$related['slug'])); ?>">Ver producto</a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>


