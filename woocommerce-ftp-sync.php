<?php
/*
Plugin Name: WooCommerce FTP Sync
Description: Sincroniza productos desde un archivo CSV alojado en un servidor FTP a WooCommerce.
Version: 1.4
Author: Ivan Bello
*/

if (!defined('ABSPATH')) {
    exit; // Evita acceso directo
}

class WooCommerce_FTP_Sync {
    private $ftp_server;
    private $ftp_user;
    private $ftp_pass;
    private $remote_file = '/PR-1900.CSV';
    private $local_file;

    public function __construct() {
        $this->ftp_server = defined('FTP_SYNC_SERVER') ? FTP_SYNC_SERVER : '';
        $this->ftp_user = defined('FTP_SYNC_USER') ? FTP_SYNC_USER : '';
        $this->ftp_pass = defined('FTP_SYNC_PASS') ? FTP_SYNC_PASS : '';

        $this->local_file = WP_CONTENT_DIR . '/uploads/prueba.csv';

        add_action('admin_menu', [$this, 'create_admin_page']);
        add_action('wp_ajax_sync_woocommerce_products', [$this, 'sync_products']);
        add_action('woocommerce_ftp_sync_cron', [$this, 'sync_products']);

        // Programar la tarea cron si no está programada
        if (!wp_next_scheduled('woocommerce_ftp_sync_cron')) {
            wp_schedule_event(time(), 'every_30_minutes', 'woocommerce_ftp_sync_cron');
        }
    }

    public function create_admin_page() {
        add_menu_page(
            'WooCommerce FTP Sync',
            'WC FTP Sync',
            'manage_options',
            'wc-ftp-sync',
            [$this, 'admin_page_content'],
            'dashicons-update'
        );
    }

    public function admin_page_content() {
        echo '<div class="wrap"><h1>Sincronización FTP WooCommerce</h1>';
        echo '<button id="sync-products" class="button button-primary">Sincronizar Ahora</button>';
        echo '<div id="sync-result"></div></div>';
        echo '<script>
            document.getElementById("sync-products").addEventListener("click", function() {
                fetch(ajaxurl + "?action=sync_woocommerce_products")
                .then(response => response.text())
                .then(data => {
                    document.getElementById("sync-result").innerHTML = data;
                });
            });
        </script>';
    }

    public function sync_products() {
        if (!$this->download_csv()) {
            echo '❌ Error al descargar el archivo CSV.';
            wp_die();
        }

        if (!file_exists($this->local_file)) {
            echo '❌ Error: El archivo CSV no existe en la ruta ' . esc_html($this->local_file);
            wp_die();
        }

        $csv_data = [];
        $handle = fopen($this->local_file, 'r');
        if ($handle !== false) {
            while (($row = fgetcsv($handle, 0, "|")) !== false) {
                $csv_data[] = $row;
            }
            fclose($handle);
        }

        if (empty($csv_data)) {
            echo '❌ Error: No se pudo leer el archivo CSV.';
            wp_die();
        }

        $headers = array_shift($csv_data);
        foreach ($csv_data as $index => $row) {
            if (count($row) !== count($headers)) {
                continue;
            }
            
            $data = array_combine($headers, $row);
            $sku = $data['isbn'] ?? null;
            $stock = (int) ($data['Stock1'] ?? 0);
            
            // Reemplazo de separadores de miles y decimales en el precio
            $price = str_replace('.', '', $data['Price'] ?? '0');
            $price = floatval(str_replace(',', '.', $price));

            // Aplicar descuento si existe
            $discount = isset($data['discount']) ? floatval($data['discount']) : 0;
            $sale_price = $discount > 0 ? $price - ($price * ($discount / 100)) : $price;

            // Aplicar clase de impuesto "Reduced Rate" si el valor de tax es mayor a 0
            $tax_class = isset($data['tax']) && intval($data['tax']) > 0 ? 'reduced-rate' : '';

            if (!$sku) {
                continue;
            }

            $product_id = wc_get_product_id_by_sku($sku);
            if ($product_id) {
                wp_update_post(['ID' => $product_id, 'post_title' => sanitize_text_field($data['name'])]);
                update_post_meta($product_id, '_price', $sale_price);
                update_post_meta($product_id, '_regular_price', $price);
                update_post_meta($product_id, '_sale_price', $sale_price);
                update_post_meta($product_id, '_stock', $stock);
                update_post_meta($product_id, '_tax_class', $tax_class);
            }
        }

        echo '✅ Sincronización completada.';
        wp_die();
    }

    private function download_csv() {
        if (empty($this->ftp_server) || empty($this->ftp_user) || empty($this->ftp_pass)) {
            echo "❌ Error: Credenciales FTP no configuradas en wp-config.php.";
            return false;
        }

        $conn = ftp_connect($this->ftp_server, 21, 30);
        if (!$conn) {
            echo "❌ Error: No se pudo conectar al servidor FTP.";
            return false;
        }

        if (!ftp_login($conn, $this->ftp_user, $this->ftp_pass)) {
            ftp_close($conn);
            echo "❌ Error: No se pudo autenticar en el FTP.";
            return false;
        }

        ftp_pasv($conn, true);
        $success = ftp_get($conn, $this->local_file, $this->remote_file, FTP_BINARY);
        ftp_close($conn);

        return $success;
    }
}

// Agregar intervalo de 30 minutos a WP-Cron
add_filter('cron_schedules', function($schedules) {
    $schedules['every_30_minutes'] = [
        'interval' => 1800,
        'display' => __('Cada 30 minutos')
    ];
    return $schedules;
});

// Activar evento al activar el plugin
register_activation_hook(__FILE__, function() {
    if (!wp_next_scheduled('woocommerce_ftp_sync_cron')) {
        wp_schedule_event(time(), 'every_30_minutes', 'woocommerce_ftp_sync_cron');
    }
});

// Eliminar evento al desactivar el plugin
register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('woocommerce_ftp_sync_cron');
});

new WooCommerce_FTP_Sync();
