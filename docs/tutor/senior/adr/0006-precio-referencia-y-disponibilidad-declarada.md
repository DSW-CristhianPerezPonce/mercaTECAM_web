# ADR-0006: Precio de referencia y disponibilidad declarada

**Estado**: Propuesta, requiere decisión de negocio

Este ADR documenta una decisión **implícita** en el código y propone fijarla antes del checkout. El texto es de análisis, no un acuerdo del equipo.

## Contexto

- Hechos: el producto guarda `precio_referencia` (CHECK >= 0, `database/schema.sql:25`) y `disponibilidad` (CHECK >= 0, `database/schema.sql:27`). El Excel de origen lo llama "Precio Estimado (COP)" (`data/productos.csv`).
- El carrito suma precio por cantidad en el navegador (`assets/js/carrito.js:48` formatea; subtotales y total en `pintar()`, `:223-258`) y lo muestra línea por línea en el resumen como cantidad × precio de referencia (`:203`). Recorta la cantidad a la disponibilidad al agregar (`:97`) y al editarla (`:150-153`).
- El importador sobrescribe la disponibilidad al reimportar (`scripts/importar_productos.php:75-118`; hipótesis por el upsert).
- No existe reserva de inventario ni pedido.

## Decisión

Pendiente. **Propuesta** a debatir: mientras no haya checkout, mantener el estado actual y comunicar en la interfaz que el precio es de referencia (rotular el total como "Total estimado") y que la disponibilidad es declarada por el productor.

## Alternativas consideradas

| Alternativa | Pros | Contras |
|---|---|---|
| A. Precio de referencia, sin compromiso (hoy) | Flexible para productores; sin carga operativa | El comprador puede esperar el precio mostrado; "total" engañoso |
| B. Precio vinculante con snapshot en el pedido | Claridad para el comprador; base legal clara | Obliga a mantener precios al día; disputa si el productor no cumple |
| C. Precio confirmado por el productor después del pedido | Coherente con ventas de cercanía | Más fricción; flujo de confirmación y estados de pedido |
| D. Disponibilidad como stock real con reserva | Evita sobreventa | Requiere inventario vivo y coordinación con productores |
| E. Disponibilidad como capacidad estimada | Poco esfuerzo del productor | No se puede prometer entrega |

## Consecuencias

- **Si se mantiene A**: riesgo reputacional y legal por expectativas de precio; carrera entre compradores por la misma disponibilidad (sin reserva).
- **Si se elige B o D**: hay que construir en servidor las reglas hoy solo presentes en JS (ver [ADR-0002](0002-carrito-en-navegador.md)), un contexto de Pedidos e Inventario, y cambiar el importador para que no sobrescriba reservas.
- **Riesgos**: dinero como `float` (`src/ProductRepository.php:22`) frente a `NUMERIC(12,2)`; unidades libres sin conversión.
- Preguntas para negocio: ¿precio vinculante?, ¿disponibilidad es stock o capacidad?, ¿cantidades fraccionarias?, ¿se compra a mercaTECAM o a cada productor? Respuestas: pendiente de confirmar.
