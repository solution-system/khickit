$(function () {
  if ($(".rateYo").length) {
    $(".rateYo").rateYo({
      maxValue: 5,
      numStars: 5,
      starWidth: "20px",
      halfStar: true,
      ratedFill: "#c10000",
      onSet: function (rating, rateYoInstance) {
        //   $(this).next().text(rating);
        var id = $(this).attr("ref");
        console.log("val: ", id, rating);
        update_field("resource_tasks", id, rating, "rate", "id");
      },
    });
  }
});
