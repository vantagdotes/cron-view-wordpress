<?php
/*
* Plugin Name: Cron jobs and status
* Plugin URI: https://github.com/vantagdotes/cron-view-wordpress
* Description: Advanced Wordpress cron viewer with real-time updates, deletion, and management capabilities.
* Version: 1.2.0
* Author: VANTAG.es
* Author URI: https://vantag.es
* License: GPLv3 or later
* License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

defined('ABSPATH') or die('You shouldnt be here...');

// Añadir menú de administración
function jtax_plugin_display_cron_tasks() {
    add_submenu_page(
        'tools.php',
        'Cron wordpress',
        'Cron wordpress',
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
    // Cargar recursos
    wp_enqueue_style('jtax-plugin-cron-styles', plugins_url('assets/style.css', __FILE__), array(), '1.2.0');
    wp_enqueue_script('jtax-plugin-cron-script', plugins_url('assets/script.js', __FILE__), array('jquery'), '1.2.0', true);
    
    wp_localize_script('jtax-plugin-cron-script', 'jtaxCron', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('jtax_cron_refresh'),
        'refresh_interval' => 10000 // Configurable en milisegundos
    ));

    echo '<div class="wrap">';
    echo '<h1>Process in background</h1>';
    echo '<div class="jtax-controls">';
    echo '<span id="cron-refresh-status">Updating in real-time...</span>';
    echo '<label><input type="checkbox" id="jtax-pause-refresh" checked> Auto-refresh</label>';
    echo '</div>';
    
    echo '<div id="cron-table-container">';
    jtax_render_cron_table();
    echo '</div>';
    
    echo '<div class="jtax-footer">';
    echo '<p>Total tasks: <span id="cron-count">0</span></p>';
    echo '<button id="jtax-force-run" class="button">Force Cron Run</button>';
    echo '</div>';
    echo '</div>';
}

// Renderizar tabla
function jtax_render_cron_table() {
    $cron_jobs = get_option('cron');
    $cron_count = 0;
    
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Name</th>';
    echo '<th>Status</th>';
    echo '<th>Latest</th>';
    echo '<th>Next</th>';
    echo '<th>Recurrence</th>';
    echo '<th>Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    if (empty($cron_jobs) || !is_array($cron_jobs)) {
        echo '<tr><td colspan="6">No scheduled tasks found.</td></tr>';
    } else {
        foreach ($cron_jobs as $timestamp => $cron) {
            if (!is_array($cron)) continue;
            
            foreach ($cron as $hook => $scheduled) {
                if (!is_array($scheduled)) continue;
                
                foreach ($scheduled as $key => $args) {
                    $next_scheduled = wp_next_scheduled($hook);
                    $schedules = wp_get_schedules();
                    $schedule = isset($args['schedule']) ? $args['schedule'] : 'one-time';
                    $interval = isset($schedules[$schedule]) ? $schedules[$schedule]['display'] : 'One-time';
                    
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
    
    echo '</tbody>';
    echo '</table>';
    return $cron_count;
}

// AJAX handler
add_action('wp_ajax_jtax_refresh_cron', function() {
    check_ajax_referer('jtax_cron_refresh');
    ob_start();
    $count = jtax_render_cron_table();
    $table = ob_get_clean();
    wp_send_json_success(array('table' => $table, 'count' => $count));
});

// Forzar ejecución de cron
add_action('wp_ajax_jtax_force_cron', function() {
    check_ajax_referer('jtax_cron_refresh');
    spawn_cron();
    wp_send_json_success();
});