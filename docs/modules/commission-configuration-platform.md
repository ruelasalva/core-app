# Plataforma de Configuración de Comisiones

## Propósito

La Plataforma de Configuración de Comisiones permite administrar reglas comerciales para el futuro motor de comisiones sin ejecutar cálculos, liberar comisiones ni modificar flujos actuales de ventas, facturación, pagos, SAT, fiscal, contabilidad o contratos.

El flujo funcional es:

Configuración -> Validación -> Publicación -> Ejecución futura.

## Alcance de RC5 Phase 3B

Incluido:

- Planes comerciales.
- Versiones de plan.
- Grupos de reglas.
- Reglas.
- Etapas de liberación.
- Beneficiarios.
- Exclusiones.
- Catálogos auxiliares.
- Publicación controlada.
- Snapshot de configuración publicada.

No incluido:

- Cálculo de comisiones.
- Generación de movimientos.
- Liberación por pagos.
- Liquidaciones.
- Aprobaciones operativas.
- Integración con ventas, facturación, pagos o contabilidad.

## Tablas

- `core_commission_config_commercial_plans`
- `core_commission_config_versions`
- `core_commission_config_rule_groups`
- `core_commission_config_rules`
- `core_commission_config_rule_stages`
- `core_commission_config_rule_beneficiaries`
- `core_commission_config_rule_exclusions`
- `core_commission_config_catalogs`

Estas tablas no reemplazan las tablas actuales del motor de comisiones. Son configuración versionada para fases futuras.

## Versionamiento

Cada plan comercial puede tener múltiples versiones.

Estados:

- `draft`: editable.
- `testing`: editable para validación controlada.
- `published`: inmutable.
- `archived`: no editable.

Una versión publicada no debe modificarse. Cualquier cambio futuro debe crearse en una nueva versión.

## Publicación

La publicación:

- Requiere permiso de autorización.
- Requiere motivo.
- Cambia la versión a `published`.
- Guarda `config_snapshot_json`.
- Registra usuario y fecha de publicación.
- Actualiza el plan comercial a estado publicado.

El snapshot conserva la configuración que consumirá el motor futuro.

## Jerarquía de reglas

Plan comercial

Version

Grupo de reglas

Regla

Etapas

Beneficiarios

Exclusiones

Las reglas soportan prioridad, acumulación, exclusividad, detener procesamiento, vigencia, evento detonador, base de cálculo, tipo de valor y notas de negocio.

## Bases de cálculo futuras

- Subtotal.
- Total.
- Margen.
- Monto fijo.
- Monto recurrente.
- Cantidad.
- Fórmula futura.

La base `margin` queda marcada como sensible porque puede exponer información de costos. El motor futuro deberá validar permisos específicos antes de mostrar o usar margen.

## Eventos configurables

Ejemplos soportados como configuración:

- Cotización.
- Pedido.
- Entrega.
- Factura.
- Pago parcial.
- Pago completo.
- Contrato firmado.
- Contrato activado.
- Facturación recurrente.
- Evento manual.

## Etapas de liberación

Una regla puede tener etapas ilimitadas.

Ejemplo:

- 30% al facturar.
- 70% al cobrar.

Esta fase solo configura la estructura. No libera comisiones.

## Beneficiarios futuros

Tipos previstos:

- Vendedor.
- Supervisor.
- Gerente comercial.
- Socio.
- Agente externo.

El motor futuro deberá generar entradas independientes por beneficiario.

## Exclusiones

Se pueden preparar exclusiones por:

- Producto.
- Categoría.
- Marca.
- Cliente.
- Proveedor.
- Contrato.
- Campaña.
- Vendedor.
- Región.

## Permisos

Permisos usados:

- `commissions.access[view]`: ver configuración.
- `commissions.access[edit]`: crear y editar configuración editable.
- `commissions.access[authorize]`: publicar versiones.

Grupo 100 mantiene bypass administrativo según reglas generales del ERP.

## Simulador futuro

RC5 Phase 3C agrega servicios read-only para simular reglas publicadas. La simulación explica:

- Reglas coincidentes.
- Reglas ignoradas.
- Prioridad aplicada.
- Base de cálculo.
- Advertencias.
- Resultado estimado.

No guarda movimientos, no crea `core_commission_entries`, no libera pagos y no modifica datos operativos.

Bases soportadas en la primera versión:

- Subtotal.
- Total.
- Monto fijo.
- Cantidad.
- Monto recurrente si se proporciona el dato.

La base margen permanece deshabilitada hasta contar con permiso explícito y reglas de exposición de costos.

## Auditoría

La plataforma registra eventos de configuración con `Helper_Core_Audit`.

Eventos futuros sugeridos:

- `commission.plan.created`
- `commission.plan.published`
- `commission.rule.created`
- `commission.rule.updated`
- `commission.rule.archived`

## Validación

Validaciones mínimas:

- `php -l` en archivos modificados.
- Migración con `FUEL_ENV=development`.
- Seed de ayuda con `FUEL_ENV=development`.
- Confirmar que no se modifican tablas operativas actuales.
- Confirmar que no se exponen `file_path` ni `storage_path`.
- Confirmar que la vista no usa `response.json`, `res.json`, `v-html`, `alert()` ni `confirm()`.

## Riesgos

- Publicar una configuración incompleta puede confundir al usuario si se interpreta como motor activo.
- La base de margen requiere permisos estrictos en fases futuras.
- Las versiones publicadas deben permanecer inmutables para proteger snapshots históricos.

## Roadmap

1. Configuración y publicación controlada.
2. Simulador read-only.
3. Motor de evaluación de reglas.
4. Generación de entradas de comisión.
5. Liberación por pagos.
6. Aprobación y liquidación.
7. Reportes y KPIs en Administración Comercial.

## RC5 Phase 3D: Entradas pendientes por factura

La generación operativa inicial consume únicamente facturas de venta activas y timbradas desde `core_billing_invoices`. Cada partida se evalúa con el mismo `RuleEngine` usado por el simulador.

Las entradas se guardan en `core_commission_entries` con:

- `status = pending`.
- `earned_at = 0`.
- `settlement_id = 0`.
- `released_amount = 0`.
- `released_percent = 0`.
- identificadores de plan, versión, regla, etapa y beneficiario de configuración.
- `calculation_snapshot_json` para preservar la explicación histórica.
- `source_hash` para idempotencia.

Las columnas legacy `plan_id` y `rule_id` no representan la nueva plataforma. Los vínculos nuevos se almacenan en `config_plan_id`, `config_version_id` y `config_rule_id`, evitando mezclar ambos espacios de identificadores.

### Comandos

PowerShell, validación sin escritura:

```powershell
$env:FUEL_ENV='development'; php oil refine generatecommissions --invoice=ID --dry-run=1
```

Aplicación controlada:

```powershell
$env:FUEL_ENV='development'; php oil refine generatecommissions --invoice=ID --apply=1
```

El modo batch permanece opt-in y solo escribe cuando se especifica `--apply=1`.

### Límites de esta fase

- No libera comisiones al recibir pagos.
- No cambia una entrada a `earned`.
- No crea liquidaciones.
- No paga comisiones.
- No revierte automáticamente provisiones cuando una factura se cancela después de la generación.
- La base margen permanece deshabilitada.
- Beneficiarios sin correspondencia en `core_sales_sellers` se omiten con advertencia.
