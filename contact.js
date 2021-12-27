/*
Name: 			View - Contact
Written by: 	Okler Themes - (http://www.okler.net)
Theme Version:	8.3.0
*/

(function ($) {
  "use strict";

  /*
	Custom Rules
	*/

  // No White Space
  $.validator.addMethod(
    "noSpace",
    function (value, element) {
      if ($(element).attr("required")) {
        return (
          value.search(
            /[a-zA-Z0-9À-žа-яА-ЯёЁα-ωΑ-Ω\s\u0621-\u064A\u0660-\u0669 ]/i
          ) == 0
        );
      }

      return true;
    },
    "Please fill this empty field."
  );

  /*
	Assign Custom Rules on Fields
	*/
  $.validator.addClassRules({
    "form-control": {
      noSpace: true,
    },
  });

  /*
	Contact Form: Basic
	*/
  $(".contact-form").each(function () {
    $(this).validate({
      errorPlacement: function (error, element) {
        if (
          element.attr("type") == "radio" ||
          element.attr("type") == "checkbox"
        ) {
          error.appendTo(element.closest(".form-group"));
        } else if (
          element.is("select") &&
          element.closest(".custom-select-1")
        ) {
          error.appendTo(element.closest(".form-group"));
        } else {
          if (element.closest(".form-group").length) {
            error.appendTo(element.closest(".form-group"));
          } else {
            error.insertAfter(element);
          }
        }
      },
      submitHandler: function (form) {
        // console.log('3- ', form);

        var $form = $(form),
          $messageSuccess = $form.find(".contact-form-success"),
          $messageError = $form.find(".contact-form-error"),
          $submitButton = $(this.submitButton),
          $errorMessage = $form.find(".mail-error-message"),
          submitButtonText = $submitButton.val();

        $submitButton
          .val(
            $submitButton.data("loading-text")
              ? $submitButton.data("loading-text")
              : "Loading..."
          )
          .attr("disabled", true);

        // Fields Data
        var formData = $form.serializeArray(),
          data = {};

        $(formData).each(function (index, obj) {
          // if (obj.name !== 'csrf_test_name') {
          data[obj.name] = obj.value;
          //  }
        });

        // Google Recaptcha v2
        if (data["g-recaptcha-response"] != undefined) {
          data["g-recaptcha-response"] = $form
            .find("#g-recaptcha-response")
            .val();
        }
        console.log("1-", data);
        var str_url = "/db_ops/send_ajax_mail/";
        console.log("url: ", data);
        // Ajax Submit
        $.ajax({
          type: "POST",
          url: str_url, // $form.attr('action'),
          data: data,
          dataType: "json",
        }).always(function (data, textStatus, jqXHR) {
          $errorMessage.empty().hide();
          console.log("2b- ", data, data.response, data.sMessage);
          if (data.response == "success") {
            // Uncomment the code below to redirect for a thank you page
            // self.location = 'thank-you.html';

            $messageSuccess.removeClass("d-none");
            $messageError.addClass("d-none");

            // Reset Form
            $form
              .find(".form-control")
              .val("")
              .blur()
              .parent()
              .removeClass("has-success")
              .removeClass("has-danger")
              .find("label.error")
              .remove();

            if ($messageSuccess.offset().top - 80 < $(window).scrollTop()) {
              $("html, body").animate(
                {
                  scrollTop: $messageSuccess.offset().top - 80,
                },
                300
              );
            }

            $form.find(".form-control").removeClass("error");

            $submitButton.val(submitButtonText).attr("disabled", false);

            return;
          } else if (
            data.response == "error" &&
            typeof data.errorMessage !== "undefined"
          ) {
            $errorMessage.html(data.errorMessage).show();
          } else {
            $errorMessage.html(data.responseText).show();
          }

          $messageError.removeClass("d-none");
          $messageSuccess.addClass("d-none");
          // var nav = $('.content-nav');
          if ($messageError.length) {
            if ($messageError.offset().top - 80 < $(window).scrollTop()) {
              $("html, body").animate(
                {
                  scrollTop: $messageError.offset().top - 80,
                },
                300
              );
            }

            $form.find(".has-success").removeClass("has-success");

            $submitButton.val(submitButtonText).attr("disabled", false);
          }
        });
      },
    });
  });
}.apply(this, [jQuery]));
