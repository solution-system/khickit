// Animate
(function ($) {
  "use strict";

  if ($.isFunction($.fn["appear"])) {
    $(function () {
      $("[data-plugin-animate], [data-appear-animation]").each(function () {
        var $this = $(this),
          opts = {};

        var pluginOptions = $this.data("plugin-options");
        if (pluginOptions) opts = pluginOptions;

        $this.themePluginAnimate(opts);
      });
    });
  }
}.apply(this, [jQuery]));

async function isNumberKey(evt, ctrl) {
  var charCode = evt.which ? evt.which : evt.keyCode;
  if (charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57)) {
    return false;
  } else if (ctrl == "hourly_rate") {
    await update_field(
      "users",
      $("#id").val(),
      $("#hourly_rate").val(),
      "hourly_rate",
      "id"
    );
  }
  return true;
}
$(document).ready(function () {
  // 'use strict';
  var dtpage = $("#page").val();
  var h_fullname = $("#h_fullname").val();
  if (h_fullname !== "" && h_fullname !== undefined) {
    var h_status = $("#h_status").val();
    var alert_type =
      $("#alert_type").val() == "" ? "info" : $("#alert_type").val();
    //  $('#non-blocking-info').click(function () {
    new PNotify({
      title: "Application is in " + $("#h_status").val(),
      text:
        h_fullname +
        " your application is in " +
        h_status +
        ". You have restricted access of the site for now. ",
      type: alert_type,
      addclass: "custom-notification-alert-success ui-pnotify-no-icon",
      nonblock: {
        nonblock: true,
        nonblock_opacity: 0.2,
      },
    });
  }
  // console.log('da: ', $('#datatable-ajax').length);
  if ($("#datatable-ajax").length > 0) {
    var table = $("#datatable-ajax").DataTable({
      rowReorder: {
        selector: "td:nth-child(2)",
      },
      ajax: {
        url: "/users/user_list",
      },
      responsive: true,
    });
  } else if ($("#config-ajax").length > 0) {
    var table = $("#config-ajax").DataTable({
      rowReorder: {
        selector: "td:nth-child(2)",
      },
      ajax: {
        url: "/config/config_list",
      },
      responsive: true,
    });
  } else if ($("#client-ajax").length > 0) {
    var table = $("#client-ajax").DataTable({
      rowReorder: {
        selector: "td:nth-child(2)",
      },
      ajax: {
        url: "/client/client_list",
      },
      responsive: true,
    });
  }

  if ($("#hired-ajax").length > 0) {
    var table = $("#hired-ajax").DataTable({
      rowReorder: {
        selector: "td:nth-child(2)",
      },
      ajax: {
        url: "/hired/hired_list/list/" + dtpage,
      },
      responsive: true,
    });
  }
  if ($("#interview-ajax").length > 0) {
    var table = $("#interview-ajax").DataTable({
      rowReorder: {
        selector: "td:nth-child(2)",
      },
      ajax: {
        url: "/interview/interview_list/list/" + dtpage,
      },
      responsive: true,
    });

    /* console.log ('int ajax: ', !table.data().count());
    if (!table.data().count()) {
      $('#interview-ajax').hide();
    } */
  }
  //  });
}); // ; // .apply(this, [jQuery]));
