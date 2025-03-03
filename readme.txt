=== WooCommerce FTP Sync ===
Contributors: Ivan Bello
Tags: woocommerce, ftp, csv, import, sync, inventory
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.4
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Descripción ==
Este plugin permite la sincronización automática de productos en WooCommerce desde un archivo CSV alojado en un servidor FTP.

**Características:**
- Descarga un archivo CSV desde un servidor FTP.
- Actualiza automáticamente el stock, precio, descuento y clase de impuesto de los productos.
- Sincronización automática cada 30 minutos mediante WP-Cron.
- Botón manual en el panel de administración para sincronización inmediata.

== Instalación ==
1. Descarga el archivo ZIP del plugin.
2. Sube el archivo a tu sitio de WordPress en `Plugins > Añadir nuevo > Subir plugin`.
3. Activa el plugin desde el menú de Plugins en WordPress.
4. Configura las credenciales FTP en el archivo `wp-config.php`:
   ```php
   define('FTP_SYNC_SERVER', 'ftp.ejemplo.com');
   define('FTP_SYNC_USER', 'usuario');
   define('FTP_SYNC_PASS', 'contraseña');
   ```
5. El plugin comenzará a sincronizar productos automáticamente cada 30 minutos.

== Uso ==
- Para iniciar la sincronización manualmente, ve a `WC FTP Sync` en el panel de administración y haz clic en el botón `Sincronizar Ahora`.

== Registro de cambios ==
= 1.4 =
- Agregada sincronización automática cada 30 minutos con WP-Cron.
- Implementada lógica para aplicar descuentos.
- Se aplica la clase de impuesto "Reduced Rate" si el campo `tax` es mayor a 0.
- Mejoras en el manejo de errores y validaciones.

= 1.3 =
- Mejora en la estructura de actualización de productos.
- Manejo de errores en la descarga del archivo CSV.

= 1.2 =
- Agregado botón en el panel de administración para sincronización manual.
- Soporte para credenciales FTP desde `wp-config.php`.

= 1.1 =
- Correcciones menores en la importación de productos.

= 1.0 =
- Versión inicial del plugin.

