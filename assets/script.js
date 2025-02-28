jQuery(document).ready(function ($) {
  let refreshInterval;

  function refreshCronTable() {
    $.ajax({
      url: jtaxCron.ajax_url,
      method: "POST",
      data: {
        action: "jtax_refresh_cron",
        _ajax_nonce: jtaxCron.nonce,
      },
      success: function (response) {
        if (response.success) {
          $("#cron-table-container").html(response.data.table);
          $("#cron-count").text(response.data.count);
          $("#cron-refresh-status").text(
            "Last update: " + new Date().toLocaleTimeString()
          );
        } else {
          $("#cron-refresh-status").text(
            "Error: " + (response.data || "Unknown error")
          );
        }
      },
      error: function (xhr, status, error) {
        $("#cron-refresh-status").text(
          "AJAX Error: " +
            status +
            " - " +
            (xhr.status ? xhr.status + " " + xhr.statusText : error)
        );
      },
    });
  }

  function startRefresh() {
    refreshInterval = setInterval(refreshCronTable, jtaxCron.refresh_interval);
  }

  function stopRefresh() {
    clearInterval(refreshInterval);
  }

  $("#jtax-pause-refresh").on("change", function () {
    if ($(this).is(":checked")) {
      startRefresh();
    } else {
      stopRefresh();
      $("#cron-refresh-status").text("Refresh paused");
    }
  });

  $("#jtax-force-run").on("click", function () {
    $.ajax({
      url: jtaxCron.ajax_url,
      method: "POST",
      data: {
        action: "jtax_force_cron",
        _ajax_nonce: jtaxCron.nonce,
      },
      success: function (response) {
        if (response.success) {
          refreshCronTable();
        }
      },
    });
  });

  $(document).on("click", ".deactivate-btn", function (e) {
    if (!confirm("Are you sure you want to deactivate this cron job?")) {
      e.preventDefault();
    }
  });

  $(document).on("click", ".reactivate-btn", function (e) {
    if (!confirm("Are you sure you want to reactivate this cron job?")) {
      e.preventDefault();
    }
  });

  $(".jtax-tab-button").on("click", function () {
    $(".jtax-tab-button").removeClass("active");
    $(this).addClass("active");

    var tab = $(this).data("tab");
    $(".jtax-tab-content").hide();
    $('[data-tab="' + tab + '"]').show();

    if (tab === "wordpress" && $("#jtax-pause-refresh").is(":checked")) {
      startRefresh();
    } else {
      stopRefresh();
    }
  });

  startRefresh();
  refreshCronTable();
});
