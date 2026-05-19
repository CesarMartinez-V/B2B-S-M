# Portal B2B Inversiones S&M

Frontend Laravel/Vue para portal de cliente B2B con backend Laravel funcionando como bridge/API adapter interno.

El portal no es dueño de los datos reales del negocio. Catalogo, marcas, pedidos, facturas, cotizaciones, estado de cuenta y perfil deben venir de una API/plataforma externa futura. Mientras esa integracion no exista, el proyecto usa mocks temporales y contratos JSON estables.

## Stack

- PHP `^8.3`
- Laravel `^13.8`
- Vue `^3.5`
- Vite `^8`
- Estilos CSS propios, sin framework visual adicional

## Instalacion Local

Si las dependencias no existen localmente:

```bash
composer install
npm install
```

Configurar `.env` segun el entorno local si todavia no existe:

```bash
copy .env.example .env
php artisan key:generate
```

Nota: no se requieren migraciones para los datos del portal B2B en esta fase. Los datos comerciales reales pertenecen a una plataforma externa futura.

## Comandos Basicos

Servidor Laravel:

```bash
php artisan serve
```

Servidor Vite:

```bash
npm run dev
```

Build de frontend cuando corresponda validarlo:

```bash
npm run build
```

Tests Laravel cuando corresponda validarlos:

```bash
php artisan test
```

## Rutas Principales

- `/`: redirige a `/login`.
- `/login`: login B2B con autenticacion mock local.
- `/panel`: panel principal.
- `/catalogo`: catalogo y carrito temporal de cotizacion.
- `/marcas`: marcas.
- `/pedidos`: pedidos.
- `/estado-de-cuenta`: estado de cuenta.
- `/cotizaciones`: cotizaciones temporales.
- `/perfil`: perfil B2B.
- `/historial-facturas`: historial de facturas.

## Endpoints API Internos

Todos los endpoints viven dentro de Laravel y son consumidos por Vue mediante `resources/js/services/*`.

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

Forma base esperada:

```json
{
  "data": {},
  "meta": {
    "source": "mock",
    "generated_at": "ISO-8601"
  }
}
```

## Arquitectura Bridge

Flujo actual:

```txt
Vue pages -> frontend services -> /api/portal/* -> Portal controllers -> Portal services -> PortalDataGateway -> mock temporal
```

Piezas principales:

- `routes/web.php`: rutas SPA servidas por Laravel.
- `routes/api.php`: API interna del portal.
- `resources/js/App.vue`: seleccion de pantalla segun pathname.
- `resources/js/components/portal/AppShell.vue`: shell desktop/mobile compartido.
- `resources/js/services/apiClient.js`: cliente HTTP interno.
- `resources/js/services/dataAdapter.js`: modo fallback API/mock.
- `app/Http/Controllers/Portal/*`: controllers JSON.
- `app/Services/Portal/*`: normalizacion, filtros y contratos para Vue.
- `app/Services/Portal/Contracts/PortalDataGateway.php`: contrato del bridge.
- `app/Services/Portal/Gateways/MockPortalDataGateway.php`: gateway temporal mock.
- `app/Services/Portal/Gateways/ExternalPortalDataGateway.php`: scaffold para integracion externa futura.
- `app/Data/Portal/PortalMockData.php`: fixtures temporales backend.

## Mock Temporal

- Existe solo para que el portal pueda navegarse antes de la API externa.
- No persiste datos reales.
- No representa el sistema dueño de inventario, facturacion, pedidos o clientes.
- Los POST temporales devuelven `persisted: false`.
- No se deben crear migraciones para datos que pertenecen a la plataforma externa sin una decision de arquitectura.

## Integracion Futura

`ExternalPortalDataGateway` debe ser el unico punto de acoplamiento con la plataforma externa. La integracion real debe definir:

- Base URL.
- Autenticacion y headers.
- Endpoints reales por modulo.
- Timeouts, reintentos y manejo de errores.
- Fallback a mock si se decide soportarlo.
- Mapeo de campos externos al contrato estable de Vue.
- Paginacion y filtros reales.
- Valor de `meta.source` para distinguir `mock`, `external` o `external-fallback`.

## Checklist Manual

- Levantar `php artisan serve`.
- Levantar `npm run dev`.
- Abrir `/login`.
- Iniciar sesion mock.
- Revisar `/panel`.
- Revisar `/catalogo`, filtros y carrito temporal.
- Revisar `/cotizaciones` y creacion desde carrito.
- Revisar `/pedidos`.
- Revisar `/estado-de-cuenta`.
- Revisar `/historial-facturas`, filtros y acciones visuales.
- Revisar `/perfil`.
- Revisar `/marcas`.
- Cambiar modo claro/oscuro.
- Probar desktop, tablet y mobile basico.
- Probar `GET /api/portal/catalog`.
- Probar `GET /api/portal/invoices?status=Pagada`.
- Probar `GET /api/portal/quotes?status=approved`.
- Confirmar que las respuestas API tengan `{ data, meta }`.
- Confirmar que no se esperan datos reales todavia.
