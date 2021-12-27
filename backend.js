function goto_update(status, user_id, id, trade_basic) {
  var loc = null;
  var url = null;

  console.log("goto_update(: ", status, user_id, id, trade_basic);
  if (status == "0") {
    loc = "/confirm/user/" + user_id;
  } else if (status == "-1") {
    url = "setpass";
  } else if (status == "-2") {
    url = "register_work";
  } else {
    if (trade_basic == "1" || trade_basic == "3") {
      if (status == "-3") {
        url = "register_experience";
      } else if (trade_basic == "1") {
        if (status == "-4") {
          url = "register_epic_certified_upload";
        } else if (status == "-5") {
          url = "register_epic_project_detail";
        } else if (status == "-6") {
          url = "register_epic_questions";
        } else if (status == "-7") {
          url = "register_questions";
        }
      } else if (status == "-7") {
        url = "register_questions";
      }
    } else if (status == "-7") {
      url = "register_questions";
    }
  }
  if (status !== "-8" && status !== "0" && url !== null) {
    loc = "/register/" + url + "/" + user_id;
  }
  if (loc !== null) {
    console.log("loc: ", loc);
    location.href = loc;
  } else {
    // update_field('user_work', " . $value['id'] . ", this.value, 'trade_basic', 'user_id')
    update_field("users", id, status, "status", "id");
  }
}

// update_field('users', '102', this.value, 'confirmed')"
function update_field(table, id, val, fld, sWhere) {
  // console.log('function update_field(: ', table, id, val, fld, sWhere);
  if (typeof val === "boolean") {
    if (val == true) val = 1;
    else val = 0;
  }
  if (sWhere !== "") {
    var str_url =
      "/db_ops/update_fld/" +
      table +
      "/" +
      id +
      "/" +
      val +
      "/" +
      fld +
      "/" +
      sWhere;
  } else {
    var str_url =
      "/db_ops/update_fld/" + table + "/" + id + "/" + val + "/" + fld;
  }

  console.log(table + " url:" + str_url);
  jQuery.ajax({
    url: str_url,
    cache: false,
    async: true,
    dataType: "text",
  });
  event.preventDefault();
}
function delete_img(rec_id, table, fld, div) {
  /* if (table == 'phones') var str_url = '/phones/delete_img/';
  else var str_url = '/processing/delete_img/';
  */
  str_url = "/db_ops/delete_file/" + rec_id + "/" + fld + "/" + table;
  console.log("url:" + str_url);
  jQuery.ajax({
    url: str_url,
    cache: false,
    async: true,
    dataType: "text",
    success: function (data) {
      // $("#msg_" + id).html("Database has been updated");
    },
  });
  $("#div_" + div).fadeOut();
  if ($("#img_length").html() == 0) {
    $("#img_length_title").hide();
  } else {
    $("#img_length").html($("#img_length").html() - 1);
  }
  event.preventDefault();
}
function activeTab(tab) {
  $('.nav-list a[href="#' + tab + '"]').tab("show");
  // $('.nav-item .nav-link a[href="#' + tab + '"]').tab('show');
  $('.nav-tabs a[href="#' + tab + '"]').tab("show");
  $(".nav-item").removeClass("active");
  $("#nav-" + tab).addClass("active");
}
$(document).ready(function () {
  $(".nav-item").click(function () {
    $(this).addClass("active");
    $(".nav-item").not(this).removeClass("active");
  });
  $(".pinned").on("pin", {
    // containerSelector: ".container",
    minWidth: 940,
  });
  /* if ($("#error_msg").val() !== "") {
    activeTab("detail");
  } else {
    activeTab("resource");
  } */
  $("#datatable-ajax").on("change", 'input[type="checkbox"]', function () {
    var ref = $(this).attr("ref");
    var chk_interviewed = $(this).prop("checked");
    console.log("users", ref, chk_interviewed, "interviewed", "id");
    update_field("users", ref, chk_interviewed, "interviewed", "id");
  });

  $(".radioBtnClass")
    .unbind()
    .click(function () {
      // var val = $(this).value;
      var val = $("input[name=call_option]:checked", "#myForm").val();
      if (
        val !== undefined &&
        confirm("Are you sure to select this time for interview: " + val)
      ) {
        $("#myForm").submit();
      } else {
        $("input[name=call_option]:checked", "#myForm").prop("checked", false);
      }
    });
  /* $('.radioBtnClass').each(function(){
  alert($(this).val());
  }); */
  $("#picture").on("change", function (e) {
    e.preventDefault();
    // console.log('pic: ', $('#picture').val());
    if ($("#picture").val() == "") {
      alert("Please Select the File");
    } else {
      $.ajax({
        url: "/dashboard/ajax_upload",
        method: "POST",
        data: new FormData(document.getElementById("upload_form")),
        contentType: false,
        cache: false,
        processData: false,
        success: async function (data) {
          // console.log('src: ', data);
          var resp = data.split(",")[0];
          var img_info = data.split(",")[1];
          if (resp == "0") {
            alert("Error: " + img_info);
          } else {
            // $('#picture').attr('src', data);
            // console.log('else: ' + img);
            await $("#pic_name").prop("src", "/resume/picture/" + img_info);
          }
        },
        /* 
        complete: function () {
          // Schedule the next request when the current one has been completed
          setTimeout(ajaxInterval, 4000);
        }, */
      });
    }
  });
});
