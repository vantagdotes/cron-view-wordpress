<?php
/*
* Plugin Name: Cron jobs and status
* Plugin URI: https://github.com/josejtax/cron-view-wordpress
* Description: Wordpress cron viewer.
* Version: 1.0.0
* Author: jtax.dev
* Author URI: https://jtax.dev
* License: GPLv3 or later
* License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

defined('ABSPATH') or die('You shouldnt be here...');

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

function jtax_plugin_cron_jtax() {
    $cron_jobs = get_option( 'cron' );
    $cron_count = 0;
    
    wp_enqueue_style( 'jtax-plugin-cron-styles', plugins_url( 'assets/style.css', __FILE__ ) );

    echo '<div>';
    echo '<h1>Process in background</h1>';
    
    if (empty($cron_jobs)) {
        echo '<p>There are no tasks in the cron.</p>';
    } else {
        echo '<h2>List of tasks:</h2>';
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
                            echo '<td>' . esc_html( $hook ) . '</td>'; // Escapar la variable $hook
                            echo '<td>';
                                if ( wp_next_scheduled( $hook ) ) {
                                    echo esc_html__( 'Active', 'jtax-plugin' );
                                } else {
                                    echo esc_html__( 'Disabled', 'jtax-plugin' );
                                }
                            echo '</td>';
                            echo '<td>' . esc_html( date( 'Y-m-d // H:i', $timestamp ) ) . '</td>'; // Escapar la salida de date()
                            echo '<td>';
                                if ( wp_next_scheduled( $hook ) == date( 'Y-m-d H:i:s', $timestamp )) {
                                    echo esc_html( date( 'Y-m-d H:i', $timestamp ) );
                                } else {
                                    echo esc_html__( 'No next tasks', 'jtax-plugin' );
                                }
                            echo '</td>';

                            echo '</tr>';
                            $cron_count++;
                        }
                    }
                }
            }
        }
        echo '</tbody>';
        echo '</table>';
        echo '<p>' . sprintf( esc_html__( 'Programmed tasks: %d', 'jtax-plugin' ), $cron_count ) . '</p>'; // Escapar la variable $cron_count
    }
    
    echo '</div>';
}