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
?>

<style>
    .front-hero {
        min-height: 430px;
        display: flex;
        align-items: flex-end;
        background: #16323d;
        color: #fff;
        overflow: hidden;
        position: relative;
    }
    .front-hero img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: .76;
    }
    .front-hero:after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(0deg, rgba(10, 17, 28, .78), rgba(10, 17, 28, .15));
    }
    .front-hero-content {
        position: relative;
        z-index: 1;
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
        padding: 82px 0 70px;
    }
    .front-hero h1 {
        max-width: 780px;
        margin: 0;
        font-size: clamp(2.25rem, 6vw, 4.9rem);
        line-height: .98;
        letter-spacing: 0;
    }
    .front-hero p {
        max-width: 650px;
        margin: 18px 0 0;
        color: #e8eef5;
        font-size: 1.08rem;
    }
    .front-hero .button,
    .section-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-top: 24px;
        padding: 12px 18px;
        background: #0f766e;
        color: #fff;
        font-weight: 700;
        border-radius: 6px;
    }
    .section-band {
        padding: 58px 0;
        border-bottom: 1px solid #e5ebf1;
    }
    .section-shell {
        width: min(1180px, calc(100% - 32px));
        margin: 0 auto;
    }
    .section-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.1fr) minmax(280px, .9fr);
        gap: 42px;
        align-items: center;
    }
    .section-copy h2 {
        margin: 0 0 10px;
        font-size: clamp(1.7rem, 3vw, 2.6rem);
        letter-spacing: 0;
        line-height: 1.08;
    }
    .section-copy .subtitle {
        margin: 0 0 18px;
        color: #657084;
        font-size: 1.05rem;
    }
    .section-copy .content {
        color: #334155;
        font-size: 1rem;
    }
    .section-media {
        width: 100%;
        aspect-ratio: 4 / 3;
        background: #f4f7fa;
        border: 1px solid #dde3ea;
        border-radius: 8px;
        overflow: hidden;
    }
    .section-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .banner-grid,
    .brand-grid,
    .feature-grid,
    .download-grid,
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 18px;
    }
    .banner-item,
    .product-card {
        border: 1px solid #dde3ea;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
    }
    .banner-item img,
    .product-card img {
        width: 100%;
        aspect-ratio: 16 / 10;
        object-fit: cover;
        background: #f4f7fa;
    }
    .banner-item h3,
    .product-card h3 {
        margin: 0;
        padding: 14px;
        font-size: 1rem;
    }
    .product-card .body {
        padding: 14px;
    }
    .product-card h3 {
        padding: 0 0 8px;
    }
    .product-card p {
        margin: 0;
        color: #657084;
        font-size: .94rem;
    }
    .product-price {
        margin-top: 12px;
        color: #0f766e;
        font-weight: 800;
    }
    .feature-item,
    .download-item,
    .brand-item {
        border: 1px solid #dde3ea;
        border-radius: 8px;
        background: #fff;
        padding: 18px;
    }
    .brand-item {
        min-height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .brand-item img {
        max-height: 72px;
        width: auto;
        object-fit: contain;
    }
    .download-item strong,
    .feature-item strong {
        display: block;
        margin-bottom: 8px;
        color: #172033;
    }
    @media (max-width: 820px) {
        .section-grid {
            grid-template-columns: 1fr;
        }
        .front-hero {
            min-height: 360px;
        }
    }
</style>

<?php if (!empty($slider_items)): ?>
    <?php $hero = reset($slider_items); ?>
    <section class="front-hero">
        <?php if (!empty($hero->image_path)): ?>
        <img src="<?php echo e($media_url($hero->image_path)); ?>" alt="<?php echo e($hero->title); ?>">
        <?php endif; ?>
        <div class="front-hero-content">
            <h1><?php echo e($hero->title ?: $page->title); ?></h1>
            <?php if (!empty($hero->subtitle)): ?>
            <p><?php echo e($hero->subtitle); ?></p>
            <?php endif; ?>
            <?php if (!empty($hero->button_text) && !empty($hero->button_url)): ?>
            <a class="button" href="<?php echo e($hero->button_url); ?>"><?php echo e($hero->button_text); ?> <i class="bi bi-arrow-right"></i></a>
            <?php endif; ?>
        </div>
    </section>
<?php else: ?>
    <section class="front-hero">
        <div class="front-hero-content">
            <h1><?php echo e($page->title); ?></h1>
            <?php if (!empty($page->seo_description)): ?>
            <p><?php echo e($page->seo_description); ?></p>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>

<?php if (!empty($sections)): ?>
    <?php foreach ($sections as $section): ?>
    <section class="section-band">
        <?php if ($section->section_type === 'feature_grid'): ?>
        <div class="section-shell">
            <div class="section-copy" style="margin-bottom: 24px;">
                <h2><?php echo e($section->title); ?></h2>
                <?php if (!empty($section->subtitle)): ?>
                <p class="subtitle"><?php echo e($section->subtitle); ?></p>
                <?php endif; ?>
            </div>
            <div class="feature-grid">
                <?php foreach (array_filter(array_map('trim', explode('|', (string) $section->content))) as $feature): ?>
                <div class="feature-item"><strong><?php echo e($feature); ?></strong></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php elseif ($section->section_type === 'brands'): ?>
        <div class="section-shell">
            <div class="section-copy" style="margin-bottom: 24px;">
                <h2><?php echo e($section->title); ?></h2>
                <?php if (!empty($section->subtitle)): ?>
                <p class="subtitle"><?php echo e($section->subtitle); ?></p>
                <?php endif; ?>
            </div>
            <div class="brand-grid">
                <?php foreach ($featured_brands as $brand): ?>
                <a class="brand-item" href="<?php echo e(Uri::create('productos')); ?>">
                    <?php if (!empty($brand['logo_path'])): ?>
                    <img src="<?php echo e($media_url($brand['logo_path'])); ?>" alt="<?php echo e($brand['name']); ?>">
                    <?php else: ?>
                    <strong><?php echo e($brand['name']); ?></strong>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php elseif ($section->section_type === 'download_cards'): ?>
        <?php $settings = json_decode((string) $section->settings_json, true); $downloads = !empty($settings['items']) ? $settings['items'] : array(); ?>
        <div class="section-shell">
            <div class="section-copy" style="margin-bottom: 24px;">
                <h2><?php echo e($section->title); ?></h2>
                <?php if (!empty($section->subtitle)): ?>
                <p class="subtitle"><?php echo e($section->subtitle); ?></p>
                <?php endif; ?>
            </div>
            <div class="download-grid">
                <?php foreach ($downloads as $download): ?>
                <a class="download-item" href="<?php echo e($media_url(\Arr::get($download, 'url', '#'))); ?>" target="_blank">
                    <strong><?php echo e(\Arr::get($download, 'title', 'Descarga')); ?></strong>
                    <span>Ver documento</span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php elseif ($section->section_type === 'cta' || $section->section_type === 'contact_info'): ?>
        <div class="section-shell">
            <div class="section-copy">
                <h2><?php echo e($section->title); ?></h2>
                <?php if (!empty($section->subtitle)): ?>
                <p class="subtitle"><?php echo e($section->subtitle); ?></p>
                <?php endif; ?>
                <?php if (!empty($section->content)): ?>
                <div class="content"><?php echo $section->content; ?></div>
                <?php endif; ?>
                <?php if ($section->section_type === 'cta'): ?>
                <?php $cta_settings = json_decode((string) $section->settings_json, true); ?>
                <a class="section-link" href="<?php echo e(\Arr::get($cta_settings, 'button_url', Uri::create('pagina/contacto'))); ?>">
                    <?php echo e(\Arr::get($cta_settings, 'button_text', 'Contactar')); ?> <i class="bi bi-arrow-right"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="section-shell section-grid">
            <div class="section-copy">
                <h2><?php echo e($section->title); ?></h2>
                <?php if (!empty($section->subtitle)): ?>
                <p class="subtitle"><?php echo e($section->subtitle); ?></p>
                <?php endif; ?>
                <?php if (!empty($section->content)): ?>
                <div class="content"><?php echo $section->content; ?></div>
                <?php endif; ?>
            </div>
            <?php if (!empty($section->media_path)): ?>
            <div class="section-media">
                <img src="<?php echo e($media_url($section->media_path)); ?>" alt="<?php echo e($section->title); ?>">
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </section>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($banners)): ?>
<section class="section-band">
    <div class="section-shell">
        <div class="banner-grid">
            <?php foreach ($banners as $banner): ?>
            <a class="banner-item" href="<?php echo e($banner->url ?: '#'); ?>">
                <?php if (!empty($banner->image_path)): ?>
                <img src="<?php echo e($media_url($banner->image_path)); ?>" alt="<?php echo e($banner->title); ?>">
                <?php endif; ?>
                <h3><?php echo e($banner->title); ?></h3>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($featured_products)): ?>
<section class="section-band">
    <div class="section-shell">
        <div class="section-copy" style="margin-bottom: 24px;">
            <h2>Productos destacados</h2>
        </div>
        <div class="product-grid">
            <?php foreach ($featured_products as $product): ?>
            <a class="product-card" href="<?php echo e(Uri::create('producto/'.$product['slug'])); ?>">
                <?php if (!empty($product['main_image_path'])): ?>
                <img src="<?php echo e($media_url($product['main_image_path'])); ?>" alt="<?php echo e($product['name']); ?>">
                <?php endif; ?>
                <div class="body">
                    <h3><?php echo e($product['name']); ?></h3>
                    <?php if (!empty($product['short_description'])): ?>
                    <p><?php echo e($product['short_description']); ?></p>
                    <?php endif; ?>
                    <div class="product-price">
                        <?php echo e($product['currency_code']); ?> <?php echo number_format((float) $product['price'], 2); ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
