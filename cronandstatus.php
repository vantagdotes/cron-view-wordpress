<?php
/*
 * Plugin Name:       Cron Jobs and Status
 * Plugin URI:        https://github.com/vantagdotes/cron-view-wordpress
 * Description:       Advanced WordPress cron viewer with real-time updates, server cron visibility, deletion, and management capabilities.
 * Version:           2.0.0
 * Author:            VANTAG.es
 * Author URI:        https://vantag.es
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 */

defined('ABSPATH') or die('You shouldnt be here...');

// Añadir menú de administración
function jtax_plugin_display_cron_tasks() {
    add_submenu_page(
        'tools.php',
        'Cron Manager',
        'Cron Manager',
        'manage_options',
        'ver_cron',
        'jtax_plugin_cron_jtax'
    );
}
add_action('admin_menu', 'jtax_plugin_display_cron_tasks');

// Procesar acciones
function jtax_handle_cron_actions() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['action']) && check_admin_referer('jtax_cron_action')) {
        $hook = sanitize_text_field($_POST['cron_hook']);
        switch ($_POST['action']) {
            case 'delete':
                wp_unschedule_hook($hook);
                break;
            case 'run_now':
                wp_schedule_single_event(time(), $hook);
                break;
        }
        wp_redirect(admin_url('tools.php?page=ver_cron'));
        exit;
    }
}
add_action('admin_init', 'jtax_handle_cron_actions');

// Función principal de visualización
function jtax_plugin_cron_jtax() {
    wp_enqueue_style('jtax-plugin-cron-styles', plugins_url('assets/style.css', __FILE__), [], '1.3.0');
    wp_enqueue_script('jtax-plugin-cron-script', plugins_url('assets/script.js', __FILE__), ['jquery'], '1.3.0', true);

    wp_localize_script('jtax-plugin-cron-script', 'jtaxCron', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('jtax_cron_refresh'),
        'refresh_interval' => 10000
    ]);

    echo '<div class="wrap">';
    echo '<h1>Cron Manager</h1>';
    echo '<div class="jtax-controls">';
    echo '<span id="cron-refresh-status">Updating in real-time...</span>';
    echo '<label><input type="checkbox" id="jtax-pause-refresh" checked> Auto-refresh</label>';
    echo '</div>';

    echo '<div class="jtax-tabs">';
    echo '<button class="jtax-tab-button active" data-tab="wordpress">WordPress Cron</button>';
    echo '<button class="jtax-tab-button" data-tab="server">Server Cron</button>';
    echo '</div>';

    echo '<div id="cron-table-container" class="jtax-tab-content" data-tab="wordpress">';
    jtax_render_wp_cron_table();
    echo '</div>';
    echo '<div id="server-cron-container" class="jtax-tab-content" data-tab="server" style="display:none;">';
    jtax_render_server_cron_table();
    echo '</div>';

    echo '<div class="jtax-footer">';
    echo '<p>Total WordPress tasks: <span id="cron-count">0</span></p>';
    echo '<button id="jtax-force-run" class="button">Force Cron Run</button>';
    echo '</div>';
    echo '</div>';
}

// Renderizar tabla de WordPress cron
function jtax_render_wp_cron_table() {
    $cron_jobs = get_option('cron');
    $cron_count = 0;

    echo '<table class="wordpress-cron">';
    echo '<thead><tr><th>Name</th><th>Status</th><th>Latest</th><th>Next</th><th>Recurrence</th><th>Actions</th></tr></thead>';
    echo '<tbody>';

    if (empty($cron_jobs) || !is_array($cron_jobs)) {
        echo '<tr><td colspan="6">No WordPress scheduled tasks found.</td></tr>';
    } else {
        foreach ($cron_jobs as $timestamp => $cron) {
            if (!is_array($cron)) continue;
            foreach ($cron as $hook => $scheduled) {
                if (!is_array($scheduled)) continue;
                foreach ($scheduled as $key => $args) {
                    $next_scheduled = wp_next_scheduled($hook);
                    $schedules = wp_get_schedules();
                    $schedule = $args['schedule'] ?? 'one-time';
                    $interval = $schedules[$schedule]['display'] ?? 'One-time';

                    echo '<tr>';
                    echo '<td>' . esc_html($hook) . '</td>';
                    echo '<td>' . ($next_scheduled ? '<span class="status-active">Active</span>' : '<span class="status-inactive">Inactive</span>') . '</td>';
                    echo '<td>' . ($timestamp ? esc_html(gmdate('Y-m-d H:i', $timestamp)) : '-') . '</td>';
                    echo '<td>' . ($next_scheduled ? esc_html(gmdate('Y-m-d H:i', $next_scheduled)) : '-') . '</td>';
                    echo '<td>' . esc_html($interval) . '</td>';
                    echo '<td>';
                    echo '<form method="post" class="cron-action-form">';
                    wp_nonce_field('jtax_cron_action');
                    echo '<input type="hidden" name="cron_hook" value="' . esc_attr($hook) . '">';
                    echo '<button type="submit" name="action" value="run_now" class="button button-secondary">Run Now</button>';
                    echo '<button type="submit" name="action" value="delete" class="button button-secondary delete-btn">Delete</button>';
                    echo '</form>';
                    echo '</td>';
                    echo '</tr>';
                    $cron_count++;
                }
            }
        }
    }

    echo '</tbody></table>';
    return $cron_count;
}

// Renderizar tabla de Server cron
function jtax_render_server_cron_table() {
    echo '<table class="server-cron">';
    echo '<thead><tr><th>Schedule</th><th>Command</th></tr></thead>';
    echo '<tbody>';

    if (!function_exists('exec') || in_array('exec', array_map('trim', explode(',', ini_get('disable_functions'))))) {
        echo '<tr><td colspan="2">Server cron viewing is disabled. The <code>exec()</code> function is not available on this server.</td></tr>';
    } else {
        $cron_output = [];
        @exec('crontab -l 2>/dev/null', $cron_output);
        if (empty($cron_output)) {
            echo '<tr><td colspan="2">No server cron jobs found or no permission to access crontab.</td></tr>';
        } else {
            foreach ($cron_output as $line) {
                $line = trim($line);
                if (empty($line) || $line[0] === '#') continue; // Saltar comentarios y líneas vacías
                $parts = preg_split('/\s+/', $line, 6);
                if (count($parts) < 6) continue;

                $schedule = implode(' ', array_slice($parts, 0, 5)); // Minuto, hora, día, mes, día de semana
                $command = $parts[5];
                echo '<tr>';
                echo '<td>' . esc_html($schedule) . '</td>';
                echo '<td><code>' . esc_html($command) . '</code></td>';
                echo '</tr>';
            }
        }
    }

    echo '</tbody></table>';
}

// AJAX handler para WordPress cron
add_action('wp_ajax_jtax_refresh_cron', function() {
    check_ajax_referer('jtax_cron_refresh');
    ob_start();
    $count = jtax_render_wp_cron_table();
    $table = ob_get_clean();
    wp_send_json_success(['table' => $table, 'count' => $count]);
});

// Forzar ejecución de WordPress cron
add_action('wp_ajax_jtax_force_cron', function() {
    check_ajax_referer('jtax_cron_refresh');
    spawn_cron();
    wp_send_json_success();
});