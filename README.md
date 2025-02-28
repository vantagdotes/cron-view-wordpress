# Cron Jobs and Status

**Plugin Name:** Cron Jobs and Status  
**Plugin URI:** [https://github.com/vantagdotes/cron-view-wordpress](https://github.com/vantagdotes/cron-view-wordpress)  
**Description:** Advanced WordPress cron viewer with real-time updates, server cron visibility, and management capabilities including deactivation.  
**Version:** 1.4.4  
**Author:** VANTAG.es  
**Author URI:** [https://vantag.es](https://vantag.es)  
**License:** GPLv3 or later  
**License URI:** [https://www.gnu.org/licenses/gpl-3.0.html](https://www.gnu.org/licenses/gpl-3.0.html)  

## Description

Cron Jobs and Status is a powerful WordPress plugin designed to provide a comprehensive view and management interface for both WordPress cron jobs and server-level cron tasks. It offers real-time updates, the ability to deactivate/reactivate WordPress cron jobs, and visibility into system-level cron jobs (where supported), all from a user-friendly dashboard within the WordPress admin area.

### Features

- **Real-Time Updates:** Automatically refreshes the cron job list every 10 seconds (configurable).
- **WordPress Cron Management:** View, run immediately, deactivate, or reactivate WordPress scheduled tasks.
- **Server Cron Visibility:** Displays system-level cron jobs (requires `exec()` support on the server).
- **Tab Interface:** Separate tabs for WordPress and server cron jobs for easy navigation.
- **User-Friendly:** Clear status indicators (Active/Inactive) and intuitive action buttons.
- **Secure:** Built-in nonce verification and capability checks to ensure only authorized users can manage cron jobs.

## Installation

1. **Download the Plugin:**
   - Clone this repository or download the ZIP file from [GitHub](https://github.com/vantagdotes/cron-view-wordpress).

2. **Upload to WordPress:**
   - Via WordPress Admin: Go to `Plugins` > `Add New` > `Upload Plugin`, then select the ZIP file.
   - Via FTP: Upload the plugin folder to `wp-content/plugins/`.

3. **Activate the Plugin:**
   - Go to `Plugins` in the WordPress admin area and activate "Cron Jobs and Status".

4. **Access the Dashboard:**
   - Navigate to `Tools` > `Cron Manager` in the WordPress admin menu.

## Usage

1. **View Cron Jobs:**
   - Open `Tools` > `Cron Manager` to see two tabs: "WordPress Cron" and "Server Cron".
   - "WordPress Cron" lists all scheduled tasks within WordPress.
   - "Server Cron" attempts to list system-level cron jobs (availability depends on server configuration).

2. **Manage WordPress Cron Jobs:**
   - **Run Now:** Execute a cron job immediately.
   - **Deactivate:** Mark a cron job as inactive without deleting it permanently.
   - **Activate:** Reactivate a previously deactivated cron job (scheduled daily by default).
   - Use the "Force Cron Run" button to trigger WordPress cron execution manually.

3. **Auto-Refresh:**
   - Toggle the "Auto-refresh" checkbox to enable/disable real-time updates.

## Requirements

- **WordPress Version:** 6.3 or higher
- **PHP Version:** 7.4 or higher
- **Server Cron Visibility:** Requires the PHP `exec()` function to be enabled and appropriate server permissions to access `crontab -l`. This feature may not work on shared hosting environments where `exec()` is disabled.

## Troubleshooting

- **"AJAX Error: error -" Message:**
  - Ensure you're logged in as an administrator with `manage_options` capability.
  - Check server logs (`wp-content/debug.log`) with `WP_DEBUG` enabled for detailed error messages.
  - Verify that `admin-ajax.php` is accessible and not blocked by security plugins or server rules.

- **Cannot Perform Actions (e.g., Publish Posts):**
  - If you see "The link you followed has expired," deactivate the plugin via FTP by renaming its folder (e.g., to `cron-jobs-and-status-disabled`), then update to version 1.4.4 or higher.

- **Server Cron Not Showing:**
  - Confirm that `exec()` is enabled in your PHP configuration (`phpinfo()` can verify this).
  - Ensure the web server user (e.g., `www-data`) has permission to run `crontab -l`.

## Development

### File Structure

cron-jobs-and-status/
├── assets/
│   ├── style.css       # Styles for the plugin interface
│   └── script.js       # JavaScript for real-time updates and interactions
└── cron-jobs-and-status.php  # Main plugin file


### Contributing
Contributions are welcome! Please fork the repository, make your changes, and submit a pull request. Ensure your code follows WordPress coding standards.

## Changelog

### 1.4.4 (Latest)
- Fixed issue where plugin interfered with other POST actions (e.g., publishing posts) by restricting action handling to the plugin's page only.

### 1.4.3
- Improved stability by removing forced `WP_DEBUG` definitions and enhancing error handling.

### 1.4.2
- Added debug logging to diagnose AJAX issues.

### 1.4.1
- Corrected button label logic for "Deactivate" vs "Activate".

### 1.4.0
- Introduced deactivation/reactivation feature for WordPress cron jobs.

## License

This plugin is licensed under the [GNU General Public License v3 or later](https://www.gnu.org/licenses/gpl-3.0.html). You are free to use, modify, and distribute it under the terms of this license.

## Support

For issues, feature requests, or questions, please open an issue on the [GitHub repository](https://github.com/vantagdotes/cron-view-wordpress/issues).

---
Made with ❤️ by [VANTAG.es](https://vantag.es)