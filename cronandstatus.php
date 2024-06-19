<?php
/*
* Plugin Name: Cron jobs and status
* Plugin URI: https://github.com/vantagdotes/cron-view-wordpress
* Description: Wordpress cron viewer.
* Version: 1.0.0
* Author: VANTAG.es
* Author URI: https://vantag.es
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
    
    wp_enqueue_style( 'jtax-plugin-cron-styles', plugins_url( 'assets/style.css', __FILE__ ), array(), '1.0.0' );

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
                            echo '<td>' . esc_html( $hook ) . '</td>';
                            echo '<td>';
                                if ( wp_next_scheduled( $hook ) ) {
                                    echo esc_html__( 'Active', 'jtax-plugin' );
                                } else {
                                    echo esc_html__( 'Disabled', 'jtax-plugin' );
                                }
                            echo '</td>';
                            echo '<td>' . esc_html( gmdate( 'Y-m-d // H:i', $timestamp ) ) . '</td>';
                            echo '<td>';
                                if ( wp_next_scheduled( $hook ) == gmdate( 'Y-m-d H:i:s', $timestamp )) {
                                    echo esc_html( gmdate( 'Y-m-d H:i', $timestamp ) );
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
        // Translators: Placeholder %s is for the number of cron jobs.
        echo '<p>' . esc_html( sprintf( esc_html__( 'Programmed tasks: %s', 'jtax-plugin' ), $cron_count ) ) . '</p>';
    }
    
    echo '</div>';
}