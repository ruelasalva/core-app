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

$no_image_svg = 'data:image/svg+xml;charset=UTF-8,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="640" height="480" viewBox="0 0 640 480"><rect width="640" height="480" fill="#eef3f7"/><path d="M160 330h320l-92-122-76 94-48-62-104 90z" fill="#cbd5e1"/><circle cx="230" cy="174" r="38" fill="#cbd5e1"/><text x="320" y="406" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="34" fill="#64748b">Sin imagen</text></svg>');

$product_url = function ($slug) {
    return Uri::create('producto/'.$slug);
};

$category_url = function ($slug) {
    return Uri::create('categoria/'.$slug);
};

$product_contact_url = function ($product) {
    return Uri::create('pagina/contacto', array(), array(
        'producto' => \Arr::get($product, 'name', ''),
        'sku' => \Arr::get($product, 'sku', ''),
        'url' => Uri::create('producto/'.\Arr::get($product, 'slug', '')),
    ));
};

$filters = !empty($filters) ? $filters : array(
    'q' => '',
    'category_id' => 0,
    'subcategory_id' => 0,
    'brand_id' => 0,
    'featured' => 0,
    'sort' => 'relevance',
);
$options = !empty($options) ? $options : array(
    'categories' => array(),
    'subcategories' => array(),
    'brands' => array(),
    'sorts' => array(),
);
$scope = !empty($scope) ? $scope : null;
$catalog_action = Uri::create(Uri::string());
$display_title = str_ireplace('Catalogo', 'Catálogo', (string) $title);
$conversion_settings = !empty($conversion_settings) && is_array($conversion_settings) ? $conversion_settings : array();
$empty_help_url = trim((string) \Arr::get($conversion_settings, 'whatsapp_url', ''));
$empty_help_target = '_blank';
if ($empty_help_url === '') {
    $empty_help_url = Uri::create('pagina/contacto');
    $empty_help_target = '_self';
}
$active_filter_chips = array();
if (!empty($filters['q'])) {
    $active_filter_chips[] = 'Búsqueda: '.$filters['q'];
}
if (!empty($filters['featured'])) {
    $active_filter_chips[] = 'Solo destacados';
}
foreach (array('category_id' => 'Categoría', 'subcategory_id' => 'Subcategoría', 'brand_id' => 'Marca') as $filter_key => $filter_label) {
    if (empty($filters[$filter_key])) {
        continue;
    }
    $option_key = $filter_key === 'brand_id' ? 'brands' : ($filter_key === 'subcategory_id' ? 'subcategories' : 'categories');
    foreach ($options[$option_key] as $option) {
        if ((int) $option['value'] === (int) $filters[$filter_key]) {
            $active_filter_chips[] = $filter_label.': '.$option['label'];
            break;
        }
    }
}
?>

<style>
    .catalog-hero {
        padding: 64px 0 44px;
        background: #f4f7fa;
        border-bottom: 1px solid #dde3ea;
    }
    .catalog-shell {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }
    .catalog-hero h1 {
        margin: 0;
        font-size: clamp(2rem, 4vw, 3.6rem);
        line-height: 1;
        letter-spacing: 0;
    }
    .catalog-hero p {
        max-width: 720px;
        margin: 14px 0 0;
        color: #657084;
        font-size: 1.05rem;
    }
    .catalog-section {
        padding: 42px 0 10px;
    }
    .catalog-layout {
        display: grid;
        grid-template-columns: minmax(240px, 286px) minmax(0, 1fr);
        gap: 24px;
        align-items: start;
    }
    .catalog-filters {
        border: 1px solid #dde3ea;
        border-radius: 8px;
        background: #fff;
        padding: 18px;
        position: sticky;
        top: 18px;
    }
    .catalog-filters h2 {
        margin: 0 0 14px;
        font-size: 1rem;
        color: #172033;
    }
    .filter-field {
        margin-bottom: 14px;
    }
    .filter-field label,
    .filter-check label {
        display: block;
        margin-bottom: 6px;
        color: #344052;
        font-size: .88rem;
        font-weight: 700;
    }
    .filter-field input,
    .filter-field select {
        width: 100%;
        min-height: 40px;
        border: 1px solid #cfd8e3;
        border-radius: 6px;
        padding: 8px 10px;
        color: #172033;
        background: #fff;
    }
    .filter-check {
        display: flex;
        gap: 8px;
        align-items: center;
        margin: 2px 0 16px;
    }
    .filter-check input {
        width: 16px;
        height: 16px;
    }
    .filter-check label {
        margin: 0;
        font-weight: 700;
    }
    .filter-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .filter-actions button,
    .filter-actions a {
        min-height: 40px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        text-decoration: none;
        border: 1px solid #0f766e;
        cursor: pointer;
    }
    .filter-actions button {
        background: #0f766e;
        color: #fff;
    }
    .filter-actions a {
        color: #0f766e;
        background: #fff;
    }
    .catalog-results-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 18px;
        color: #657084;
        font-size: .94rem;
    }
    .catalog-results-bar strong {
        color: #172033;
    }
    .catalog-active-search {
        color: #0f766e;
        font-weight: 800;
    }
    .catalog-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 18px;
    }
    .catalog-card {
        min-height: 100%;
        border: 1px solid #dde3ea;
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
        transition: transform .18s ease, box-shadow .18s ease;
    }
    .catalog-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 34px rgba(15, 23, 42, .08);
    }
    .catalog-card img {
        width: 100%;
        aspect-ratio: 4 / 3;
        object-fit: cover;
        background: #eef3f7;
    }
    .catalog-card .body {
        padding: 16px;
    }
    .catalog-card h2 {
        margin: 0 0 8px;
        font-size: 1.08rem;
        line-height: 1.2;
    }
    .catalog-card p {
        margin: 0;
        color: #657084;
        font-size: .94rem;
    }
    .catalog-meta {
        margin-bottom: 10px;
        color: #0f766e;
        font-size: .83rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .catalog-submeta {
        margin: -4px 0 10px;
        color: #657084;
        font-size: .84rem;
    }
    .catalog-price {
        margin-top: 14px;
        font-weight: 800;
        color: #172033;
    }
    .catalog-login-price {
        margin-top: 14px;
        color: #657084;
        font-size: .92rem;
    }
    .catalog-login-price a {
        color: #0f766e;
        font-weight: 800;
    }
    .empty-state {
        padding: 34px;
        border: 1px dashed #b8c4d1;
        border-radius: 8px;
        color: #657084;
        background: #f8fafc;
    }
    @media (max-width: 820px) {
        .catalog-layout {
            grid-template-columns: 1fr;
        }
        .catalog-filters {
            position: static;
        }
        .catalog-results-bar {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>

<section class="catalog-hero front-hero--catalog">
    <div class="catalog-shell">
        <h1><?php echo e($display_title); ?></h1>
        <?php if (!empty($description)): ?>
        <p><?php echo e($description); ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="catalog-section">
    <div class="catalog-shell">
        <div class="catalog-layout">
            <aside class="catalog-filters">
                <details class="catalog-filter-panel" open>
                <summary><i class="bi bi-sliders"></i> Filtrar catálogo</summary>
                <form method="get" action="<?php echo e($catalog_action); ?>" data-track-form="catalog_search">
                    <div class="filter-field catalog-search-field">
                        <label for="catalog-q">Buscar</label>
                        <input id="catalog-q" type="search" name="q" value="<?php echo e($filters['q']); ?>" placeholder="Busca por producto, SKU, modelo o marca">
                    </div>

                    <?php if ($scope !== 'category'): ?>
                    <div class="filter-field">
                        <label for="catalog-category">Categoría</label>
                        <select id="catalog-category" name="category_id">
                            <option value="">Todas</option>
                            <?php foreach ($options['categories'] as $option): ?>
                            <option value="<?php echo e($option['value']); ?>" <?php echo ((int) $filters['category_id'] === (int) $option['value']) ? 'selected' : ''; ?>>
                                <?php echo e($option['label']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="filter-field">
                        <label for="catalog-subcategory">Subcategoría</label>
                        <select id="catalog-subcategory" name="subcategory_id">
                            <option value="">Todas</option>
                            <?php foreach ($options['subcategories'] as $option): ?>
                            <option value="<?php echo e($option['value']); ?>" <?php echo ((int) $filters['subcategory_id'] === (int) $option['value']) ? 'selected' : ''; ?>>
                                <?php echo e($option['label']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-field">
                        <label for="catalog-brand">Marca</label>
                        <select id="catalog-brand" name="brand_id">
                            <option value="">Todas</option>
                            <?php foreach ($options['brands'] as $option): ?>
                            <option value="<?php echo e($option['value']); ?>" <?php echo ((int) $filters['brand_id'] === (int) $option['value']) ? 'selected' : ''; ?>>
                                <?php echo e($option['label']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-check">
                        <input id="catalog-featured" type="checkbox" name="featured" value="1" <?php echo !empty($filters['featured']) ? 'checked' : ''; ?>>
                        <label for="catalog-featured">Solo destacados</label>
                    </div>

                    <div class="filter-field">
                        <label for="catalog-sort">Ordenar</label>
                        <select id="catalog-sort" name="sort">
                            <?php foreach ($options['sorts'] as $option): ?>
                            <option value="<?php echo e($option['value']); ?>" <?php echo ($filters['sort'] === $option['value']) ? 'selected' : ''; ?>>
                                <?php echo e($option['label']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button type="submit">Filtrar</button>
                        <a href="<?php echo e($catalog_action); ?>">Limpiar</a>
                    </div>
                </form>
                </details>
            </aside>

            <div class="catalog-results">
                <div class="catalog-results-bar">
                    <strong><?php echo count($products); ?> productos encontrados</strong>
                    <?php if (!empty($filters['q'])): ?>
                    <span>Búsqueda: <span class="catalog-active-search"><?php echo e($filters['q']); ?></span></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($filters['q'])): ?>
                <div class="catalog-query-banner">
                    Mostrando resultados para <strong><?php echo e($filters['q']); ?></strong>
                </div>
                <?php endif; ?>
                <?php if (!empty($active_filter_chips)): ?>
                <div class="catalog-filter-chips" aria-label="Filtros activos">
                    <?php foreach ($active_filter_chips as $chip): ?>
                    <span><?php echo e($chip); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

        <?php if (!empty($products)): ?>
        <div class="catalog-grid">
            <?php foreach ($products as $product): ?>
            <?php $has_purchasable_price = !empty($product['can_view_price']) && (float) \Arr::get($product, 'price', 0) > 0; ?>
            <article class="catalog-card">
                <a href="<?php echo e($product_url($product['slug'])); ?>">
                    <img src="<?php echo e(!empty($product['main_image_path']) ? $media_url($product['main_image_path']) : $no_image_svg); ?>" alt="<?php echo e($product['name']); ?>">
                </a>
                <div class="body">
                    <?php if (!empty($product['sku']) || !empty($product['brand_name']) || !empty($product['category_name'])): ?>
                    <div class="product-card-meta">
                        <?php if (!empty($product['sku'])): ?><span>SKU: <?php echo e($product['sku']); ?></span><?php endif; ?>
                        <?php if (!empty($product['brand_name'])): ?><span><?php echo e($product['brand_name']); ?></span><?php endif; ?>
                        <?php if (!empty($product['category_name'])): ?><span><?php echo e($product['category_name']); ?></span><?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($product['subcategory_name'])): ?>
                    <div class="catalog-submeta"><?php echo e($product['subcategory_name']); ?></div>
                    <?php endif; ?>
                    <h2><a href="<?php echo e($product_url($product['slug'])); ?>"><?php echo e($product['name']); ?></a></h2>
                    <?php if (!empty($product['short_description'])): ?>
                    <p><?php echo e($product['short_description']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($product['can_view_price']) && (float) $product['price'] > 0): ?>
                    <div class="catalog-price-stack">
                        <div class="catalog-price-line"><span>Precio Lista</span><strong><?php echo e($product['list_currency_code']); ?> <?php echo number_format((float) $product['list_price'], 2); ?></strong></div>
                        <?php if (!empty($product['has_customer_price'])): ?>
                        <div class="catalog-price-line preferred"><span>Tu Precio</span><strong><?php echo e($product['customer_currency_code']); ?> <?php echo number_format((float) $product['customer_price'], 2); ?></strong></div>
                        <?php endif; ?>
                        <?php if (!empty($product['has_preferential_price'])): ?>
                        <div class="catalog-savings">Ahorro: <?php echo e($product['list_currency_code']); ?> <?php echo number_format((float) $product['price_savings'], 2); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="product-quote-state">
                        Solicita precio y disponibilidad con un asesor comercial.
                    </div>
                    <?php endif; ?>
                    <div class="product-commerce-badges">
                        <span><i class="bi bi-receipt"></i> Facturación disponible</span>
                        <span><i class="bi bi-briefcase"></i> Atención empresarial</span>
                        <span><i class="bi bi-truck"></i> Entrega disponible</span>
                    </div>
                    <div class="product-card-actions">
                        <?php if ($has_purchasable_price): ?>
                        <?php echo Form::open(['action' => 'carrito/agregar', 'method' => 'post', 'class' => 'product-card-cart-form', 'data-cart-ajax' => '1']); ?>
                            <?php echo Form::hidden('product_id', (int) $product['id']); ?>
                            <?php echo Form::hidden('quantity', 1); ?>
                            <button class="product-inquiry-link product-inquiry-link--primary" type="submit">Agregar al carrito</button>
                        <?php echo Form::close(); ?>
                        <a class="product-inquiry-link" href="<?php echo e(!empty($product['inquiry_enabled']) ? \Arr::get($product, 'inquiry_url', $product_contact_url($product)) : $product_contact_url($product)); ?>" target="<?php echo e(!empty($product['inquiry_enabled']) ? \Arr::get($product, 'inquiry_target', '_self') : '_self'); ?>" rel="noopener noreferrer" data-track-event="<?php echo !empty($product['inquiry_enabled']) ? 'quote_click whatsapp_catalog' : 'quote_click'; ?>">Cotizar</a>
                        <?php else: ?>
                        <?php if (!empty($product['inquiry_enabled'])): ?><a class="product-inquiry-link product-inquiry-link--primary" data-track-event="quote_click whatsapp_catalog" href="<?php echo e(\Arr::get($product, 'inquiry_url', Uri::create('pagina/contacto'))); ?>" target="<?php echo e(\Arr::get($product, 'inquiry_target', '_self')); ?>" rel="noopener noreferrer">Cotizar</a><?php endif; ?>
                        <?php endif; ?>
                        <a class="card-action secondary" href="<?php echo e($product_url($product['slug'])); ?>">Ver producto <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <h2>No encontramos productos con esos filtros.</h2>
            <p>Podemos ayudarte a ubicar una alternativa compatible o confirmar disponibilidad con un asesor.</p>
            <a class="empty-state-action" data-track-event="<?php echo !empty($filters['q']) ? 'whatsapp_search' : 'whatsapp_catalog'; ?>" href="<?php echo e($empty_help_url); ?>" target="<?php echo e($empty_help_target); ?>" rel="noopener noreferrer">Solicitar ayuda para encontrarlo</a>
        </div>
        <?php endif; ?>
            </div>
        </div>
    </div>
</section>


