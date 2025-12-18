(function($) {
  window.addEventListener("load", function() {
    pvcUpdatePostViewsWidget();
    pvcUpdatePostMostViewedWidget();
  });
  $(function() {
    $(".pvc-accordion-header").on("click", function(e) {
      $(this).closest(".pvc-accordion-item").toggleClass("pvc-collapsed");
      const items = $("#pvc-dashboard-accordion").find(".pvc-accordion-item");
      const menuItems = {};
      if (items.length > 0) {
        $(items).each(function(index, item) {
          let itemName = $(item).attr("id");
          itemName = itemName.replace("pvc-", "");
          menuItems[itemName] = $(item).hasClass("pvc-collapsed");
        });
      }
      pvcUpdateUserOptions({ menu_items: menuItems });
    });
  });
  const pvcAjaxQueue = $({});
  $.pvcAjaxQueue = function(ajaxOpts) {
    let jqXHR;
    const dfd = $.Deferred();
    const promise = dfd.promise();
    function doRequest(next) {
      jqXHR = $.ajax(ajaxOpts);
      jqXHR.done(dfd.resolve).fail(dfd.reject).then(next, next);
    }
    pvcAjaxQueue.queue(doRequest);
    promise.abort = function(statusText) {
      if (jqXHR) {
        return jqXHR.abort(statusText);
      }
      const queue = pvcAjaxQueue.queue();
      const index = $.inArray(doRequest, queue);
      if (index > -1) {
        queue.splice(index, 1);
      }
      dfd.rejectWith(ajaxOpts.context || ajaxOpts, [promise, statusText, ""]);
      return promise;
    };
    return promise;
  };
  pvcUpdateUserOptions = function(options) {
    $.pvcAjaxQueue({
      url: pvcArgs.ajaxURL,
      type: "POST",
      dataType: "json",
      data: {
        action: "pvc_dashboard_user_options",
        nonce: pvcArgs.nonceUser,
        options
      },
      success: function() {
      }
    });
  };
  pvcUpdateConfig = function(config, args) {
    config.data = args.data;
    config.options.plugins.tooltip = {
      callbacks: {
        title: function(tooltip) {
          return args.data.dates[tooltip[0].dataIndex];
        }
      }
    };
    $.each(config.data.datasets, function(i, dataset) {
      dataset.fill = args.design.fill;
      dataset.tension = 0.4;
      dataset.borderColor = args.design.borderColor;
      dataset.backgroundColor = args.design.backgroundColor;
      dataset.borderWidth = args.design.borderWidth;
      dataset.borderDash = args.design.borderDash;
      dataset.pointBorderColor = args.design.pointBorderColor;
      dataset.pointBackgroundColor = args.design.pointBackgroundColor;
      dataset.pointBorderWidth = args.design.pointBorderWidth;
    });
    return config;
  };
  function pvcGetPostMostViewedData(init, period, container) {
    $(container).addClass("loading").find(".spinner").addClass("is-active");
    $.ajax({
      url: pvcArgs.ajaxURL,
      type: "POST",
      dataType: "json",
      data: {
        action: "pvc_dashboard_post_most_viewed",
        nonce: pvcArgs.nonce,
        period
      },
      success: function(response) {
        $(container).removeClass("loading");
        $(container).find(".spinner").removeClass("is-active");
        if (!init)
          pvcBindDateEvents(response.dates, container);
        $(container).find("#pvc-post-most-viewed-content").html(response.html);
        pvcTriggerEvent("pvc-dashboard-widget-loaded", response);
      }
    });
  }
  function pvcGetPostViewsData(init, period, container) {
    $(container).addClass("loading").find(".spinner").addClass("is-active");
    $.ajax({
      url: pvcArgs.ajaxURL,
      type: "POST",
      dataType: "json",
      data: {
        action: "pvc_dashboard_post_views_chart",
        nonce: pvcArgs.nonce,
        period,
        lang: pvcArgs.lang ? pvcArgs.lang : ""
      },
      success: function(response) {
        $(container).removeClass("loading");
        $(container).find(".spinner").removeClass("is-active");
        if (init) {
          let config = {
            type: "line",
            options: {
              maintainAspectRatio: false,
              responsive: true,
              plugins: {
                legend: {
                  display: true,
                  position: "bottom",
                  align: "center",
                  fullSize: true,
                  onHover: function(e) {
                    e.native.target.style.cursor = "pointer";
                  },
                  onLeave: function(e) {
                    e.native.target.style.cursor = "default";
                  },
                  onClick: function(e, element, legend) {
                    const index = element.datasetIndex;
                    const ci = legend.chart;
                    const meta = ci.getDatasetMeta(index);
                    if (ci.isDatasetVisible(index))
                      meta.hidden = true;
                    else
                      meta.hidden = false;
                    ci.update();
                    pvcUpdateUserOptions({
                      post_type: ci.data.datasets[index].post_type,
                      hidden: meta.hidden
                    });
                  },
                  labels: {
                    boxWidth: 8,
                    boxHeight: 8,
                    font: {
                      size: 13,
                      weight: "normal",
                      family: "'-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Oxygen-Sans', 'Ubuntu', 'Cantarell', 'Helvetica Neue', 'sans-serif'"
                    },
                    padding: 10,
                    usePointStyle: false,
                    textAlign: "center"
                  }
                }
              },
              scales: {
                x: {
                  display: true,
                  title: {
                    display: false
                  }
                },
                y: {
                  display: true,
                  grace: 0,
                  beginAtZero: true,
                  title: {
                    display: false
                  },
                  ticks: {
                    precision: 0,
                    maxTicksLimit: 12
                  }
                }
              },
              hover: {
                mode: "label"
              }
            }
          };
          config = pvcUpdateConfig(config, response);
          window.postViewsChart = new Chart(document.getElementById("pvc-post-views-chart").getContext("2d"), config);
        } else {
          pvcBindDateEvents(response.dates, container);
          window.postViewsChart.config = pvcUpdateConfig(window.postViewsChart.config, response);
          window.postViewsChart.update();
        }
        pvcTriggerEvent("pvc-dashboard-widget-loaded", response);
      }
    });
  }
  function pvcUpdatePostViewsWidget(period = "") {
    const container = $("#pvc-post-views").find(".pvc-dashboard-container");
    if ($(container).length > 0) {
      pvcBindDateEvents(false, container);
      pvcGetPostViewsData(true, period, container);
    }
  }
  function pvcUpdatePostMostViewedWidget(period = "") {
    const container = $("#pvc-post-most-viewed").find(".pvc-dashboard-container");
    if ($(container).length > 0) {
      pvcBindDateEvents(false, container);
      pvcGetPostMostViewedData(true, period, container);
    }
  }
  function pvcBindDateEvents(newDates, container) {
    const dates = $(container).find(".pvc-date-nav");
    if (newDates !== false)
      dates[0].innerHTML = newDates;
    const prev = dates[0].getElementsByClassName("prev")[0];
    const next = dates[0].getElementsByClassName("next")[0];
    const id = $(container).closest(".pvc-accordion-item").attr("id");
    if (id === "pvc-post-most-viewed")
      prev.addEventListener("click", function(e) {
        e.preventDefault();
        pvcLoadPostMostViewedData(e.target.dataset.date);
      });
    else if (id === "pvc-post-views")
      prev.addEventListener("click", function(e) {
        e.preventDefault();
        pvcLoadPostViewsData(e.target.dataset.date);
      });
    if (next.tagName === "A") {
      if (id === "pvc-post-most-viewed")
        next.addEventListener("click", function(e) {
          e.preventDefault();
          pvcLoadPostMostViewedData(e.target.dataset.date);
        });
      else if (id === "pvc-post-views")
        next.addEventListener("click", function(e) {
          e.preventDefault();
          pvcLoadPostViewsData(e.target.dataset.date);
        });
    }
  }
  function pvcLoadPostViewsData(period = "") {
    const container = $("#pvc-post-views").find(".pvc-dashboard-container");
    pvcGetPostViewsData(false, period, container);
  }
  function pvcLoadPostMostViewedData(period = "") {
    const container = $("#pvc-post-most-viewed").find(".pvc-dashboard-container");
    pvcGetPostMostViewedData(false, period, container);
  }
  function pvcTriggerEvent(name, response) {
    const remove = ["dates", "html", "design"];
    remove.forEach(function(prop) {
      delete response[prop];
    });
    const event = new CustomEvent(name, {
      detail: response
    });
    window.dispatchEvent(event);
  }
})(jQuery);
