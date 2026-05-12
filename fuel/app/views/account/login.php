<style>
    .account-band { padding: 56px 0; background: #f4f7fa; border-bottom: 1px solid #dde3ea; }
    .account-shell { width: min(920px, calc(100% - 32px)); margin: 0 auto; }
    .account-panel { max-width: 520px; margin: 34px auto 0; border: 1px solid #dde3ea; border-radius: 8px; background: #fff; padding: 28px; }
    .account-panel h1 { margin: 0 0 8px; font-size: 2rem; line-height: 1.1; }
    .account-panel p { margin: 0 0 18px; color: #657084; }
    .account-field { margin-bottom: 14px; }
    .account-field label { display: block; margin-bottom: 6px; font-weight: 700; }
    .account-field input { width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 11px 12px; font: inherit; }
    .account-actions { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-top: 18px; }
    .account-btn { border: 0; border-radius: 6px; background: var(--core-brand); color: #fff; padding: 11px 18px; font-weight: 800; cursor: pointer; }
    .account-link { color: var(--core-brand); font-weight: 800; }
    .account-alert { border-radius: 6px; padding: 11px 12px; margin-bottom: 16px; }
    .account-alert.error { background: #fee2e2; color: #991b1b; }
    .account-alert.success { background: #dcfce7; color: #166534; }
</style>

<section class="account-band">
    <div class="account-shell">
        <div class="account-panel">
            <h1>Acceso clientes</h1>
            <p>Ingresa para consultar precios y preparar tus compras.</p>

            <?php if (!empty($error)): ?>
            <div class="account-alert error"><?php echo e($error); ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
            <div class="account-alert success"><?php echo e($success); ?></div>
            <?php endif; ?>

            <?php echo Form::open(['action' => 'acceso', 'method' => 'post']); ?>
                <?php echo Form::csrf(); ?>
                <div class="account-field">
                    <label>Correo</label>
                    <?php echo Form::input('email', Input::post('email', ''), ['type' => 'email', 'autocomplete' => 'email']); ?>
                </div>
                <div class="account-field">
                    <label>Contrasena</label>
                    <?php echo Form::password('password', '', ['autocomplete' => 'current-password']); ?>
                </div>
                <div class="account-actions">
                    <button class="account-btn" type="submit">Entrar</button>
                    <a class="account-link" href="<?php echo Uri::create('registro'); ?>">Crear cuenta</a>
                </div>
            <?php echo Form::close(); ?>
        </div>
    </div>
</section>
