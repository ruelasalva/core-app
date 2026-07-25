# Commission Engine Functional Architecture

## 1. Objetivo

El Commission Engine debe calcular, explicar, liberar, aprobar, liquidar y auditar comisiones comerciales sin acoplarse directamente a Ventas, Facturación, Pagos, Contratos o Contabilidad.

Esta especificación es funcional. No implementa lógica, migraciones ni cambios al motor actual. Define el comportamiento objetivo para sprints futuros.

## 2. Estructura Actual Encontrada

El proyecto ya cuenta con una base inicial de comisiones:

- `core_sales_sellers`: vendedores internos, revendedores o externos.
- `core_commission_plans`: planes de comisión.
- `core_commission_rules`: reglas por alcance, evento, base, porcentaje o monto fijo.
- `core_commission_quotas`: cuotas por vendedor y periodo.
- `core_commission_entries`: movimientos de comisión.
- `core_commission_settlements`: liquidaciones.
- `core_commission_adjustments`: ajustes.

El controlador actual `Controller_Admin_Commissions` permite administrar vendedores, planes, reglas, cuotas, ajustes, generación manual desde pedidos y liquidaciones. La generación actual se concentra en pedidos de venta y reglas con eventos `sale`, `invoice`, `delivery` o `payment`, pero todavía no existe liberación real por pago ni integración formal con contratos, facturas, cancelaciones o Event Bus.

## 3. Principios Funcionales

- Cada comisión debe ser explicable.
- Cada cálculo debe conservar snapshot histórico.
- Las reglas nuevas no deben alterar comisiones ya generadas.
- Las comisiones pagadas no se editan ni eliminan; se corrigen con reversas o ajustes auditados.
- El motor debe soportar eventos configurables, no depender solo de facturas.
- La liberación por pago debe ser proporcional y auditable.
- El cálculo por margen requiere permiso especial y control de exposición.
- Las reglas deben ser evaluadas por prioridad, acumulación y exclusión.
- El motor debe emitir eventos de negocio para comunicaciones, Workspace, auditoría y reportes.

## 4. Eventos de Comisión

Los planes deben definir qué evento genera una comisión y qué evento la libera.

Eventos funcionales iniciales:

- `quotation_created`: cotización creada.
- `quotation_approved`: cotización aprobada.
- `sales_order_created`: pedido creado.
- `order_delivered`: pedido entregado.
- `invoice_issued`: factura emitida.
- `invoice_paid`: factura pagada totalmente.
- `partial_payment`: pago parcial aplicado.
- `contract_signed`: contrato firmado.
- `contract_activated`: contrato activado.
- `recurring_contract_payment`: cobro recurrente de contrato.
- `manual_event`: evento manual controlado.
- `erp_event_bus`: evento recibido desde `Helper_Core_Event::fire()`.

Cada evento debe transportar identificadores mínimos:

- `event_code`
- `source_module`
- `source_entity_type`
- `source_entity_id`
- `source_item_id`
- `party_id`
- `seller_id`
- `currency_code`
- `event_date`
- `amounts`
- `metadata` segura

No debe transportar costos, márgenes, secretos, rutas físicas ni payloads completos.

## 5. Etapas de Comisión

El motor debe soportar etapas ilimitadas por plan o regla.

Ejemplos:

- 30% al facturar y 70% al cobrar.
- 20% al aprobar cotización, 30% al entregar y 50% al cobrar.
- 100% al pago para ventas de contado.
- Monto fijo al activar contrato y porcentaje recurrente por cada pago.

Cada etapa debe tener:

- código
- nombre
- evento disparador
- porcentaje de la comisión total
- condición de liberación
- orden
- política de reversa
- estado inicial

La suma de etapas puede ser menor, igual o mayor a 100% solo si el plan lo permite explícitamente. Por defecto debe validar 100%.

## 6. Bases de Cálculo

Bases soportadas:

- `subtotal`: subtotal sin impuestos.
- `total`: total con impuestos, solo si la política lo permite.
- `margin`: margen comercial.
- `quantity`: cantidad.
- `fixed_amount`: monto fijo.
- `recurring_amount`: importe recurrente de contrato.
- `custom_formula`: fórmula futura, deshabilitada por defecto.

Riesgos:

- `margin` puede exponer costos o márgenes internos. Requiere permiso `commissions.access[margin]`.
- `total` puede pagar comisión sobre impuestos. Debe usarse solo si la política comercial lo aprueba.
- `custom_formula` debe ejecutarse con un lenguaje controlado o evaluador seguro; nunca con `eval()`.

## 7. Exclusiones

El motor debe soportar exclusiones por:

- producto
- marca
- categoría
- subcategoría
- cliente
- vendedor
- proveedor
- contrato
- campaña
- canal
- tipo de documento
- moneda
- condición fiscal o comercial

Cada exclusión debe indicar si:

- solo ignora una regla
- detiene el procesamiento del plan
- detiene todas las reglas
- permite regla fallback

## 8. Prioridad y Resolución de Conflictos

Evaluación recomendada:

1. Validar plan activo y vigencia.
2. Validar evento disparador.
3. Aplicar exclusiones globales.
4. Evaluar reglas por prioridad ascendente.
5. Evaluar coincidencias específicas antes que generales.
6. Aplicar reglas acumulables.
7. Detener si la regla marca `stop_processing`.
8. Aplicar fallback si ninguna regla coincide.

Reglas:

- Una regla exclusiva bloquea reglas de menor prioridad dentro del mismo plan.
- Una regla acumulable puede coexistir con otras reglas acumulables.
- Si hay conflicto entre regla específica y general, gana la específica.
- Si dos reglas tienen la misma prioridad y ambas son exclusivas, debe registrarse advertencia y aplicar la más específica.

## 9. Snapshot Histórico

Cada comisión generada debe guardar su fotografía de cálculo para que el resultado no cambie cuando cambie la configuración.

Snapshot recomendado:

- plan id, código, nombre y versión.
- regla id, código, nombre y versión.
- evento y fecha.
- base de cálculo.
- porcentaje o monto fijo aplicado.
- vendedor y beneficiarios.
- cliente o tercero.
- producto, marca, categoría y subcategoría cuando aplique.
- moneda.
- importe base.
- comisión total calculada.
- etapas generadas.
- configuración de prioridad.
- exclusiones evaluadas.
- usuario o proceso que generó.
- advertencias.

El snapshot debe almacenarse como JSON seguro y no debe incluir costos visibles si el usuario no tiene permiso.

## 10. Liberación por Pago Parcial

La liberación por pago debe ser proporcional a la aplicación real del pago.

Ejemplo:

- Factura: 10,000
- Comisión total: 500
- Pago aplicado: 4,000
- Proporción pagada: 40%
- Comisión liberada: 200

Debe soportar:

- múltiples pagos.
- pagos parciales.
- reversas de pago.
- notas de crédito.
- cancelación de factura.
- saldo pendiente.
- pagos aplicados desde `core_payment_allocations`.

La liberación nunca debe duplicar importes. Cada pago aplicado debe tener una relación única con la liberación de comisión.

## 11. Comisiones por Equipo

Una misma operación puede generar comisiones para varios beneficiarios:

- vendedor.
- supervisor.
- gerente comercial.
- socio o partner.
- agente externo.
- revendedor.

Cada beneficiario debe tener una entrada independiente, su propio plan/regla, estado, liquidación y snapshot.

El motor no debe mezclar importes de beneficiarios en una sola entrada.

## 12. Ajustes

Tipos funcionales:

- bono.
- penalización.
- corrección.
- anticipo.
- préstamo.
- recuperación.
- ajuste manual.
- ajuste automático.

Reglas:

- Todo ajuste requiere motivo.
- Ajustes negativos no deben dejar liquidaciones pagadas en negativo sin autorización.
- Anticipos y préstamos deben liquidarse contra comisiones futuras mediante recuperación.
- Correcciones de comisiones pagadas deben crear movimiento inverso, no modificar el histórico.

## 13. Flujo de Aprobación

Estados recomendados de entrada:

- `pending`: calculada pero no ganada.
- `earned`: ganada por evento operativo.
- `approved`: aprobada para liquidación.
- `settled`: incluida en liquidación.
- `paid`: pagada.
- `cancelled`: cancelada antes de pago.
- `reversed`: revertida por cancelación, nota o corrección.
- `held`: retenida por disputa o revisión.

Transiciones:

- `pending` a `earned`: motor por evento.
- `earned` a `approved`: responsable comercial o finanzas.
- `approved` a `settled`: creador de liquidación.
- `settled` a `paid`: tesorería o responsable de pagos.
- cualquier estado no pagado a `held`: responsable autorizado.
- `paid` a `reversed`: solo con ajuste inverso auditado.

## 14. Simulador

El simulador debe calcular sin escribir en base de datos.

Debe explicar:

- reglas evaluadas.
- reglas coincidentes.
- reglas ignoradas.
- prioridad aplicada.
- base de cálculo.
- porcentaje o monto aplicado.
- comisión estimada.
- etapas.
- advertencias.
- permisos faltantes.

El simulador debe usar los mismos servicios de evaluación que el motor real, pero con persistencia deshabilitada.

## 15. Panel de Explicación

Cada entrada debe responder: ¿por qué se generó esta comisión?

Debe mostrar:

- plan y regla.
- evento disparador.
- origen operativo.
- fórmula.
- importe base.
- porcentaje o monto fijo.
- comisión total.
- importe liberado.
- importe pendiente.
- estado.
- snapshot.
- auditoría.
- liberaciones relacionadas.
- ajustes relacionados.

## 16. Reportes

Reportes requeridos:

- comisiones por vendedor.
- comisiones por cliente.
- comisiones por factura.
- comisiones por pago.
- comisiones pendientes.
- comisiones ganadas.
- comisiones aprobadas.
- liquidaciones.
- bonos.
- ajustes.
- reversas.
- comisiones recurrentes.
- comisiones por contrato.
- comisiones por equipo.
- comisiones retenidas.
- KPIs de Administración Comercial.

KPIs:

- comisiones pendientes.
- comisiones ganadas.
- comisiones aprobadas.
- comisiones liquidadas.
- comisiones pagadas.
- comisiones retenidas.
- comisiones revertidas.
- comisiones por vendedor top.
- porcentaje de comisión sobre ventas.
- comisión liberada por cobranza.

## 17. Eventos Futuros del Event Bus

Eventos recomendados:

- `commissions.entry.generated`
- `commissions.entry.earned`
- `commissions.entry.released`
- `commissions.entry.approved`
- `commissions.entry.held`
- `commissions.entry.reversed`
- `commissions.adjustment.created`
- `commissions.settlement.created`
- `commissions.settlement.approved`
- `commissions.settlement.paid`
- `commissions.simulation.executed`
- `commissions.recalculated`

Los payloads deben ser mínimos y seguros: ids, folios, importes agregados y estados. No deben incluir costos, márgenes, secretos ni rutas físicas.

## 18. Servicios Futuros

Servicios recomendados:

- `Service_Core_Commissions_Engine`
- `Service_Core_Commissions_EventMapper`
- `Service_Core_Commissions_RuleMatcher`
- `Service_Core_Commissions_Calculator`
- `Service_Core_Commissions_StageManager`
- `Service_Core_Commissions_PaymentRelease`
- `Service_Core_Commissions_ReversalManager`
- `Service_Core_Commissions_AdjustmentManager`
- `Service_Core_Commissions_SettlementManager`
- `Service_Core_Commissions_Simulator`
- `Service_Core_Commissions_ExplanationBuilder`
- `Service_Core_Commissions_ReportReader`
- `Service_Core_Commissions_AuditLogger`

Los controladores deben orquestar y delegar reglas a estos servicios.

## 19. Permisos Futuros

Permisos recomendados:

- `commissions.access[view]`: consultar.
- `commissions.access[create]`: generar.
- `commissions.access[edit]`: configurar planes y reglas.
- `commissions.access[authorize]`: aprobar, retener, revertir o ajustar.
- `commissions.access[pay]`: marcar liquidaciones como pagadas.
- `commissions.access[simulate]`: ejecutar simulador.
- `commissions.access[recalculate]`: recalcular o reparar.
- `commissions.access[export]`: exportar reportes.
- `commissions.access[margin]`: usar o ver cálculos basados en margen.

## 20. Riesgos de Negocio

- Pagar comisión antes de cobrar puede afectar flujo de efectivo.
- Pagar sobre total con impuestos puede inflar comisiones.
- Exponer margen puede revelar información sensible.
- Recalcular sin snapshot puede alterar históricos.
- Cancelaciones y notas de crédito sin reversa pueden duplicar beneficios.
- Liquidaciones sin aprobación pueden generar pagos indebidos.
- Reglas ambiguas pueden pagar doble.
- No auditar ajustes abre riesgo operativo y financiero.

## 21. Roadmap de Implementación

### Phase 3B: Servicios base

- Crear servicios del motor.
- Mover lógica del controlador a servicios.
- Crear contratos internos de evento, regla, cálculo y explicación.
- Sin conectar pagos todavía.

### Phase 3C: Generación por factura y eventos

- Integrar `invoice_issued`.
- Integrar `sales_order_created` y `order_delivered` si aplica.
- Emitir eventos de comisiones.

### Phase 3D: Liberación por pago

- Leer `core_payment_allocations`.
- Liberar proporcionalmente.
- Soportar pago parcial, reversa y saldo pendiente.

### Phase 3E: Liquidaciones

- Flujo `earned` a `approved` a `settled` a `paid`.
- Vincular liquidación con pago cuando proceda.
- Reglas de cierre y auditoría.

### Phase 3F: Reportes

- Reportes operativos y exportables.
- Filtros por vendedor, periodo, cliente, estado y origen.

### Phase 3G: Simulador

- Simulación sin escritura.
- Panel de explicación de reglas.

### Phase 3H: KPIs en Administración Comercial

- KPIs de comisiones por estado.
- Top vendedores por comisión.
- Alertas de reglas ambiguas y comisiones retenidas.

## 22. Validaciones Requeridas

- Validar generación sin duplicados.
- Validar reglas acumulables y exclusivas.
- Validar exclusiones.
- Validar pagos parciales.
- Validar reversas.
- Validar cancelaciones.
- Validar notas de crédito.
- Validar liquidaciones por periodo.
- Validar usuarios sin permiso.
- Validar ocultamiento de margen sin permiso.
- Validar que no se modifiquen comisiones históricas al cambiar reglas.
- Validar que Event Bus no bloquee la operación principal.

