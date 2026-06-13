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
    .account-benefits { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; margin: 18px 0 22px; }
    .account-benefit { border: 1px solid #dde3ea; border-radius: 8px; background: #f8fafc; padding: 10px 12px; color: #172033; font-size: .92rem; font-weight: 800; }
    .account-benefit i { color: var(--core-brand); margin-right: 6px; }
    .account-register-callout { border-top: 1px solid #dde3ea; margin-top: 20px; padding-top: 18px; color: #475569; }
    .account-register-callout strong { display: block; color: #172033; margin-bottom: 4px; }
    @media (max-width: 640px) { .account-benefits { grid-template-columns: 1fr; } .account-actions { align-items: stretch; flex-direction: column; } .account-btn, .account-link { width: 100%; text-align: center; } }
</style>

<section class="account-band">
    <div class="account-shell">
        <div class="account-panel">
            <h1>Acceso clientes</h1>
            <p>Ingresa para consultar precios y preparar tus compras.</p>
            <div class="account-benefits">
                <div class="account-benefit"><i class="bi bi-tags"></i> Precios asignados</div>
                <div class="account-benefit"><i class="bi bi-file-earmark-arrow-down"></i> CFDI</div>
                <div class="account-benefit"><i class="bi bi-wallet2"></i> Estado de cuenta</div>
                <div class="account-benefit"><i class="bi bi-truck"></i> Seguimiento de pedidos</div>
            </div>

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
                    <label>Contraseña</label>
                    <?php echo Form::password('password', '', ['autocomplete' => 'current-password']); ?>
                </div>
                <div class="account-actions">
                    <button class="account-btn" type="submit">Entrar</button>
                    <a class="account-link" href="<?php echo Uri::create('registro'); ?>">Crear cuenta</a>
                </div>
            <?php echo Form::close(); ?>
            <div class="account-register-callout">
                <strong>¿Aún no tienes cuenta empresarial?</strong>
                Regístrate para consultar precios asignados, CFDI, estado de cuenta y seguimiento de pedidos.
            </div>
        </div>
    </div>
</section>
