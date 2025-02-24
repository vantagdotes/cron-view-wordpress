jQuery(document).ready(function($) {
    let refreshInterval;
    
    function refreshCronTable() {
        $.ajax({
            url: jtaxCron.ajax_url,
            method: 'POST',
            data: {
                action: 'jtax_refresh_cron',
                _ajax_nonce: jtaxCron.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#cron-table-container').html(response.data.table);
                    $('#cron-count').text(response.data.count);
                    $('#cron-refresh-status').text('Last update: ' + new Date().toLocaleTimeString());
                }
            },
            error: function() {
                $('#cron-refresh-status').text('Error updating cron list');
            }
        });
    }

    function startRefresh() {
        refreshInterval = setInterval(refreshCronTable, jtaxCron.refresh_interval);
    }

    function stopRefresh() {
        clearInterval(refreshInterval);
    }

    // Control de auto-refresh
    $('#jtax-pause-refresh').on('change', function() {
        if ($(this).is(':checked')) {
            startRefresh();
        } else {
            stopRefresh();
            $('#cron-refresh-status').text('Refresh paused');
        }
    });

    // Forzar ejecución de cron
    $('#jtax-force-run').on('click', function() {
        $.ajax({
            url: jtaxCron.ajax_url,
            method: 'POST',
            data: {
                action: 'jtax_force_cron',
                _ajax_nonce: jtaxCron.nonce
            },
            success: function() {
                refreshCronTable();
            }
        });
    });

    // Confirmación para eliminar
    $(document).on('click', '.delete-btn', function(e) {
        if (!confirm('Are you sure you want to delete this cron job?')) {
            e.preventDefault();
        }
    });

    // Inicio
    startRefresh();
    refreshCronTable();
});