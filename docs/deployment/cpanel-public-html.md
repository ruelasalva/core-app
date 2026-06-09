# Despliegue cPanel con public_html

## Objetivo

CORE-APP debe cargar desde la raiz del dominio sin `/public`, manteniendo el codigo privado fuera del webroot.

Estructura objetivo:

```text
home/usuario/
  fuel/
  public_html/
    index.php
    .htaccess
    assets/
    favicon.ico
    manifest.json
    sw.js
```

## Que subir a public_html

Copiar el contenido de `public/` hacia `public_html/`:

```text
public/index.php
public/.htaccess
public/assets/
public/favicon.ico
public/manifest.json
public/sw.js
public/web.config
```

No copiar la carpeta `public/` completa como subcarpeta. El contenido debe quedar directamente dentro de `public_html/`.

## Que subir fuera de public_html

Subir fuera del webroot:

```text
fuel/
composer.json
composer.lock
oil
```

La ruta esperada por `public_html/index.php` es:

```text
../fuel/app
../fuel/packages
../fuel/core
```

Por eso `fuel/` debe quedar como hermano de `public_html/`.

## Que no subir a public_html

No subir a `public_html/`:

```text
fuel/
docs/
.git/
.agents/
.codex/
node_modules/
composer.json
composer.lock
composer.phar
oil
package.json
package-lock.json
AGENTS.md
AGENTS_old.md
README.md
TESTING.md
```

## Configuracion de entorno

CORE-APP detecta el ambiente asi:

1. `FUEL_ENV` si existe.
2. `localhost`, `127.0.0.1`, `.local`, `.test`, `.dev` como `development`.
3. Cualquier otro host como `production`.

En cPanel se recomienda configurar:

```text
FUEL_ENV=production
```

Si el panel no permite variables de entorno, el dominio publico se detectara como produccion.

## URLs esperadas

Produccion:

```text
https://dominio.com/
https://dominio.com/admin
https://dominio.com/assets/css/...
https://dominio.com/sw.js
https://dominio.com/manifest.json
```

Local:

```text
http://localhost/core-app/public
http://localhost/core-app/public/admin
```

## Seguridad

`fuel/` y `docs/` deben quedar fuera de `public_html/`.

El `.htaccess` incluye bloqueos defensivos por si accidentalmente se copian archivos privados al webroot, pero no debe usarse como unica proteccion.

## Validacion

Probar que funcionan:

```text
/
/admin
/clientes
/proveedores
/assets/
/sw.js
/manifest.json
```

Probar que no son accesibles:

```text
/fuel
/docs
/.git
/composer.json
/oil
/AGENTS.md
/fuel/app/config/config.php
/fuel/app/logs/
/fuel/app/migrations/
/fuel/app/tasks/
/fuel/app/storage/
```

## Checklist de carga

1. Subir `fuel/` fuera de `public_html/`.
2. Subir contenido de `public/` directamente a `public_html/`.
3. Confirmar que `public_html/index.php` existe.
4. Confirmar que `public_html/assets/` existe.
5. Confirmar que `public_html/fuel/` no existe.
6. Configurar `FUEL_ENV=production` si el panel lo permite.
7. Probar rutas publicas.
8. Probar rutas bloqueadas.
9. Probar login administrativo.
10. Probar carga de assets e imagenes.
11. Probar descargas controladas de documentos.
12. Revisar logs despues del primer acceso.
