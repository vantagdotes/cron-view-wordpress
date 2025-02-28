<?php
/*
 * Plugin Name:       Cron Jobs and Status
 * Plugin URI:        https://github.com/vantagdotes/cron-view-wordpress
 * Description:       Advanced WordPress cron viewer with real-time updates, server cron visibility, and management capabilities including deactivation.
 * Version:           1.4.4
 * Author:            VANTAG.es
 * Author URI:        https://vantag.es
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 */

defined('ABSPATH') or die('You shouldnt be here...');

// Registrar opción para cron desactivados
register_activation_hook(__FILE__, function() {
    if (!get_option('jtax_disabled_crons')) {
        add_option('jtax_disabled_crons', []);
    }
});

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

// Procesar acciones solo si estamos en la página del plugin
function jtax_handle_cron_actions() {
    // Solo proceder si estamos en la página del plugin y hay una acción específica
    if (!isset($_GET['page']) || $_GET['page'] !== 'ver_cron' || !isset($_POST['action']) || !current_user_can('manage_options')) {
        return; // No interferir con otras solicitudes POST
    }

    if (!check_admin_referer('jtax_cron_action', '_wpnonce')) {
        wp_die('Security check failed. Please try again.');
    }

    $hook = sanitize_text_field($_POST['cron_hook']);
    $disabled_crons = get_option('jtax_disabled_crons', []);

    switch ($_POST['action']) {
        case 'deactivate':
            wp_unschedule_hook($hook);
            if (!in_array($hook, $disabled_crons)) {
                $disabled_crons[] = $hook;
                update_option('jtax_disabled_crons', $disabled_crons);
            }
            break;
        case 'reactivate':
            if (($key = array_search($hook, $disabled_crons)) !== false) {
                unset($disabled_crons[$key]);
                update_option('jtax_disabled_crons', array_values($disabled_crons));
            }
            wp_schedule_event(time(), 'daily', $hook);
            break;
        case 'run_now':
            wp_schedule_single_event(time(), $hook);
            if (($key = array_search($hook, $disabled_crons)) !== false) {
                unset($disabled_crons[$key]);
                update_option('jtax_disabled_crons', array_values($disabled_crons));
            }
            break;
    }
    wp_redirect(admin_url('tools.php?page=ver_cron'));
    exit;
}
add_action('admin_init', 'jtax_handle_cron_actions');

// Función principal de visualización
function jtax_plugin_cron_jtax() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    wp_enqueue_style('jtax-plugin-cron-styles', plugins_url('assets/style.css', __FILE__), [], '1.4.4');
    wp_enqueue_script('jtax-plugin-cron-script', plugins_url('assets/script.js', __FILE__), ['jquery'], '1.4.4', true);

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
    $cron_jobs = get_option('cron', []);
    $disabled_crons = get_option('jtax_disabled_crons', []);
    $cron_count = 0;

    $output = '<table class="wordpress-cron">';
    $output .= '<thead><tr><th>Name</th><th>Status</th><th>Latest</th><th>Next</th><th>Recurrence</th><th>Actions</th></tr></thead>';
    $output .= '<tbody>';

    if (empty($cron_jobs) || !is_array($cron_jobs)) {
        $output .= '<tr><td colspan="6">No WordPress scheduled tasks found.</td></tr>';
    } else {
        foreach ($cron_jobs as $timestamp => $cron) {
            if (!is_array($cron)) continue;
            foreach ($cron as $hook => $scheduled) {
                if (!is_array($scheduled)) continue;
                foreach ($scheduled as $key => $args) {
                    $next_scheduled = wp_next_scheduled($hook);
                    $is_disabled = in_array($hook, $disabled_crons);
                    $schedules = wp_get_schedules();
                    $schedule = $args['schedule'] ?? 'one-time';
                    $interval = $schedules[$schedule]['display'] ?? 'One-time';

                    $output .= '<tr>';
                    $output .= '<td>' . esc_html($hook) . '</td>';
                    $output .= '<td>' . ($next_scheduled && !$is_disabled ? '<span class="status-active">Active</span>' : '<span class="status-inactive">Inactive</span>') . '</td>';
                    $output .= '<td>' . ($timestamp ? esc_html(gmdate('Y-m-d H:i', $timestamp)) : '-') . '</td>';
                    $output .= '<td>' . ($next_scheduled && !$is_disabled ? esc_html(gmdate('Y-m-d H:i', $next_scheduled)) : '-') . '</td>';
                    $output .= '<td>' . esc_html($interval) . '</td>';
                    $output .= '<td>';
                    $output .= '<form method="post" class="cron-action-form">';
                    $output .= wp_nonce_field('jtax_cron_action', '_wpnonce', true, false);
                    $output .= '<input type="hidden" name="cron_hook" value="' . esc_attr($hook) . '">';
                    $output .= '<button type="submit" name="action" value="run_now" class="button button-secondary">Run Now</button>';
                    if ($next_scheduled && !$is_disabled) {
                        $output .= '<button type="submit" name="action" value="deactivate" class="button button-secondary deactivate-btn">Deactivate</button>';
                    } else {
                        $output .= '<button type="submit" name="action" value="reactivate" class="button button-secondary reactivate-btn">Activate</button>';
                    }
                    $output .= '</form>';
                    $output .= '</td>';
                    $output .= '</tr>';
                    $cron_count++;
                }
            }
        }
    }

    $output .= '</tbody></table>';
    echo $output;
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
                if (empty($line) || $line[0] === '#') continue;
                $parts = preg_split('/\s+/', $line, 6);
                if (count($parts) < 6) continue;

                $schedule = implode(' ', array_slice($parts, 0, 5));
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
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
        return;
    }

    if (!check_ajax_referer('jtax_cron_refresh', false, false)) {
        wp_send_json_error('Nonce verification failed');
        return;
    }

    ob_start();
    $count = jtax_render_wp_cron_table();
    $table = ob_get_clean();

    if (empty($table)) {
        wp_send_json_error('Failed to render cron table');
    } else {
        wp_send_json_success(['table' => $table, 'count' => $count]);
    }
});

// Forzar ejecución de WordPress cron
add_action('wp_ajax_jtax_force_cron', function() {
    if (!current_user_can('manage_options') || !check_admin_referer('jtax_cron_refresh', false, false)) {
        wp_send_json_error('Permission or nonce check failed');
        return;
    }
    spawn_cron();
    wp_send_json_success();
});