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
    .account-benefits { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; margin: 18px 0 22px; }
    .account-benefit { border: 1px solid #dde3ea; border-radius: 8px; background: #f8fafc; padding: 10px 12px; color: #172033; font-size: .92rem; font-weight: 800; }
    .account-benefit i { color: var(--core-brand); margin-right: 6px; }
    .account-note { border: 1px solid #bfdbfe; border-radius: 8px; background: #eff6ff; color: #1e3a8a; padding: 11px 12px; margin: -4px 0 18px; font-size: .94rem; }
    @media (max-width: 640px) { .account-grid { grid-template-columns: 1fr; } }
    @media (max-width: 640px) { .account-benefits { grid-template-columns: 1fr; } .account-actions { align-items: stretch; flex-direction: column; } .account-btn, .account-link { width: 100%; text-align: center; } }
</style>

<section class="account-band">
    <div class="account-shell">
        <div class="account-panel">
            <h1>Crear cuenta</h1>
            <p>Crea tu cuenta empresarial para acceder a precios asignados, historial de cotizaciones, pedidos, estado de cuenta y CFDI.</p>
            <div class="account-benefits" data-track-impression="register_start">
                <div class="account-benefit"><i class="bi bi-tags"></i> Consulta precios asignados</div>
                <div class="account-benefit"><i class="bi bi-file-earmark-arrow-down"></i> Descarga CFDI</div>
                <div class="account-benefit"><i class="bi bi-wallet2"></i> Estado de cuenta</div>
                <div class="account-benefit"><i class="bi bi-clock-history"></i> Historial de cotizaciones</div>
                <div class="account-benefit"><i class="bi bi-truck"></i> Seguimiento de pedidos</div>
                <div class="account-benefit"><i class="bi bi-headset"></i> Atención empresarial</div>
            </div>
            <div class="account-note">RFC y datos fiscales se completan después desde tu portal.</div>

            <?php if (!empty($error)): ?>
            <div class="account-alert"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php echo Form::open(['action' => 'registro', 'method' => 'post', 'data-track-form' => 'register_submit']); ?>
                <?php echo Form::csrf(); ?>
                <div class="account-grid">
                    <div class="account-field">
                        <label>Nombre</label>
                        <?php echo Form::input('name', Input::post('name', ''), ['autocomplete' => 'name']); ?>
                    </div>
                    <div class="account-field">
                        <label>Empresa</label>
                        <?php echo Form::input('company', Input::post('company', ''), ['autocomplete' => 'organization']); ?>
                    </div>
                    <div class="account-field">
                        <label>Correo</label>
                        <?php echo Form::input('email', Input::post('email', ''), ['type' => 'email', 'autocomplete' => 'email']); ?>
                    </div>
                    <div class="account-field">
                        <label>Teléfono</label>
                        <?php echo Form::input('phone', Input::post('phone', ''), ['autocomplete' => 'tel']); ?>
                    </div>
                    <div class="account-field">
                        <label>Contraseña</label>
                        <?php echo Form::password('password', '', ['autocomplete' => 'new-password']); ?>
                    </div>
                    <div class="account-field">
                        <label>Confirmar contraseña</label>
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
