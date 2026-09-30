# Conexión PASSBALL-Cup ↔ AFIHub (verificación de jugadores)

Documentación end-to-end de cómo PASSBALL-Cup valida si una matrícula está
inscrita en la actividad de fútbol, y qué variable define qué en cada lado.

## 1. Flujo completo

```
login.php (form matrícula)
  -> POST /api/auth/login              (routes/api.php)
  -> AuthController::login()           (backend/controllers/AuthController.php)
  -> registrarUsuario() / login normal
  -> datosJugadorSiEstaInscrito()      (backend/services/jugadores_service.php)
  -> HTTP GET (cURL) -----------------> AFIHub: controllers/publico_verificar_inscripcion.php
                                        <- valida X-Api-Key y Origin
                                        <- consulta BD afihub (inscripciones_afi)
  <- JSON {success, estudiante}
  -> usuarios.rol = 'jugador' | 'usuario' (se guarda en BD passballcup)
```

## 2. Archivos involucrados y su función

### Lado PASSBALL-Cup

| Archivo | Función |
|---|---|
| [login.php](../../login.php) | Formulario público; envía matrícula a `/api/auth/login` vía JS (`assets/js/login.js`). No conoce nada de AFIHub. |
| [routes/api.php](../routes/api.php) | Define la ruta `POST /api/auth/login` → `AuthController::login`. También expone `GET /api/test/jugador` (solo en local) para probar la integración sin pasar por login completo. |
| [controllers/AuthController.php](../controllers/AuthController.php) | Orquesta el login: busca el usuario por matrícula; si no existe, llama a `registrarUsuario()`, que a su vez llama a `datosJugadorSiEstaInscrito()` para decidir el `rol` (`jugador` vs `usuario`) y guardar nombre/apellidos/semestre. |
| [services/jugadores_service.php](../services/jugadores_service.php) | Único punto que sabe hablar con AFIHub. Arma la URL (`AFI_BASE_URL` + `afi_id` fijo + `matricula`), manda los headers `Origin` y `X-Api-Key`, interpreta la respuesta JSON y hace *fail-safe*: si AFIHub falla o no responde, devuelve `null` (el login sigue funcionando, solo no asigna rol de jugador). |
| [config/app.php](../config/app.php) | Carga `.env` y define los defaults de entorno, incluyendo las 3 variables de la integración (`AFI_BASE_URL`, `AFI_ORIGIN`, `AFI_API_KEY`), eligiendo local o producción según `APP_ENV`. |
| [controllers/TestController.php](../controllers/TestController.php) | Método `jugador()`: expone `GET /api/test/jugador?matricula=...` para probar `datosJugadorSiEstaInscrito()` de forma aislada. Bloqueado si `APP_ENV === 'production'`. |
| `.env` (raíz del proyecto, fuera de git) | Contiene los valores reales: `DB_*`, `APP_ENV`, `APP_DEBUG`, `AFI_API_KEY`. |

### Lado AFIHub (proyecto externo, `C:\xampp\htdocs\AFIhub`)

| Archivo | Función |
|---|---|
| `controllers/publico_verificar_inscripcion.php` | Endpoint público que recibe `afi_id` + `matricula` por GET. Valida `X-Api-Key` (obligatorio) y `Origin` (whitelist, solo para CORS de navegador). Consulta `inscripciones_afi` y responde JSON con los datos del estudiante o 403/400/500. |
| `config/secrets.php` | Define la constante `AFI_API_KEY`. **No tiene `.env`**, así que el secreto vive hardcodeado aquí como constante PHP. Está en `.gitignore` para no subirse al repo. |
| `config/database.php` | Conexión PDO a la BD `afihub` (credenciales también hardcodeadas directamente en el archivo, mismo patrón que `secrets.php`). |

## 3. Variables que intervienen (mapa completo)

| Variable | Dónde vive | Valor local | Valor producción | Quién la usa |
|---|---|---|---|---|
| `AFI_ID_FUTBOL` | Constante en `jugadores_service.php` (hardcodeada, no es env) | `30` | `30` (mismo id en ambas BD, ya que AFIHub es la misma BD para ambos entornos salvo que se migre) | PASSBALL-Cup, para armar la URL de consulta |
| `AFI_BASE_URL` | `.env` de PASSBALL-Cup (con default en `config/app.php` si no está en `.env`) | `http://localhost/AFIhub/controllers/publico_verificar_inscripcion.php` | `https://passballcup.encuestapassword2026.com/api/publico_verificar_inscripcion.php` | PASSBALL-Cup, URL destino del cURL |
| `AFI_ORIGIN` | `.env` de PASSBALL-Cup (con default en `config/app.php`) | `http://localhost` | `https://passballcup.encuestapassword2026.com` | PASSBALL-Cup, valor que manda como header `Origin` |
| `AFI_API_KEY` | `.env` de PASSBALL-Cup **y** `config/secrets.php` de AFIHub (deben coincidir char por char) | mismo string en ambos lados | mismo string en ambos lados (idealmente un valor **distinto** al de local, ver sección 4) | Ambos: PASSBALL-Cup la manda en `X-Api-Key`, AFIHub la compara con `hash_equals()` |
| `APP_ENV` | `.env` de PASSBALL-Cup | `local` | `production` | Decide qué default toman `AFI_BASE_URL`/`AFI_ORIGIN`, y si `/api/test/jugador` queda habilitado (se bloquea en producción) |
| `DB_HOST/NAME/USER/PASS` | `.env` de PASSBALL-Cup | XAMPP local | credenciales del hosting real | Conexión a la BD `passballcup` (no interviene en la llamada a AFIHub, pero es donde se guarda el `rol` resultante) |
| `$host/$dbname/$username/$password` (`afihub`) | Hardcodeado en `config/database.php` de AFIHub | `localhost` / `afihub` / `root` / `''` | credenciales reales del hosting de AFIHub | Conexión de AFIHub a su propia BD, para resolver si la matrícula está inscrita |

### 🔑 Diferencia clave: PASSBALL-Cup usa `.env`, AFIHub no

- **PASSBALL-Cup** carga `.env` con `cargarEnv()` en `config/app.php` y todo pasa por `$_ENV[...]`. Cambiar de entorno es cambiar el archivo `.env` (no se toca código).
- **AFIHub** no tiene loader de `.env`: sus "variables de entorno" son **constantes PHP hardcodeadas** directamente en `config/secrets.php` y `config/database.php`. Para cambiar de entorno en AFIHub hay que **editar esos archivos directamente en el servidor** (no hay un único `.env` que conmute todo). Esto implica más cuidado: cualquier cambio de clave/URL en AFIHub se hace a mano en el archivo correspondiente, y ese archivo nunca se sube a git (ver `.gitignore` de AFIHub, que ya ignora `cache/`, `vendor/`, etc. — confirmar que `config/secrets.php` esté cubierto o añadirlo explícitamente).

## 4. Implementación en producción — checklist

1. **Generar una API key de producción distinta a la de local** (no reutilizar la de desarrollo). Usar algo con suficiente entropía, ej. `bin2hex(random_bytes(32))`.
2. **PASSBALL-Cup**: en el `.env` del servidor de producción, definir:
   ```
   APP_ENV=production
   APP_DEBUG=0
   AFI_API_KEY=<la-key-de-produccion>
   ```
   No hace falta definir `AFI_BASE_URL`/`AFI_ORIGIN` a mano: al poner `APP_ENV=production`, `config/app.php` ya toma los defaults de producción. Solo hay que sobreescribirlos en `.env` si la URL real de AFIHub en producción cambia respecto a la que está hardcodeada como default.
3. **AFIHub**: en el servidor de producción, editar `config/secrets.php` con la misma API key de producción (`define('AFI_API_KEY', '<la-key-de-produccion>');`). Confirmar que `config/database.php` apunte a la BD real de producción, no a `localhost`.
4. **Confirmar `$ALLOWED_ORIGINS`** en `publico_verificar_inscripcion.php` de AFIHub: debe incluir el dominio real de producción de PASSBALL-Cup (`https://passballcup.encuestapassword2026.com`), no solo `localhost`.
5. **HTTPS obligatorio en producción**: la URL de `AFI_BASE_URL` en producción debe ser `https://`, y el header `Origin` que manda PASSBALL-Cup debe ser el dominio `https://` real, exactamente igual (sin slash final, sin puerto) a como está en `$ALLOWED_ORIGINS` de AFIHub.
6. **Verificar que `/api/test/jugador` quede bloqueado** en producción: ya lo está porque `TestController::jugador()` corta con 404 si `APP_ENV === 'production'`; confirmar que el `.env` de producción realmente tenga `APP_ENV=production` (si falta, por default en `config/app.php` cae a `'local'` y el endpoint de prueba quedaría expuesto).
7. **No confundir `afi_id` con `fecha_id`**: si en producción cambia el id de la actividad de fútbol en la BD de AFIHub, hay que actualizar la constante `AFI_ID_FUTBOL` en `services/jugadores_service.php` (actualmente `30`; ver bug histórico donde estaba mal puesta en `330`, que era el `fecha_id` del horario).
8. **Probar en producción** con el mismo patrón usado en local:
   - Llamar directo a AFIHub con `curl` + `X-Api-Key` real, confirmar 200 con una matrícula real inscrita.
   - Confirmar que sin `X-Api-Key` responda 403.
   - Hacer login real desde `login.php` en producción con una matrícula inscrita y verificar en BD que `usuarios.rol = 'jugador'`.
9. **Rotar la key si se filtra**: cambiarla en ambos `.env`/`secrets.php` al mismo tiempo (un despliegue sincronizado, ya que si solo se cambia de un lado la integración se rompe con 403).
