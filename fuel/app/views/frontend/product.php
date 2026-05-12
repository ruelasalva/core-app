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
    @media (max-width: 860px) {
        .product-detail {
            grid-template-columns: 1fr;
        }
    }
</style>

<section class="product-shell product-detail">
    <div>
        <div class="product-media">
            <?php if (!empty($product['main_image_path'])): ?>
            <img src="<?php echo e($media_url($product['main_image_path'])); ?>" alt="<?php echo e($product['name']); ?>">
            <?php endif; ?>
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
