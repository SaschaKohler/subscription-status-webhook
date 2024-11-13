jQuery(document).ready(function ($) {
  // Confirmation dialogs
  $(".ssw-run-now").on("click", function (e) {
    if (!confirm("Are you sure you want to run the webhook check now?")) {
      e.preventDefault();
    }
  });

  $(".ssw-force-migration").on("click", function (e) {
    if (!confirm("Are you sure? This will reset all tracking data.")) {
      e.preventDefault();
    }
  });

  $(".ssw-repair-tables").on("click", function (e) {
    if (!confirm("Are you sure you want to repair the database tables?")) {
      e.preventDefault();
    }
  });

  // Form validation
  $("#ssw-settings-form").on("submit", function (e) {
    var planId = $("#ssw_target_plan_id").val();
    var webhookUrl = $("#ssw_webhook_url").val();

    if (!planId || !webhookUrl) {
      alert("Please fill in all required fields.");
      e.preventDefault();
      return false;
    }
  });

  // Auto-hide notices after 5 seconds
  setTimeout(function () {
    $(".ssw-notice").fadeOut("slow");
  }, 5000);

  // Debug log expansion
  $(".ssw-debug-log-entry").on("click", function () {
    $(this).find(".ssw-debug-details").slideToggle();
  });
});
