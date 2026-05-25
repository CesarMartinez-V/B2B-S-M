# Memoria del proyecto

## Fase funcional de botones y flujos B2B

- Se centralizaron acciones seguras en `resources/js/composables/usePortalActions.js` para soporte, contacto con vendedor, documentos pendientes, navegacion interna, impresion segura y explicacion de integraciones ERP futuras.
- El carrito de cotizacion temporal vive en `portal-quote-cart` mediante `quoteCartStore.js` y guarda solo datos necesarios del producto seleccionado: id, sku, nombre, marca, categoria, cantidad, disponibilidad y precio publico interno para calcular totales.
- El carrito permite agregar, quitar, editar cantidad, vaciar, contar productos y calcular total. Si existe disponibilidad maxima, no permite superar esa cantidad.
- El catalogo no muestra precios ni unidades, pero usa `priceValue` y `availableQty` internamente para preparar la solicitud.
- En `/catalogo`, los botones `Cotizar` agregan productos disponibles al carrito temporal. El CTA flotante navega por SPA a `/cotizaciones` sin crear documentos reales.
- En `/cotizaciones`, la cotizacion temporal se puede revisar, editar, vaciar y enviar como `Solicitud preparada` local. Esta accion no crea proforma, factura, cotizacion ni invoice real en Fastevo.
- Las cotizaciones reales siguen llegando desde `GET /api/portal/quotes`. Las solicitudes temporales locales se guardan en `sm_quotes_temp` y se mezclan visualmente sin modificar ERP.
- `Enviar solicitud` marca la solicitud local como preparada y muestra el mensaje: `La creacion real en ERP queda pendiente de aprobacion`.
- Los botones de panel navegan a rutas reales cuando existe flujo read-only: catalogo, estado de cuenta, historial, pedidos y cotizaciones. Acciones de reportes/filtros futuros muestran modal de integracion ERP pendiente.
- En pedidos, los documentos comerciales y guias no exponen `guide_url` ni rutas internas. Si no hay endpoint seguro, abren modal explicando que se requiere un endpoint seguro de documentos en Fastevo.
- Estado de cuenta e historial exportan una impresion basica segura de la vista actual usando escape HTML en `usePdfExport.js`.
- Perfil registra solicitudes locales de edicion, muestra seguridad visual y deja cambio de clave como integracion pendiente con auth real.
- Marcas permite navegar al catalogo filtrando por marca cuando hay productos locales coincidentes; si no, muestra modal de disponibilidad.
- Login mantiene sesion temporal, y privacidad, terminos, soporte, solicitar acceso, recuperar clave y biometria abren modales claros. No implementa auth real.
- Ninguna accion de esta fase llama `vendor-inventory/create-proforma`, `create-proforma`, crea facturas reales, crea proformas reales ni modifica documentos reales en ERP.

Pruebas manuales sugeridas:

- Catalogo: agregar producto disponible, verificar contador, tocar `Cotizar`, llegar a `/cotizaciones`.
- Cotizaciones: editar cantidad, quitar producto, vaciar, seguir cotizando y enviar solicitud temporal.
- Pedidos: abrir detalle, intentar documentos, contactar soporte y ver mapa sin coordenadas.
- Estado de cuenta: filtros, cargar mas, exportar PDF basico, ver comprobante y contactar vendedor.
- Historial: buscar, filtrar, paginar, ver detalle, exportar y contactar vendedor.
- Perfil: solicitar edicion, revisar seguridad y cambio de clave visual.
- Login: recuperar clave, solicitar acceso, soporte, privacidad, terminos, biometria e ingreso temporal.

## Objetivo

Proyecto B2B construido con Laravel y Vue. Laravel sirve la SPA y expone una API interna estable para Vue.

Este portal NO es dueño de los datos reales del negocio. Los datos reales de catálogo, facturas, pedidos, cotizaciones, estado de cuenta y perfil deben venir desde otra plataforma/modulo externo mediante API.

El backend Laravel de este proyecto funciona como bridge/adapter/normalizador entre Vue y esa plataforma externa futura. Mientras la integración externa no exista, se usa un mock backend temporal para mantener contratos JSON estables.

## Decisiones iniciales

- Laravel sirve la aplicacion y define rutas web.
- Vue renderiza la interfaz del login.
- No se agregan frameworks visuales ni librerias de UI.
- Los textos visibles del frontend se centralizan en `resources/js/PalabrasWeb.js` para evitar texto hardcodeado dentro de los componentes.
- Vue no debe conectarse directamente a la API externa. Vue debe consumir `/api/portal/*` dentro de este Laravel.
- Laravel debe normalizar las respuestas externas para entregar a Vue contratos estables `{ data, meta }`.
- No se deben crear migraciones/tablas locales para datos que pertenecen a la plataforma externa hasta definir la integración real.
- El mock backend actual es temporal y no representa persistencia ni fuente de verdad.

## Arquitectura API bridge

- `routes/api.php`: endpoints internos del portal bajo `/api/portal/*`.
- `app/Http/Controllers/Portal/*`: controllers delgados que devuelven JSON estable.
- `app/Services/Portal/*`: services por modulo. Aplican filtros simples y preparan contratos para Vue.
- `app/Services/Portal/Contracts/PortalDataGateway.php`: contrato que define los datos requeridos por el portal.
- `app/Services/Portal/Gateways/MockPortalDataGateway.php`: gateway temporal que lee `PortalMockData`.
- `app/Services/Portal/Gateways/ExternalPortalDataGateway.php`: scaffold preparado para consumir la API externa futura y normalizar respuestas.
- `app/Data/Portal/PortalMockData.php`: datos mock temporales del backend. No es fuente real ni reemplazo de la plataforma externa.

Flujo esperado:

```txt
Vue services -> Laravel /api/portal/* -> PortalService -> PortalDataGateway -> Mock temporal o API externa futura
```

## Endpoints internos del portal

- `GET /api/portal/dashboard`
- `GET /api/portal/catalog`
- `GET /api/portal/brands`
- `GET /api/portal/orders`
- `GET /api/portal/account`
- `GET /api/portal/invoices`
- `GET /api/portal/quotes`
- `GET /api/portal/profile`
- `POST /api/portal/auth/identity`
- `POST /api/portal/quotes`
- `POST /api/portal/support/contact`

Todos deben responder con esta forma base:

```json
{
  "data": {},
  "meta": {
    "source": "mock",
    "generated_at": "ISO-8601"
  }
}
```

Cuando se conecte la plataforma externa, `meta.source` deberia pasar a algo como `external` o `external-fallback` segun corresponda.

## Integracion externa futura

`ExternalPortalDataGateway` debe ser el unico punto de acoplamiento con la otra plataforma. La integracion futura debe:

- Recibir filtros desde los services.
- Llamar los endpoints reales de la plataforma externa.
- Convertir nombres, estructuras, fechas, importes, estados e imagenes al contrato estable del portal.
- Manejar errores/timeouts sin exponer detalles externos a Vue.
- Permitir fallback al mock backend si la plataforma externa falla, si asi se decide.

No debe:

- Guardar datos reales localmente.
- Crear tablas para catálogo, facturas, pedidos, cotizaciones, estado de cuenta o perfil sin decisión de arquitectura.
- Duplicar reglas de negocio del sistema dueño de los datos.
- Implementar autenticación real en esta fase.

## Integracion Fastevo dev y APIs B2B

Catalogo, panel principal, pedidos, cotizaciones, historial de facturas, estado de cuenta y perfil no consumen endpoints web protegidos del ERP. `ExternalPortalDataGateway` llama APIs B2B read-only de Fastevo, que no requieren cookie de sesion, XSRF ni login automatico.

El navegador no llama Fastevo directamente. Vue consume solo `/api/portal/*`; Laravel Inversiones_S&M actua como bridge server-to-server hacia Fastevo, por lo que no hay dependencia de CORS entre el navegador y `dev.capgrupo.com`.

La URL base y rutas B2B publicas de Fastevo estan centralizadas en `config/portal.php`, no en `.env`:

```php
'fastevo' => [
    'base_url' => 'https://dev.capgrupo.com',
    'timeout' => 15,
    'paths' => [
        'auth_identity' => '/api/portal-b2b/auth/identity',
        'products' => '/api/portal-b2b/products',
        'invoices' => '/api/portal-b2b/invoices',
        'account' => '/api/portal-b2b/account',
        'profile' => '/api/portal-b2b/profile',
        'dashboard' => '/api/portal-b2b/dashboard',
        'orders' => '/api/portal-b2b/orders',
        'quotes' => '/api/portal-b2b/quotes',
    ],
],
```

### Login B2B temporal por identidad/RTN

- El login del portal pide `Numero de identidad o RTN` y envia el valor a `POST /api/portal/auth/identity`.
- Inversiones_S&M no consulta tablas locales de clientes. Actua como bridge y llama a Fastevo `POST /api/portal-b2b/auth/identity`.
- Fastevo valida contra `clients.vat_number`, normalizando el valor con `preg_replace('/\D+/', '', $identity)` para aceptar guiones, espacios, puntos y slash.
- Si no existe cliente activo o hay mas de un cliente con el mismo `vat_number` normalizado, Fastevo responde `authenticated=false` y no devuelve lista ni datos sensibles.
- Si existe exactamente un cliente valido, Fastevo devuelve `token`, `client.name`, `client.code` y `client.vatNumberMasked`. No devuelve `client_id` plano, `vat_number` completo, correo, telefono, direccion ni datos administrativos.
- Inversiones_S&M crea un token temporal interno con `Crypt::encryptString(json_encode(...))` que encapsula `fastevo_b2b_token`, `client_name`, `client_code`, `issued_at` y `expires_at`.
- El frontend guarda solo el token temporal interno en `sessionStorage` bajo `portal-auth-session` y lo envia en el header `X-Portal-Session` en cada request `/api/portal/*`.
- `ExternalPortalDataGateway` desencripta `X-Portal-Session`, extrae `fastevo_b2b_token` y llama Fastevo con `Authorization: Bearer <token>`. Vue sigue consumiendo solo `/api/portal/*`.
- Inversiones_S&M ya no envia `client_id` por query a Fastevo. Esto evita IDOR porque Fastevo resuelve el cliente desde el token B2B.
- Si Fastevo responde `401` o el token interno expira, el portal devuelve sesion expirada, limpia la sesion frontend y regresa a `/login`.
- Al iniciar sesion con otro cliente o al cerrar sesion se limpian caches de perfil, cuenta, facturas, pedidos, cotizaciones, dashboard, catalogo, carrito temporal y prefetch.
- Esta fase es temporal. Produccion debe agregar una segunda capa de seguridad: contrasena, OTP, correo, enlace firmado o autenticacion real del portal.
- Logs seguros: se permite `identity_present`, `identity_last4`, `token_valid`, `b2b_token_present`, `duplicate_detected` y tiempos. No se debe imprimir identidad completa, RTN completo, token ni `client_id` real.

Pruebas manuales del login temporal:

- Fastevo directo: `POST https://dev.capgrupo.com/api/portal-b2b/auth/identity` con identidad/RTN existente.
- Fastevo directo: repetir con identidad inexistente y con formato con guiones.
- Inversiones directo: `POST http://127.0.0.1:8000/api/portal/auth/identity` con identidad/RTN existente.
- UI: entrar a `http://127.0.0.1:8000/login`, ingresar identidad o RTN y confirmar navegacion a `/panel`.
- Despues del login validar `/api/portal/profile`, `/api/portal/account`, `/api/portal/invoices`, `/api/portal/dashboard`, `/api/portal/orders`, `/api/portal/quotes` y `/api/portal/catalog` usando el token B2B encapsulado.
- Confirmar en Fastevo que las llamadas llegan con `Authorization: Bearer <token>` y sin `client_id` por query.

Flujo actual de catalogo:

```txt
Vue /api/portal/catalog -> ExternalPortalDataGateway::catalog() -> Fastevo /api/portal-b2b/products -> cache/fallback del portal
```

Flujos actuales de facturas y estado de cuenta:

```txt
Vue /api/portal/invoices -> ExternalPortalDataGateway::invoices() -> Fastevo /api/portal-b2b/invoices -> fallback del portal
Vue /api/portal/account -> ExternalPortalDataGateway::account() -> Fastevo /api/portal-b2b/account -> fallback del portal
```

Optimizacion de paginacion y filtros:

- `GET /api/portal-b2b/invoices` acepta `include_filters=1|0`.
- Primera carga de historial usa `include_filters=1` para traer filtros dinamicos de estados, anios, rango de fechas y tipos de documento.
- Cambios de pagina del historial usan `include_filters=0` cuando los filtros ya estan en cache del frontend para evitar recalcular agregados.
- `GET /api/portal-b2b/account` acepta `include_summary=1|0` e `include_filters=1|0`.
- Primera carga de estado de cuenta usa `include_summary=1&include_filters=1`.
- `Cargar 20 mas` usa `include_summary=0&include_filters=0` para pedir solo la pagina de movimientos.
- Los filtros/resumenes se cachean en Fastevo por cliente con TTL corto, sin imprimir `client_id` real en logs.
- El selector de anios de historial muestra solo anios reales devueltos por backend; no existe opcion visual `Todos los años`.
- Para validar tiempos, comparar pagina 1 con filtros/resumen contra pagina 2 con flags en `0`:

Estas pruebas deben enviarse con `Authorization: Bearer <token>` y sin `client_id` por query.

```txt
/api/portal-b2b/invoices?page=1&per_page=20&include_filters=1
/api/portal-b2b/invoices?page=2&per_page=20&include_filters=0
/api/portal-b2b/account?page=1&per_page=20&include_summary=1&include_filters=1
/api/portal-b2b/account?page=2&per_page=20&include_summary=0&include_filters=0
```

Flujo actual de perfil:

```txt
Vue /api/portal/profile -> ExternalPortalDataGateway::profile() -> Fastevo /api/portal-b2b/profile -> fallback del portal
```

Flujo actual de panel principal:

```txt
Vue /api/portal/dashboard -> ExternalPortalDataGateway::dashboard() -> Fastevo /api/portal-b2b/dashboard -> cache/fallback del portal
```

Flujo actual de pedidos:

```txt
Vue /api/portal/orders -> ExternalPortalDataGateway::orders() -> Fastevo /api/portal-b2b/orders -> cache/fallback del portal
```

Flujo actual de cotizaciones:

```txt
Vue /api/portal/quotes -> ExternalPortalDataGateway::quotes() -> Fastevo /api/portal-b2b/quotes -> cache/fallback del portal
```

Para catalogo:

- No se usan credenciales, cookies ni XSRF del ERP legacy.
- No se llama `/products/getProducts`.
- Se conserva cache backend, last-known-good y `mock-fallback`.
- Se conserva el limite `per_page <= 100`.
- Los logs incluyen `endpoint`, `status`, `content_type`, `elapsed_ms`, `source` y `b2b_catalog_endpoint: true`.

Para panel principal, pedidos, cotizaciones, facturas, estado de cuenta y perfil:

- No se usan credenciales, cookies ni XSRF del ERP legacy.
- No se llama `/invoices/getInvoices`.
- No se llama `/receivables/getReceivables`.
- No se llama `/payments/getPayments`.
- No se llama `/clients/getClients`.
- No se llama `/purchase_orders/getPurchaseOrders`.
- No se llama `/orders/getOrders`.
- No se llama `/order_invoices/getOrderInvoices`.
- No se llama `/guides/getGuides` como fuente principal de pedidos.
- No se llama `/api/vendor-inventory/create-proforma`.
- No se crean cotizaciones ni proformas reales desde el portal en esta fase.
- Sin token B2B valido, Fastevo responde `401` y no expone dashboard, pedidos, cotizaciones, facturas, estado de cuenta ni perfiles globales.
- En produccion, el cliente se resuelve desde auth/token/session del portal, no desde un querystring publico.
- El perfil expone solo campos minimos seguros: contacto, empresa, codigo, correo, telefono, direccion, condicion comercial, credito y actividad reciente.
- El panel principal expone solo perfil resumido, KPIs financieros, actividad reciente de facturas/pagos, grafico mensual agregado y acciones rapidas.
- Pedidos usa `invoices` como fuente principal porque es la entidad con `client_id`; `guides`, `invoice_order_status`, `invoice_items` y `products` solo enriquecen el payload.
- Pedidos no expone `guide_url` directa; solo `guideUrlAvailable` hasta tener endpoint seguro de documentos.
- Cotizaciones usa `invoices` con `invoice_type_id=2` como fuente principal y `invoice_items`/`products` solo para items publicos.
- Cotizaciones excluye costos, comisiones, proveedores, usuarios internos, acciones HTML y URLs administrativas.
- El perfil excluye `created_by_id`, `user_id` interno, bancos, empleados, nomina, permisos, acciones HTML, URLs administrativas, passwords y tokens.

Resultado esperado:

- `meta.source = external` si Fastevo respondio directamente.
- `meta.source = external-cache` si se uso cache read-only del bridge.
- `meta.source = mock-fallback` si no hay respuesta B2B ni cache disponible.

Si aparece `mock-fallback` en catalogo, revisar `storage/logs/laravel.log`. Los logs del catalogo B2B deben mostrar `b2b_catalog_endpoint`, `status`, `content_type`, `elapsed_ms` y `endpoint`, pero nunca imprimen secretos.

Los logs de login automatico muestran `login_attempt`, `login_success`, `cookie_present`, `xsrf_present` y `status`, pero nunca imprimen usuario, password, cookie ni token.

## Estrategia de performance y cache del portal

El portal usa navegacion SPA interna sin `vue-router` para evitar recargas completas entre pantallas. Los enlaces internos mantienen `href` por accesibilidad, pero interceptan el click con `history.pushState()` y un estado reactivo global. Esto evita perder los stores en memoria al ir, por ejemplo, de `/catalogo` a `/marcas` y volver.

Rutas internas SPA:

- `/panel`
- `/catalogo`
- `/marcas`
- `/pedidos`
- `/estado-de-cuenta`
- `/historial-facturas`
- `/cotizaciones`
- `/perfil`

El cache frontend sigue el patron stale-while-revalidate:

- Si hay cache vigente, se muestra inmediatamente.
- Si hay cache vencido, se muestra inmediatamente y se refresca en background.
- Si no hay cache, se muestra skeleton/carga solo en la primera carga real.
- Se usa `inFlight` por cache key para evitar requests duplicados.

Stores actuales:

- `resources/js/stores/catalogStore.js`: cache en memoria y `sessionStorage` solo para paginas recientes del catalogo.
- `resources/js/stores/invoiceStore.js`: cache en memoria para historial de facturas.
- `resources/js/stores/accountStore.js`: cache en memoria para resumen de estado de cuenta.
- `resources/js/stores/profileStore.js`: cache en memoria de perfil con TTL mas largo.
- `resources/js/stores/quoteStore.js`: cache en memoria para cotizaciones.
- `resources/js/stores/orderStore.js`: cache en memoria para pedidos.
- `resources/js/stores/portalPrefetchStore.js`: precarga silenciosa post-login/entrada al portal.

`sessionStorage` se usa solo para catalogo y solo guarda paginas recientes, filtros, meta y timestamp. No se guardan cookies ERP, tokens, credenciales ni datos sensibles. Facturas, estado de cuenta, perfil, pedidos y cotizaciones usan cache en memoria por ahora. Al cerrar sesion temporal, se limpia el cache persistido del catalogo.

Prefetch post-login o al entrar al shell:

- Catalogo pagina 1 con `per_page=24`.
- Filtros iniciales/progresivos del catalogo.
- Historial de facturas pagina 1 y prefetch de pagina 2.
- Estado de cuenta resumen.
- Perfil.
- Cotizaciones pagina 1.
- Pedidos pagina 1.

El prefetch no bloquea el render y limita concurrencia a 2 tareas para no saturar ERP. Si el usuario entra directo a `/catalogo`, el catalogo tiene prioridad.

Catalogo:

- No se cargan los 9,604 productos al frontend.
- La pagina visible pide `per_page=24`.
- Las paginas de productos piden `include_filters=0` para no bloquearse recalculando filtros.
- Los filtros se piden por separado con `include_filters=1` y quedan cacheados en backend/frontend.
- La paginacion mantiene `meta.total` y `meta.last_page` reales desde ERP/cache.
- Se hace prefetch silencioso de pagina anterior y siguiente.
- El cache key incluye pagina, `per_page` y filtros activos.
- Los filtros se mantienen desde cache si un request de productos no los incluye.

Backend:

- `ExternalPortalDataGateway` capea `per_page` del catalogo hacia Fastevo B2B a maximo `100`.
- Si llega un valor mayor, se reduce y se loguea `Portal catalog per_page capped` sin datos sensibles.
- No debe existir `per_page=10000` en el gateway.
- Las respuestas de catalogo B2B read-only se cachean con TTL corto y ultimo valor bueno.
- Si Fastevo B2B falla, se usa `external-cache` si existe o `mock-fallback` como ultima defensa.
- Los logs de catalogo incluyen `elapsed_ms`, `status`, `content_type`, `endpoint`, `source` y `b2b_catalog_endpoint`, pero no imprimen secretos.
- Los logs de endpoints ERP legacy pueden incluir `cookie_present` y `xsrf_present`, pero catalogo no usa cookie ni XSRF.

Como probar tiempos:

- Abrir `/catalogo`; la primera carga puede depender del ERP, pero debe guardar cache.
- Ir a `/marcas` y volver a `/catalogo`; debe renderizar casi instantaneo sin recargar la app.
- Cambiar pagina 1 -> 2 -> 3 y volver a 1; las paginas cacheadas deben responder rapido.
- Ir a una pagina lejana; debe pedir solo esa pagina.
- Revisar consola por logs `[catalog]` y `[prefetch]`.
- Revisar `storage/logs/laravel.log` y confirmar que no aparece `per_page=10000`.

## Auditoria final de performance y estabilidad

Estado final: la optimizacion SPA + cache + prefetch queda estable para revision manual antes de PR. No se instalaron paquetes, no se tocaron migraciones, no se cambio `.env`, no se ejecuto build/tests y no se requiere `vue-router`.

Navegacion SPA sin `vue-router`:

- `resources/js/composables/usePortalNavigation.js` centraliza `currentPath`, `navigateTo()`, `normalizePath()` e `isInternalPortalPath()`.
- Las rutas internas usan `history.pushState()`; los botones Back/Forward del navegador actualizan `currentPath` por `popstate`.
- `App.vue` selecciona la pantalla desde `currentPath` sin recargar la app.
- Las rutas SPA cubiertas son `/panel`, `/catalogo`, `/marcas`, `/pedidos`, `/estado-de-cuenta`, `/historial-facturas`, `/cotizaciones` y `/perfil`.
- `/login` se mantiene como ruta publica.
- Si el usuario queda no autenticado en una ruta interna, `App.vue` reemplaza la URL por `/login` para evitar pantallas bloqueadas al usar Back despues de logout.
- El logout ejecuta `logout()`, limpia `portal.catalog.cache.v1` mediante `clearCatalogCache()` y navega a `/login`.

Cache catalogo:

- `catalogStore` mantiene cache en memoria por key de pagina, `per_page` y filtros.
- `inFlight` evita requests duplicados para la misma key.
- El TTL frontend del catalogo es de 7 minutos.
- La estrategia es stale-while-revalidate: cache vigente responde inmediato; cache vencido se muestra y refresca en background; sin cache se muestra carga inicial.
- `sessionStorage` solo se usa en `catalogStore` con la key `portal.catalog.cache.v1`.
- `sessionStorage` guarda paginas recientes, filtros, meta y timestamp; no guarda cookies ERP, XSRF, passwords, usuario ERP ni credenciales.
- El limite persistido es de 20 paginas recientes.
- Al cerrar sesion se elimina `portal.catalog.cache.v1`.

Prefetch:

- `portalPrefetchStore` corre una sola vez por sesion frontend con `state.started`.
- El prefetch se agenda con `requestIdleCallback` o `setTimeout`, por lo que no bloquea el render inicial.
- `runLimited()` limita la concurrencia a 2 tareas.
- Se precarga catalogo pagina 1, filtros de catalogo, facturas pagina 1 y pagina 2, estado de cuenta, perfil, cotizaciones y pedidos.
- Si se entra directo a `/catalogo`, el catalogo se encola primero.
- Los stores cacheados y los mapas `inFlight` reducen requests duplicados cuando una pantalla tambien pide sus datos al montar.

Limites ERP y fallback:

- `ExternalPortalDataGateway::perPage()` capea catalogo hacia ERP a maximo `100` y registra `Portal catalog per_page capped` si se solicita mas.
- El warm-up de filtros usa `FILTER_WARMUP_PER_PAGE = 100` y procesa maximo 5 paginas por ciclo.
- Facturas, receivables y payments tambien capean `per_page` a `100`.
- No debe existir `per_page=10000` en el codigo.
- El catalogo backend usa cache read-only con TTL corto y last-known-good por 6 horas.
- Si ERP responde, `source` es `external`; si se sirve cache, `source` es `external-cache`; si no hay ERP ni cache, `source` es `mock-fallback`.
- Los logs de ERP incluyen `endpoint`, `url`, `status`, `content_type`, `elapsed_ms`, `cookie_present` y `xsrf_present`.
- Los logs no imprimen valores de cookie, XSRF token, password ni usuario ERP.

Resultado de auditoria de codigo:

- No se detecto `per_page=10000` en PHP/JS/Vue.
- `sessionStorage` solo aparece en `catalogStore` para catalogo.
- Los stores nuevos de facturas, account, perfil, cotizaciones y pedidos estan usados por `portalPrefetchStore`; algunas pantallas aun leen desde adapters existentes, por lo que no se deben borrar esos stores.
- Los componentes legacy `components/navigation/*` siguen usados por `PortalLayout` y `PortalPage`, por lo que no se deben borrar sin una limpieza planificada.
- Se aplico una correccion segura en `App.vue` para redireccionar reactivamente a `/login` cuando una ruta interna queda no autenticada.

Checklist manual antes de PR:

- Abrir `/login`, iniciar sesion mock y confirmar navegacion a `/panel`.
- Navegar con sidebar/bottom nav por `/panel`, `/catalogo`, `/marcas`, `/pedidos`, `/estado-de-cuenta`, `/historial-facturas`, `/cotizaciones` y `/perfil`.
- Confirmar que entre rutas internas no hay reload completo de la app.
- Usar Back/Forward del navegador y confirmar que cambia la pantalla esperada.
- Hacer logout y confirmar que vuelve a `/login`.
- Despues de logout, usar Back y confirmar que no queda visible ninguna pantalla protegida.
- Abrir `/catalogo`, esperar primera carga y confirmar `sessionStorage['portal.catalog.cache.v1']`.
- Ir de `/catalogo` a otra pantalla y volver; confirmar respuesta inmediata o casi inmediata.
- Cambiar pagina 1 -> 2 -> 3 -> 1 y confirmar cache en consola con logs `[catalog]`.
- Ir a una pagina lejana y confirmar que solo pide esa pagina y vecinos.
- Confirmar que logout elimina `portal.catalog.cache.v1`.
- Revisar consola por `[prefetch] started` y `[prefetch] completed` una sola vez por sesion.
- Revisar Network para verificar que no haya requests duplicados por la misma key de catalogo.
- Revisar `storage/logs/laravel.log` y confirmar `elapsed_ms`, `source`, `cookie_present` y `xsrf_present` sin secretos.
- Confirmar que no aparece `per_page=10000`.
- Probar `/api/portal/catalog?page=1&per_page=1000&include_filters=0` y confirmar que backend responde con `per_page` capeado a `100`.

## Rutas

- `/`: redirige a `/login`.
- `/login`: muestra el login del Portal de Cliente B2B.
- `/panel`: muestra la base del Panel Principal.
- `/catalogo`: muestra la base del Catálogo.
- `/marcas`: muestra la base de Nuestras Marcas.
- `/pedidos`: muestra la base de Mis Pedidos.
- `/estado-de-cuenta`: muestra la base de Estado de Cuenta.
- `/cotizaciones`: muestra la base de Cotizaciones.
- `/perfil`: muestra la pantalla especifica de perfil B2B.
- `/historial-facturas`: muestra historial visual de facturas con datos mock.

## Archivos principales

- `routes/web.php`: rutas web iniciales.
- `resources/views/app.blade.php`: plantilla Blade minima que monta Vue en `#app`.
- `resources/js/app.js`: punto de entrada de Vue.
- `resources/js/App.vue`: selector de pantalla basado en la ruta actual sin agregar Vue Router.
- `resources/js/PalabrasWeb.js`: diccionario central de textos del frontend.
- `resources/js/pages/LoginPage.vue`: pantalla de login recreada desde la referencia visual.
- `resources/js/pages/PortalPage.vue`: pantalla base temporal para las secciones internas del portal.
- `resources/js/pages/InvoiceHistoryPage.vue`: pantalla especifica de historial de facturas.
- `resources/js/components/portal/AppShell.vue`: shell compartido para navegacion interna estandar.
- `resources/js/components/portal/PortalTopBar.vue`: topbar compartida con toggle claro/oscuro.
- `resources/js/components/portal/PortalSideBar.vue`: sidebar desktop compartida.
- `resources/js/components/portal/PortalBottomNav.vue`: bottom nav mobile compartida.
- `resources/js/components/portal/MobileTopBar.vue`: topbar mobile compartida.
- `resources/js/portalNavigation.js`: datos centralizados de marca, usuario y navegacion.
- `resources/js/layouts/PortalLayout.vue`: layout compartido del portal autenticado.
- `resources/js/components/navigation/TopBar.vue`: barra superior responsive.
- `resources/js/components/navigation/SideNav.vue`: navegación lateral desktop.
- `resources/js/components/navigation/BottomNav.vue`: navegación inferior mobile.
- `resources/css/app.css`: estilos globales base.
- `vite.config.js`: configuracion para compilar Laravel + Vue.
- `package.json`: scripts `dev` y `build` invocan Vite con `node` para evitar problemas de Windows cuando la ruta contiene `&`.
- `routes/api.php`: API interna del portal B2B.
- `app/Services/Portal/Contracts/PortalDataGateway.php`: contrato de datos del bridge.
- `app/Services/Portal/Gateways/MockPortalDataGateway.php`: gateway mock temporal.
- `app/Services/Portal/Gateways/ExternalPortalDataGateway.php`: gateway preparado para API externa futura.
- `app/Data/Portal/PortalMockData.php`: datos temporales para mock backend.

## Avance

- Se creo el proyecto Laravel base.
- Se configuro Vue dentro de Laravel.
- Se retiro Tailwind del flujo frontend para mantener estilos propios sin frameworks visuales.
- Se creo la primera pantalla frontend: login B2B oscuro con tarjeta central, campos, iconografia SVG y texto centralizado.
- Se configuro `.env` para no depender de base de datos: sesiones en archivos, cache en archivos y cola sincronica.
- Fase 1 del portal: se creo shell Vue responsive con TopBar, SideNav, BottomNav, rutas principales y tokens globales Glacier/liquid glass.
- Se extendio `PalabrasWeb.js` con navegación, acciones, rutas y contenido base para Panel, Catálogo, Marcas, Pedidos, Estado de Cuenta y Cotizaciones.
- Se estandarizo la navegacion interna con `AppShell` usando la navegacion de Perfil como patron unico.
- Se agrego modo claro/oscuro frontend con preferencia en `localStorage` y modo oscuro por defecto.
- Se agrego la vista `/historial-facturas` con tabla desktop y cards mobile estilo Glacier.
- Se ajustaron paddings responsive generales, Perfil, Mis Pedidos y Estado de Cuenta.
- Se compacto el espacio lateral del shell interno sin cambiar la navegacion estandar.
- Se redisenó `/historial-facturas` con hero liquid glass, metricas premium, tabla desktop y cards mobile mejoradas.
- Se agregaron animaciones CSS sutiles reutilizables con soporte para `prefers-reduced-motion`.
- Se mejoro la nitidez visual de imagenes en login y panel reduciendo escalado/filtros agresivos.
- Se corrigio el layout interno a full-width dashboard: `AppShell` ya no limita `.shell-content`, se redujeron paddings laterales y se eliminaron centrados/max-width restrictivos en Panel, Catalogo, Marcas, Pedidos, Estado de Cuenta, Cotizaciones, Historial de Facturas y Perfil.
- Se elevaron las tarjetas internas a un tratamiento liquid glass mas consistente con bordes translucidos, glows cyan/purple, sombras suaves y hover lift.
- Se agrego una primera API interna `/api/portal/*` con Laravel como bridge mock para que Vue no dependa directamente de mocks locales.
- Se agregaron services/gateways backend para separar controllers, normalizacion y fuente de datos.
- Se ajustaron services frontend a modo fallback: intentan Laravel API y conservan mocks locales si falla.

## Pendiente

- Definir la API externa real del otro modulo y mapearla dentro de `ExternalPortalDataGateway`.
- Definir estrategia de errores/timeouts y fallback entre gateway externo y mock temporal.
- Definir autenticacion/autorizacion real cuando la integracion externa lo requiera.
- Definir validaciones frontend si se requieren antes de conectar autenticacion.
- Ajustar textos desde `PalabrasWeb.js` cuando el cliente confirme copy final.
- Reemplazar las pantallas base internas por componentes finales específicos tomando como referencia Stitch.

## Reglas de datos

- No crear migraciones para catálogo, marcas, pedidos, estado de cuenta, historial de facturas, cotizaciones o perfil en esta fase.
- No guardar copias locales de datos reales de la plataforma externa.
- No convertir este portal en sistema dueño de inventario, facturacion, pedidos o clientes.
- Mantener los contratos JSON del portal estables aunque cambie la API externa.

## Cierre para PR

### Arquitectura final

- Laravel sirve la aplicacion Vue desde rutas web simples.
- Vue renderiza las pantallas del portal B2B y consume services frontend internos.
- Los services frontend apuntan a `/api/portal/*` mediante `resources/js/services/apiClient.js`.
- Laravel expone una API interna estable y actua como bridge/adapter/normalizador.
- Los datos reales no pertenecen a este repositorio; deben venir de una API/plataforma externa futura.
- Mientras esa API externa no exista, Laravel usa `MockPortalDataGateway` y `PortalMockData` como fallback temporal.
- El frontend mantiene fallback local para evitar pantallas rotas si la API interna falla durante desarrollo.

### Rutas web

- `/`: redirige a `/login`.
- `/login`: pantalla de login B2B con autenticacion mock local.
- `/panel`: panel principal.
- `/catalogo`: catalogo y carrito temporal de cotizacion.
- `/marcas`: marcas premium.
- `/pedidos`: pedidos.
- `/estado-de-cuenta`: estado de cuenta.
- `/cotizaciones`: gestion de cotizaciones temporales.
- `/perfil`: perfil B2B.
- `/historial-facturas`: historial visual de facturas.

### Rutas API

- `GET /api/portal/dashboard`: datos del panel.
- `GET /api/portal/catalog`: catalogo con filtros `search`, `category`, `brand`, `max_price`, `page`, `per_page`.
- `GET /api/portal/brands`: marcas.
- `GET /api/portal/orders`: pedidos.
- `GET /api/portal/account`: estado de cuenta con filtros `filter`, `period`, `limit`.
- `GET /api/portal/invoices`: facturas con filtros `query`, `status`, `from`, `to`, `page`, `per_page`.
- `GET /api/portal/quotes`: cotizaciones con filtros `search`, `status`, `archived`, `page`, `per_page`.
- `GET /api/portal/profile`: perfil.
- `POST /api/portal/quotes`: crea respuesta temporal de cotizacion, sin persistencia real.
- `POST /api/portal/support/contact`: crea respuesta temporal de soporte, sin persistencia real.

### Bridge Laravel

- `routes/api.php` define endpoints internos bajo `/api/portal/*`.
- `app/Http/Controllers/Portal/*` recibe requests y devuelve JSON.
- `app/Services/Portal/*` aplica filtros, paginacion simple y shape final para Vue.
- `PortalResponse::make()` estandariza `{ data, meta }`.
- `PortalDataGateway` define el contrato de datos requerido por el portal.
- `MockPortalDataGateway` entrega fixtures temporales.
- `ExternalPortalDataGateway` queda preparado como unico punto futuro para llamar la API externa y normalizar payloads.

### Mock temporal

- `app/Data/Portal/PortalMockData.php` contiene datos fixture para desarrollo.
- No representa una base de datos real.
- No es fuente de verdad.
- No debe extenderse como logica de negocio definitiva.
- No debe reemplazar a la API/plataforma externa.
- Los POST temporales devuelven `persisted: false` para evitar confundirlos con persistencia real.

### Pendiente para API externa

- Definir base URL, autenticacion, headers y credenciales.
- Definir endpoints reales para dashboard, catalogo, marcas, pedidos, estado de cuenta, facturas, cotizaciones, perfil y soporte.
- Mapear nombres de campos externos al contrato estable de Vue.
- Definir manejo de errores, timeouts, reintentos y fallback.
- Definir paginacion real y filtros soportados por cada endpoint externo.
- Definir `meta.source` para `external`, `mock` o `external-fallback`.
- Reemplazar autenticacion mock por autenticacion real cuando corresponda.
- Decidir si algun dato debe persistirse localmente; por defecto no se persisten datos del sistema externo.

### Checklist manual para PR

- Levantar Laravel con `php artisan serve`.
- Levantar Vite con `npm run dev`.
- Abrir `/login`.
- Iniciar sesion con el flujo mock.
- Confirmar redireccion/navegacion a `/panel`.
- Revisar `/panel` en desktop y mobile.
- Revisar `/catalogo`, buscar, filtrar y agregar producto al carrito temporal.
- Revisar `/cotizaciones`, crear cotizacion desde carrito temporal y probar acciones visuales.
- Revisar `/pedidos` en desktop y mobile.
- Revisar `/estado-de-cuenta`, filtros y layout responsive.
- Revisar `/historial-facturas`, filtros, detalle y exportacion visual.
- Revisar `/perfil`.
- Revisar `/marcas`.
- Cambiar modo claro/oscuro desde la topbar.
- Validar que no haya scroll horizontal accidental en mobile.
- Probar manualmente `GET /api/portal/catalog`.
- Probar manualmente `GET /api/portal/invoices?status=Pagada`.
- Probar manualmente `GET /api/portal/quotes?status=approved`.
- Confirmar que los endpoints devuelven `{ data, meta }`.
- Confirmar que no se esperan datos reales todavia.
- Confirmar que no se crearon migraciones ni tablas para datos externos.
