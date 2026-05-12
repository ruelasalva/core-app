<style>
    .account-band { padding: 56px 0; background: #f4f7fa; border-bottom: 1px solid #dde3ea; }
    .account-shell { width: min(920px, calc(100% - 32px)); margin: 0 auto; }
    .account-panel { max-width: 620px; margin: 34px auto 0; border: 1px solid #dde3ea; border-radius: 8px; background: #fff; padding: 28px; }
    .account-panel h1 { margin: 0 0 8px; font-size: 2rem; line-height: 1.1; }
    .account-panel p { margin: 0 0 18px; color: #657084; }
    .account-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
    .account-field label { display: block; margin-bottom: 6px; font-weight: 700; }
    .account-field input { width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 11px 12px; font: inherit; }
    .account-field.full { grid-column: 1 / -1; }
    .account-actions { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-top: 20px; }
    .account-btn { border: 0; border-radius: 6px; background: var(--core-brand); color: #fff; padding: 11px 18px; font-weight: 800; cursor: pointer; }
    .account-link { color: var(--core-brand); font-weight: 800; }
    .account-alert { border-radius: 6px; padding: 11px 12px; margin-bottom: 16px; background: #fee2e2; color: #991b1b; }
    @media (max-width: 640px) { .account-grid { grid-template-columns: 1fr; } }
</style>

<section class="account-band">
    <div class="account-shell">
        <div class="account-panel">
            <h1>Crear cuenta</h1>
            <p>Tu cuenta se crea como cliente web con acceso basico al portal de clientes.</p>

            <?php if (!empty($error)): ?>
            <div class="account-alert"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php echo Form::open(['action' => 'registro', 'method' => 'post']); ?>
                <div class="account-grid">
                    <div class="account-field full">
                        <label>Nombre</label>
                        <?php echo Form::input('name', Input::post('name', ''), ['autocomplete' => 'name']); ?>
                    </div>
                    <div class="account-field">
                        <label>Correo</label>
                        <?php echo Form::input('email', Input::post('email', ''), ['type' => 'email', 'autocomplete' => 'email']); ?>
                    </div>
                    <div class="account-field">
                        <label>Telefono</label>
                        <?php echo Form::input('phone', Input::post('phone', ''), ['autocomplete' => 'tel']); ?>
                    </div>
                    <div class="account-field">
                        <label>Contrasena</label>
                        <?php echo Form::password('password', '', ['autocomplete' => 'new-password']); ?>
                    </div>
                    <div class="account-field">
                        <label>Confirmar contrasena</label>
                        <?php echo Form::password('password_confirm', '', ['autocomplete' => 'new-password']); ?>
                    </div>
                    <?php if (!empty($captcha_html)): ?>
                    <div class="account-field full">
                        <?php echo $captcha_html; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="account-actions">
                    <button class="account-btn" type="submit">Crear cuenta</button>
                    <a class="account-link" href="<?php echo Uri::create('acceso'); ?>">Ya tengo cuenta</a>
                </div>
            <?php echo Form::close(); ?>
        </div>
    </div>
</section>
