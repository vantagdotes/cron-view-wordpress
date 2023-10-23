<?php
/*
* Plugin Name: Cron jobs and status
* Plugin URI: https://github.com/josejtax/cron-view-wordpress
* Description: Wordpress cron viewer.
* Version: 0.1
* Author: Jose Manuel Fernández
* Author URI: mailto:hola@jmfernandez.dev
*/

defined('ABSPATH') or die('You shouldnt be here...');

function cron_page() {
    add_submenu_page(
        'tools.php',
        'Cron wordpress',
        'Cron wordpress',
        'manage_options',
        'ver_cron',
        'plugin_cron_jtax'
    );
}
add_action('admin_menu', 'cron_page');

function plugin_cron_jtax() {
    $cron_jobs = get_option( 'cron' );
    $cron_count = 0;
    
    echo '<div>';
    echo '<h1>Process in background</h1>';
    
    if (empty($cron_jobs)) {
        echo '<p>There are no tasks in the cron.</p>';
    } else {
        echo '<p>List of tasks:</p>';
        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Name</th>';
        echo '<th>Status</th>';
        echo '<th>Latest update</th>';
        echo '<th>Upcoming work</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ( $cron_jobs as $timestamp => $cron ) {
            if (is_array($cron)) {
                foreach ( $cron as $hook => $scheduled ) {
                    if (is_array($scheduled)) {
                        foreach ( $scheduled as $key => $args ) {
                            echo '<tr>';
                            echo '<td>' . $hook . '</td>';
                            echo '<td>' . ( wp_next_scheduled( $hook ) ? 'Active' : 'Disable' ) . '</td>';
                            echo '<td>' . date( 'Y-m-d H:i:s', $timestamp ) . '</td>';
                            echo '<td>' . date( 'Y-m-d H:i:s', wp_next_scheduled( $hook ) ) . '</td>';
                            echo '</tr>';
                            $cron_count++;
                        }
                    }
                }
            }
        }
        echo '</tbody>';
        echo '</table>';
        echo '<p>Programmed tasks: ' . $cron_count . '</p>';
    }
    
    echo '</div>';
}
