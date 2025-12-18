const initPostViewsCounter = function() {
  PostViewsCounter = {
    promise: null,
    args: {},
    /**
     * Initialize counter.
     *
     * @param {Object} args
     * @return {void}
     */
    init: function(args) {
      this.args = args;
      const params = {};
      params.storage_type = "cookies";
      params.storage_data = this.readCookieData("pvc_visits" + (args.multisite !== false ? "_" + parseInt(args.multisite) : ""));
      if (args.mode === "rest_api") {
        this.promise = this.request(args.requestURL, params, "POST", {
          "Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
          "X-WP-Nonce": args.nonce
        });
      } else {
        params.action = "pvc-check-post";
        params.pvc_nonce = args.nonce;
        params.id = args.postID;
        this.promise = this.request(args.requestURL, params, "POST", {
          "Content-Type": "application/x-www-form-urlencoded; charset=utf-8"
        });
      }
    },
    /**
     * Handle fetch request.
     *
     * @param {string} url
     * @param {Object} params
     * @param {string} method
     * @param {Object} headers
     * @return {Promise}
     */
    request: function(url, params, method, headers) {
      const options = {
        method,
        mode: "cors",
        cache: "no-cache",
        credentials: "same-origin",
        headers,
        body: this.prepareRequestData(params)
      };
      const _this = this;
      return fetch(url, options).then(function(response) {
        if (!response.ok)
          throw Error(response.statusText);
        return response.json();
      }).then(function(response) {
        try {
          if (typeof response === "object" && response !== null) {
            if ("success" in response && response.success === false) {
              console.log("PVC: Request error.");
              console.log(response.data);
            } else {
              _this.saveCookieData(response.storage);
              _this.triggerEvent("pvcCheckPost", response);
            }
          } else {
            console.log("PVC: Invalid object.");
            console.log(response);
          }
        } catch (error) {
          console.log("PVC: Invalid JSON data.");
          console.log(error);
        }
      }).catch(function(error) {
        console.log("PVC: Invalid response.");
        console.log(error);
      });
    },
    /**
     * Prepare the data to be sent with the request.
     *
     * @param {Object} data
     * @return {string}
     */
    prepareRequestData: function(data) {
      return Object.keys(data).map(function(el) {
        return encodeURIComponent(el) + "=" + encodeURIComponent(data[el]);
      }).join("&").replace(/%20/g, "+");
    },
    /**
     * Trigger a custom DOM event.
     *
     * @param {string} eventName
     * @param {Object} data
     * @return {void}
     */
    triggerEvent: function(eventName, data) {
      const newEvent = new CustomEvent(eventName, {
        bubbles: true,
        detail: data
      });
      document.dispatchEvent(newEvent);
    },
    /**
     * Save cookies.
     *
     * @param {Object} data
     * @return {void}
     */
    saveCookieData: function(data) {
      if (!data.hasOwnProperty("name"))
        return;
      let cookieSecure = "";
      if (document.location.protocol === "https:")
        cookieSecure = ";secure";
      for (let i = 0; i < data.name.length; i++) {
        const cookieDate = /* @__PURE__ */ new Date();
        let expiration = parseInt(data.expiry[i]);
        if (expiration)
          expiration = expiration * 1e3;
        else
          expiration = cookieDate.getTime() + 864e5;
        cookieDate.setTime(expiration);
        document.cookie = data.name[i] + "=" + data.value[i] + ";expires=" + cookieDate.toUTCString() + ";path=/" + (this.args.path === "/" ? "" : this.args.path) + ";domain=" + this.args.domain + cookieSecure + ";SameSite=Lax";
      }
    },
    /**
     * Read cookies.
     *
     * @param {string} name
     * @return {string}
     */
    readCookieData: function(name) {
      const cookies = [];
      document.cookie.split(";").forEach(function(el) {
        const parts = el.split("=");
        const key = parts[0];
        const value = parts[1];
        const trimmedKey = key.trim();
        const regex = new RegExp(name + "\\[\\d+\\]");
        if (regex.test(trimmedKey))
          cookies.push(value);
      });
      return cookies.join("a");
    }
  };
  PostViewsCounter.init(pvcArgsFrontend);
};
document.addEventListener("DOMContentLoaded", initPostViewsCounter);
