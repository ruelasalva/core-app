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

<section class="product-shell product-detail">
    <div>
        <div class="product-media">
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

        <h1><?php echo e($product['name']); ?></h1>

        <?php if (!empty($product['short_description'])): ?>
        <p class="short"><?php echo e($product['short_description']); ?></p>
        <?php endif; ?>

        <?php if (!empty($product['can_view_price'])): ?>
        <div class="product-price">
            <?php echo e($product['currency_code']); ?> <?php echo number_format((float) $product['price'], 2); ?>
        </div>
        <?php echo Form::open(['action' => 'carrito/agregar', 'method' => 'post', 'class' => 'product-cart-form', 'data-cart-ajax' => '1']); ?>
            <?php echo Form::hidden('product_id', (int) $product['id']); ?>
            <?php echo Form::input('quantity', 1, ['type' => 'number', 'min' => '1', 'step' => '1']); ?>
            <button type="submit">Agregar al carrito</button>
        <?php echo Form::close(); ?>
        <?php else: ?>
        <div class="product-login-price">
            <a href="<?php echo Uri::create('acceso'); ?>">Inicia sesion</a> o <a href="<?php echo Uri::create('registro'); ?>">crea tu cuenta</a> para consultar precio y comprar.
        </div>
        <?php endif; ?>

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
        <a class="related-card" href="<?php echo e(Uri::create('producto/'.$related['slug'])); ?>">
            <img src="<?php echo e(!empty($related['main_image_path']) ? $media_url($related['main_image_path']) : $no_image_svg); ?>" alt="<?php echo e($related['name']); ?>">
            <div class="body">
                <h3><?php echo e($related['name']); ?></h3>
                <?php if (!empty($related['short_description'])): ?>
                <p><?php echo e($related['short_description']); ?></p>
                <?php endif; ?>
                <?php if (!empty($related['can_view_price'])): ?>
                <div class="product-price" style="font-size: 1.05rem; margin: 12px 0 0;">
                    <?php echo e($related['currency_code']); ?> <?php echo number_format((float) $related['price'], 2); ?>
                </div>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
