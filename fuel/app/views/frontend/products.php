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

$product_url = function ($slug) {
    return Uri::create('producto/'.$slug);
};

$category_url = function ($slug) {
    return Uri::create('categoria/'.$slug);
};
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
    .catalog-price {
        margin-top: 14px;
        font-weight: 800;
        color: #172033;
    }
    .empty-state {
        padding: 34px;
        border: 1px dashed #b8c4d1;
        border-radius: 8px;
        color: #657084;
        background: #f8fafc;
    }
</style>

<section class="catalog-hero">
    <div class="catalog-shell">
        <h1><?php echo e($title); ?></h1>
        <?php if (!empty($description)): ?>
        <p><?php echo e($description); ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="catalog-section">
    <div class="catalog-shell">
        <?php if (!empty($products)): ?>
        <div class="catalog-grid">
            <?php foreach ($products as $product): ?>
            <a class="catalog-card" href="<?php echo e($product_url($product['slug'])); ?>">
                <?php if (!empty($product['main_image_path'])): ?>
                <img src="<?php echo e($media_url($product['main_image_path'])); ?>" alt="<?php echo e($product['name']); ?>">
                <?php endif; ?>
                <div class="body">
                    <?php if (!empty($product['category_name'])): ?>
                    <div class="catalog-meta"><?php echo e($product['category_name']); ?></div>
                    <?php endif; ?>
                    <h2><?php echo e($product['name']); ?></h2>
                    <?php if (!empty($product['short_description'])): ?>
                    <p><?php echo e($product['short_description']); ?></p>
                    <?php endif; ?>
                    <div class="catalog-price">
                        <?php echo e($product['currency_code']); ?> <?php echo number_format((float) $product['price'], 2); ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            Todavia no hay productos publicados en este apartado.
        </div>
        <?php endif; ?>
    </div>
</section>
