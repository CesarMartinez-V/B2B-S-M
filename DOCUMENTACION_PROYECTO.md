# Memoria del proyecto

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

## Integracion ERP local por cookie de sesion o login dev

Esta configuracion es solo para desarrollo local. Permite que el bridge Laravel consuma endpoints protegidos del ERP local sin hardcodear credenciales en el codigo. En produccion se debe usar una API key, service account formal o integracion server-to-server definida por el ERP.

Variables disponibles en `.env` del portal:

```env
ERP_BASE_URL=http://localhost:8001
ERP_USERNAME=""
ERP_PASSWORD=""
ERP_LOGIN_URL=/login
ERP_COOKIE=""
ERP_XSRF_TOKEN=""
ERP_TIMEOUT=15
```

Prioridad de autenticacion:

- Si `ERP_COOKIE` existe, el bridge usa esa cookie manual.
- Si no existe `ERP_COOKIE` pero existen `ERP_USERNAME` y `ERP_PASSWORD`, el bridge intenta login automatico local contra `ERP_LOGIN_URL`.
- Si ninguna opcion existe o falla, cae a `mock-fallback` sin romper el catalogo.

Modo automatico local:

- Definir credenciales solo en `.env` local del portal:

```env
ERP_USERNAME="correo-o-usuario-local"
ERP_PASSWORD="password-local"
ERP_LOGIN_URL=/login
```

- Limpiar configuracion/cache:

```bash
php artisan config:clear
php artisan cache:clear
```

- Probar:

```txt
http://127.0.0.1:8000/api/portal/catalog?page=1&per_page=8
```

El bridge hara `GET /login`, extraera CSRF/cookies iniciales, enviara `POST /login` con `email` o `username`, guardara temporalmente la cookie en cache y luego consumira `/products/getProducts`.

Modo manual por cookie:

- Entrar al ERP en `http://localhost:8001`.
- Abrir DevTools > Application > Cookies.
- Copiar `XSRF-TOKEN`.
- Copiar la cookie de sesion disponible, normalmente `laravel_session` o `fastbi_session`.
- Pegar en `.env` del portal sin commitear valores reales:

```env
ERP_COOKIE="XSRF-TOKEN=...; laravel_session=..."
ERP_XSRF_TOKEN="..."
```

Despues de cambiar `.env`, ejecutar:

```bash
php artisan config:clear
php artisan cache:clear
```

Probar:

```txt
http://127.0.0.1:8000/api/portal/catalog?page=1&per_page=8
```

Resultado esperado:

- `meta.source = external` si ERP respondio directamente.
- `meta.source = external-cache` si se uso cache read-only del bridge.
- `meta.source = mock-fallback` si no hay sesion valida ni cache disponible.

Si aparece `mock-fallback`, revisar `storage/logs/laravel.log`. Los logs deben mostrar `cookie_present`, `xsrf_present`, `status`, `content_type` y `endpoint`, pero nunca imprimen el valor real de la cookie ni del token. Si el ERP devuelve `401`, renovar la cookie desde DevTools y limpiar config/cache de Laravel.

Los logs de login automatico muestran `login_attempt`, `login_success`, `cookie_present`, `xsrf_present` y `status`, pero nunca imprimen usuario, password, cookie ni token.

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
