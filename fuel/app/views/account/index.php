<style>
    .account-band { padding: 56px 0; background: #f4f7fa; border-bottom: 1px solid #dde3ea; }
    .account-shell { width: min(1020px, calc(100% - 32px)); margin: 0 auto; }
    .account-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-top: 28px; }
    .account-card { border: 1px solid #dde3ea; border-radius: 8px; background: #fff; padding: 22px; }
    .account-card h2 { margin: 0 0 14px; font-size: 1.15rem; }
    .account-card p { margin: 0 0 8px; color: #657084; }
    .account-action { display: inline-flex; margin-top: 12px; border-radius: 6px; background: var(--core-brand); color: #fff; padding: 10px 14px; font-weight: 800; }
    .account-wide { grid-column: 1 / -1; }
    .quote-table { width: 100%; border-collapse: collapse; }
    .quote-table th, .quote-table td { padding: 10px; border-bottom: 1px solid #e5ebf1; text-align: left; }
    .quote-table th { color: #334155; font-size: .86rem; text-transform: uppercase; }
    .quote-status { display: inline-flex; border-radius: 999px; background: #fef3c7; color: #92400e; padding: 4px 9px; font-size: .82rem; font-weight: 800; }
    @media (max-width: 720px) { .account-grid { grid-template-columns: 1fr; } }
</style>

<section class="account-band">
    <div class="account-shell">
        <h1>Mi cuenta</h1>
        <p>Consulta tu informacion comercial y accesos disponibles.</p>

        <div class="account-grid">
            <div class="account-card">
                <h2>Datos del cliente</h2>
                <p><strong>Nombre:</strong> <?php echo e($party ? $party->name : ''); ?></p>
                <p><strong>Correo:</strong> <?php echo e($party ? $party->email : ''); ?></p>
                <p><strong>Telefono:</strong> <?php echo e($party ? $party->phone : ''); ?></p>
                <p><strong>Lista de precios:</strong> <?php echo e($price_list); ?></p>
            </div>
            <div class="account-card">
                <h2>Accesos</h2>
                <p>Los precios del catalogo se muestran mientras esta sesion permanezca activa.</p>
                <p>El portal de clientes queda disponible para futuras compras, documentos y soporte.</p>
                <a class="account-action" href="<?php echo Uri::create('productos'); ?>">Ver productos</a>
            </div>
            <div class="account-card account-wide">
                <h2>Mis cotizaciones</h2>
                <?php if (!empty($quotes)): ?>
                <table class="quote-table">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Estado</th>
                            <th>Total</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quotes as $quote): ?>
                        <tr>
                            <td><strong><?php echo e($quote['folio']); ?></strong></td>
                            <td><span class="quote-status"><?php echo e($quote['status']); ?></span></td>
                            <td><?php echo e($quote['currency_code']); ?> <?php echo number_format((float) $quote['total'], 2); ?></td>
                            <td><?php echo !empty($quote['created_at']) ? date('Y-m-d H:i', (int) $quote['created_at']) : ''; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>Todavia no tienes cotizaciones solicitadas.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
