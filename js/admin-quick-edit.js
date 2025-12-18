(function($) {
  $(function() {
    const wpInlineEdit = inlineEditPost.edit;
    inlineEditPost.edit = function(id) {
      wpInlineEdit.apply(this, arguments);
      let postId = 0;
      if (typeof id === "object")
        postId = parseInt(this.getId(id));
      if (postId > 0) {
        const editRow = $("#edit-" + postId);
        const postRow = $("#post-" + postId);
        const postViews = $(".column-post_views", postRow).text();
        $(':input[name="post_views"]', editRow).val(postViews);
        $(':input[name="current_post_views"]', editRow).val(postViews);
      }
      return false;
    };
    $(document).on("click", "#bulk_edit", function() {
      const bulkRow = $("#bulk-edit");
      const postIds = [];
      if (pvcArgsQuickEdit.wpVersion59) {
        bulkRow.find("#bulk-titles-list").children(".ntdelitem").each(function() {
          postIds.push($(this).find("button").attr("id").replace(/[^0-9]/i, ""));
        });
      } else {
        bulkRow.find("#bulk-titles").children().each(function() {
          postIds.push($(this).attr("id").replace(/^(ttle)/i, ""));
        });
      }
      const postViews = bulkRow.find('input[name="post_views"]').val();
      $.ajax({
        url: ajaxurl,
        // this is a variable that WordPress has already defined for us
        type: "post",
        async: false,
        cache: false,
        data: {
          action: "save_bulk_post_views",
          // this is the name of our WP AJAX function that we'll set up next
          post_ids: postIds,
          // and these are the 2 parameters we're passing to our function
          post_views: postViews,
          current_post_views: postViews,
          nonce: pvcArgsQuickEdit.nonce
        }
      });
    });
  });
})(jQuery);
