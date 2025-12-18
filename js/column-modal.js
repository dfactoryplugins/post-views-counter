(function($) {
  if (typeof $ === "undefined")
    return;
  let pvcModalChart = null;
  let currentPostId = null;
  if (typeof pvcColumnModal === "undefined")
    return;
  function initMicromodal() {
    if (typeof MicroModal === "undefined")
      return false;
    MicroModal.init({
      disableScroll: true,
      awaitCloseAnimation: true
    });
    return true;
  }
  function prepareModalForPost(postId, postTitle) {
    if (!postId)
      return false;
    currentPostId = postId;
    $("#pvc-modal-title").text(postTitle);
    const $container = $(".pvc-modal-chart-container");
    $container.addClass("loading");
    $container.find(".spinner").addClass("is-active");
    $(".pvc-modal-views-label").text("");
    $(".pvc-modal-count").text("");
    $(".pvc-modal-dates").html("");
    return true;
  }
  function resetModalContent() {
    $("#pvc-modal-title").text("");
    $(".pvc-modal-views-label").text("");
    $(".pvc-modal-count").text("");
    $(".pvc-modal-dates").html("");
    const $container = $(".pvc-modal-chart-container");
    $container.removeClass("loading");
    $container.find(".spinner").removeClass("is-active");
    $(".pvc-modal-error").remove();
  }
  function loadChartData(postId, period) {
    const $container = $(".pvc-modal-chart-container");
    $container.addClass("loading");
    $container.find(".spinner").addClass("is-active");
    $.ajax({
      url: pvcColumnModal.ajaxURL,
      type: "POST",
      dataType: "json",
      data: {
        action: "pvc_column_chart",
        nonce: pvcColumnModal.nonce,
        post_id: postId,
        period
      },
      success: function(response) {
        if (response.success) {
          renderChart(response.data);
        } else {
          showError(response.data.message || pvcColumnModal.i18n.error);
        }
      },
      error: function(xhr, status, error) {
        showError(pvcColumnModal.i18n.error);
      },
      complete: function() {
        $container.removeClass("loading");
        $container.find(".spinner").removeClass("is-active");
      }
    });
  }
  function renderChart(data) {
    const ctx = document.getElementById("pvc-modal-chart");
    if (!ctx)
      return;
    if (pvcModalChart) {
      pvcModalChart.destroy();
      pvcModalChart = null;
    }
    $(".pvc-modal-error").remove();
    $(ctx).show();
    $(".pvc-modal-views-label").text(pvcColumnModal.i18n.summary);
    $(".pvc-modal-count").text(data.total_views.toLocaleString());
    $(".pvc-modal-dates").html(data.dates_html);
    const config = {
      type: "line",
      data: data.data,
      options: {
        maintainAspectRatio: false,
        responsive: true,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              title: function(context) {
                return data.data.dates[context[0].dataIndex];
              },
              label: function(context) {
                const count = context.parsed.y;
                const viewText = count === 1 ? pvcColumnModal.i18n.view : pvcColumnModal.i18n.views;
                return count.toLocaleString() + " " + viewText;
              }
            }
          }
        },
        scales: {
          x: {
            display: true,
            grid: {
              display: false
            }
          },
          y: {
            display: true,
            beginAtZero: true,
            ticks: {
              precision: 0
            },
            grid: {
              color: "rgba(0, 0, 0, 0.05)"
            }
          }
        }
      }
    };
    if (data.design) {
      config.data.datasets.forEach(function(dataset) {
        Object.assign(dataset, data.design);
        dataset.tension = 0.4;
      });
    }
    pvcModalChart = new Chart(ctx.getContext("2d"), config);
  }
  function showError(message) {
    if (pvcModalChart) {
      pvcModalChart.destroy();
      pvcModalChart = null;
    }
    $(".pvc-modal-summary").text("");
    $(".pvc-modal-dates").html("");
    const $container = $(".pvc-modal-chart-container");
    $container.removeClass("loading");
    $container.find(".spinner").removeClass("is-active");
    $(".pvc-modal-error").remove();
    $container.before('<p class="pvc-modal-error">' + message + "</p>");
    $container.find("canvas").hide();
  }
  $(function() {
    if ($("#pvc-chart-modal").length === 0)
      return;
    if (!initMicromodal())
      return;
    $(document).on("click", ".pvc-view-chart", function(e) {
      e.preventDefault();
      const postId = $(this).data("post-id");
      const postTitle = $(this).data("post-title");
      if (!postId)
        return;
      if (prepareModalForPost(postId, postTitle)) {
        if (typeof MicroModal !== "undefined") {
          MicroModal.show("pvc-chart-modal", {
            onShow: function(modal) {
              if (currentPostId)
                loadChartData(currentPostId, "");
            },
            onClose: function() {
              if (pvcModalChart) {
                pvcModalChart.destroy();
                pvcModalChart = null;
              }
              currentPostId = null;
              resetModalContent();
            },
            disableScroll: true,
            awaitCloseAnimation: true
          });
        }
      }
    });
    $(document).on("click", ".pvc-modal-nav-prev, .pvc-modal-nav-next", function(e) {
      e.preventDefault();
      if ($(this).hasClass("pvc-disabled"))
        return;
      const period = $(this).data("period");
      if (period && currentPostId) {
        loadChartData(currentPostId, period);
      }
    });
  });
})(jQuery);
