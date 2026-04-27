# Instalación — Seshat Avalúos

Aplicación PHP (8.2 o superior) + MySQL. Pensada para hosting compartido con **LiteSpeed** o Apache.

## 1. Requisitos del servidor

- PHP **≥ 8.2** con extensiones: `pdo_mysql`, `json`, `mbstring`, `openssl`, `curl` (y las demás habituales del proyecto).
- **MySQL** o MariaDB.
- **Composer** en tu máquina de desarrollo o por SSH en el hosting (para generar `vendor/`).
- Opcional: cuenta **Google Cloud** + API Calendar (solo si usarás disponibilidad y eventos en Google Calendar).

## 2. Archivos en el hosting

Puedes usar **una de estas dos** estructuras.

### Opción A — Carpeta `public` (recomendada en desarrollo)

```
proyecto/
  public/          ← Document root del sitio (o subcarpeta)
    index.php
    .htaccess
  src/
  views/
  vendor/
  database/
  .env
```

Si el sitio es `https://tudominio.com/seshat` y el servidor **no** permite apuntar el document root solo a `public/`, sube el contenido de `public/` dentro de `seshat/` y el resto del proyecto **fuera** de la carpeta pública (más seguro) o sigue la opción B.

### Opción C — Subes **todo el repo** a `/seshat/` (queda `public/` como subcarpeta)

Es lo habitual si subes `src/`, `views/`, `vendor/`, `public/`, etc. En ese caso:

1. Debe existir el **`.htaccess` en la raíz del proyecto** (junto a `composer.json`): reenvía `/seshat/login` a `public/index.php`.
2. En **`.env`** define **`APP_BASE_PATH=/seshat`** (obligatorio aquí: si no, los enlaces podrían salir como `/seshat/public/...`).
3. Sube también **`.env`** (no solo `.env.example`) y revisa que **`RewriteBase /seshat/`** coincida en **los dos** `.htaccess` (raíz y `public/`) si cambia la ruta.

Sin el `.htaccess` de la raíz, `/seshat/login` no llega a `index.php` y el servidor responde **404**.

### Opción B — Todo lo público dentro de `/seshat/` (típico en hosting compartido)

Coloca en la carpeta que sirve la URL (ej. `public_html/seshat/`):

- `index.php` y `.htaccess` (copiados desde `public/`)
- Carpetas hermanas en el mismo nivel que `index.php`: `src/`, `views/`, `vendor/`, `database/`
- Archivo **`.env`** en ese mismo nivel (junto a `src/`)

El `index.php` incluido detecta la raíz del proyecto si `src/` está al lado de `index.php`.

## 3. Base de datos

1. Crea una base de datos y un usuario MySQL con permisos sobre esa base.
2. Importa el esquema desde phpMyAdmin o consola:

   ```text
   database/schema.sql
   ```

3. (Opcional) Datos de prueba con un valuador demo (contraseña: **`password`**):

   ```text
   database/seed_demo.sql
   ```

   En producción cambia ese usuario o elimínalo.

## 4. Composer

En la **raíz del proyecto** (donde está `composer.json`):

```bash
composer install --no-dev
```

Sube la carpeta **`vendor/`** completa al servidor si no puedes ejecutar Composer allí.

## 5. Configuración `.env`

1. Copia `.env.example` a **`.env`**.
2. Edita al menos:

| Variable | Descripción |
|----------|-------------|
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Conexión MySQL |
| `APP_URL` | URL pública base, ej. `https://consorcioempresarial.org/seshat` |
| `APP_BASE_PATH` | Prefijo de ruta sin barra final, ej. `/seshat`. Si `index.php` está en `/seshat/index.php`, muchas veces **se deduce solo**; definir `APP_BASE_PATH` evita dudas. |
| `APP_TIMEZONE` | Zona horaria, ej. `America/Chicago` |
| `APP_DEBUG` | En producción: `0` |

### Valuadores (registro)

- Si defines **`VALUADOR_REGISTER_SECRET`**, solo quien escriba esa clave en el formulario podrá registrarse como valuador.
- Si lo dejas vacío, el formulario solo permitirá registro como **cliente** (salvo que insertes valuadores a mano o uses `seed_demo.sql`).

### Google Calendar (opcional)

1. En [Google Cloud Console](https://console.cloud.google.com/) crea proyecto, habilita **Google Calendar API** y credenciales **OAuth 2.0 (aplicación web)**.
2. URI de redirección autorizada **exacta**, por ejemplo:

   `https://consorcioempresarial.org/seshat/google/callback`

3. En `.env`:

   - `GOOGLE_CLIENT_ID`
   - `GOOGLE_CLIENT_SECRET`
   - `GOOGLE_REDIRECT_URI` (la misma URL del paso 2)

4. Ejecuta **`composer install`** para tener la librería de Google en `vendor/`.

## 6. Apache / LiteSpeed (`.htaccess`)

En la carpeta donde está `index.php` debe existir el `.htaccess` del proyecto.

- Si la aplicación está en **`/seshat/`**, el archivo incluye **`RewriteBase /seshat/`**. Debe coincidir con la ruta real.
- Si publicas en la **raíz del dominio** (`https://tudominio.com/`), **comenta o elimina** la línea `RewriteBase /seshat/`.

Tras cambiar `.htaccess`, prueba de nuevo las rutas limpias (`/seshat/login`, etc.).

## 7. Permisos y seguridad

- El archivo **`.env`** no debe ser descargable públicamente (debe quedar **fuera** del document root si el hosting lo permite).
- En producción: **`display_errors = Off`** en PHP y `APP_DEBUG=0`.
- Elimina o protege **`diagnostico-hosting.php`** si lo usaste solo para diagnóstico.
- Usa **HTTPS** y contraseñas fuertes para los usuarios.

## 8. Comprobar la instalación

1. Abre la URL base (ej. `https://consorcioempresarial.org/seshat/`).
2. Debe cargarse la página de inicio.
3. Registro / inicio de sesión.
4. Crea un avalúo de prueba (necesitas al menos un usuario **valuador** en la base de datos).

## 9. Problemas frecuentes

| Síntoma | Qué revisar |
|---------|-------------|
| Error 500 al abrir cualquier URL | Logs del servidor, permisos de archivos, versión de PHP. |
| Página en blanco | `APP_DEBUG=1` temporalmente (solo en entorno de prueba) o revisar logs. |
| Enlaces van a la raíz del dominio sin `/seshat` | `APP_BASE_PATH` y `RewriteBase` en `.htaccess`. |
| “Error de conexión a la base de datos” | Credenciales y nombre de base en `.env`. |
| Google OAuth falla | `GOOGLE_REDIRECT_URI` idéntica en Google Cloud y en `.env`; HTTPS correcto. |
| Clase Google no encontrada | Falta `vendor/`; ejecuta `composer install`. |

## 10. Actualizaciones

1. Sube los archivos nuevos conservando `.env`.
2. Si hubo cambios en la base de datos, aplica las migraciones o SQL que se indiquen en las notas de versión.
3. Si Composer cambió dependencias: `composer install` de nuevo y sube `vendor/`.

---

Si la URL de instalación cambia respecto a `/seshat`, actualiza **`APP_URL`**, **`APP_BASE_PATH`**, **`RewriteBase`** en `.htaccess` y la **URI de redirección** en Google Cloud.

---

## 11. Composer en Windows (XAMPP) — SSL / Avast

Si `composer install` falla con **`curl error 60`** o menciona **Avast**:

1. **No uses `disable-tls` en Composer** (deja de verificar certificados y da avisos). Revisa el archivo global:
   - `%APPDATA%\Composer\config.json`  
   - Si ves `"disable-tls": true`, bórralo o ponlo en `false`. Puede quedar solo `"cafile"` apuntando a un `cacert.pem` actualizado.

2. **Certificados CA actualizados** (en la raíz del proyecto hay un `cacert.pem` descargado de [curl.se/ca/cacert.pem](https://curl.se/ca/cacert.pem)). Puedes enlazar Composer a él:
   ```powershell
   php composer.phar config --global cafile "C:\ruta\a\seshat\cacert.pem"
   ```

3. **Avast** (u otro antivirus): la “inspección HTTPS” sustituye certificados y provoca el error 60. Prueba:
   - **Desactivar temporalmente** la inspección HTTPS en Avast (*Protección* → *Núcleo escudos* → *Escudo web* → ajustes → desmarcar “Habilitar escaneo HTTPS” o equivalente según versión), **o**
   - Añadir la excepción / desactivar solo mientras ejecutas Composer.

4. **PHP de XAMPP**: en `C:\xampp\php\php.ini`, las líneas `curl.cainfo` y `openssl.cafile` suelen apuntar a `C:\xampp\apache\bin\curl-ca-bundle.crt`. Si sigue fallando, puedes probar a apuntarlas al mismo `cacert.pem` del proyecto (ruta absoluta).

5. En este repo tienes **`composer.phar`** y **`composer.bat`**. Desde la carpeta del proyecto:
   ```powershell
   .\composer.bat install --no-dev
   ```
   O:
   ```powershell
   C:\xampp\php\php.exe composer.phar install --no-dev
   ```

Si tras ajustar Avast y la configuración TLS sigue fallando, instala dependencias en **otro PC o en el hosting con SSH**, copia la carpeta **`vendor/`** y el archivo **`composer.lock`** (si existe) al proyecto.
