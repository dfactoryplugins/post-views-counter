(function($) {
  $(function() {
    $("#post-views .edit-post-views").on("click", function() {
      if ($("#post-views-input-container").is(":hidden")) {
        $("#post-views-input-container").slideDown("fast");
        $(this).hide();
      }
      return false;
    });
    $("#post-views .save-post-views").on("click", function() {
      let views = $("#post-views-display b").text().trim();
      $("#post-views-input-container").slideUp("fast");
      $("#post-views .edit-post-views").show();
      views = parseInt($("#post-views-input").val());
      $("#post-views-input").val(views);
      $("#post-views-display b").text(views);
      return false;
    });
    $("#post-views .cancel-post-views").on("click", function() {
      let views = $("#post-views-display b").text().trim();
      $("#post-views-input-container").slideUp("fast");
      $("#post-views .edit-post-views").show();
      views = parseInt($("#post-views-current").val());
      $("#post-views-display b").text(views);
      $("#post-views-input").val(views);
      return false;
    });
  });
})(jQuery);
