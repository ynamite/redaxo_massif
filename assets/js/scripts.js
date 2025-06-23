var massifUsability = (function ($) {
  ("use strict");

  $(document).on("rex:ready rex:selectMedia rex:YForm_selectData", function () {
    uniqueMultiSelect();
    sortMultiSelect();
    setTimeout(function () {
      mediaPreviews();
    }, 50);

    selectizeMassif();

    var settings = {
      animationSpeed: 50,
      animationEasing: "swing",
      change: null,
      changeDelay: 0,
      control: "hue",
      defaultValue: "",
      format: "hex",
      hide: null,
      hideSpeed: 100,
      inline: false,
      keywords: "",
      letterCase: "lowercase",
      opacity: true,
      position: "bottom left",
      show: null,
      showSpeed: 100,
      theme: "bootstrap",
      swatches: [],
    };

    $("input.minicolors-massif").minicolors(settings);

    $("[data-copy-to-clipboard]").on("click", function (e) {
      e.preventDefault();
      var $this = $(this);
      var text = $this.data("copy-to-clipboard");
      copyToClipboard(text);
    });
  });

  $(document).on("shown.bs.tab", function () {
    setTimeout(function () {
      window.dispatchEvent(new Event("resize"));
    }, 500);
  });

  $(document).on("rex:ready", function (event, container) {
    initStatusToggle(container);
    initCustomToggles(container);
    initDuplicateTriggers(container);
  });

  function updateDatasetStatus($this, status, callback) {
    apiCall(
      "changeStatus",
      {
        data_id: $this.data("id"),
        table: $this.data("table"),
        status: status,
      },
      callback
    );
  }

  function initStatusToggle(container) {
    // status toggle
    if (container.find(".status-toggle").length) {
      var statusToggle = function () {
        var $this = $(this);

        updateDatasetStatus($this, $this.data("status"), function (resp) {
          var $parent = $this.parent();
          $parent.html(resp.message.element);
          $parent.children("a:first").click(statusToggle);
        });
        return false;
      };
      container.find(".status-toggle").click(statusToggle);
    }

    // status select
    if (container.find(".status-select").length) {
      var statusChange = function () {
        var $this = $(this);

        updateDatasetStatus($this, $this.val(), function (resp) {
          var $parent = $this.parent();
          $parent.html(resp.message.element);
          $parent.children("select:first").change(statusChange);
        });
      };
      container.find(".status-select").change(statusChange);
    }
  }

  function duplicateDataset($this, id, callback) {
    apiCall(
      "duplicate",
      {
        data_id: id,
        table: $this.data("table"),
      },
      callback
    );
  }

  function initDuplicateTriggers(container) {
    // initDuplicateTriggers
    if (container.find(".duplicate-trigger").length) {
      var duplicateTrigger = function () {
        var $this = $(this);

        duplicateDataset($this, $this.data("id"), function (resp) {
          window.location.href = window.location.href;
        });
        return false;
      };
      container.find(".duplicate-trigger").click(duplicateTrigger);
    }
  }

  function updateDatasetCustom($this, callback) {
    apiCall(
      "changeCustom",
      {
        data_id: $this.data("id"),
        name: $this.data("name"),
        table: $this.data("table"),
        value: $this.data("value"),
      },
      callback
    );
  }

  function apiCall(method, data, callback) {
    $("#rex-js-ajax-loader").addClass("rex-visible");
    const { pathname, search } = location;
    const url = `${pathname}${search}&rex-api-call=massif_usability&method=${method}`;
    $.post(url, { ...data }, async function (resp) {
      const json = await JSON.parse(resp);
      callback(json);
      $("#rex-js-ajax-loader").removeClass("rex-visible");
    });
  }

  function initCustomToggles(container) {
    let $toggles = container.find(".custom-toggle");
    $toggles.each(function () {
      let $this = $(this);
      var customToggle = function () {
        var $_this = $(this);
        updateDatasetCustom($_this, async function (json) {
          var $parent = $_this.parent();
          $parent.html(json.message.element);
          $parent.children("a:first").click(customToggle);
        });
        return false;
      };
      $this.click(customToggle);
    });
  }

  // sort select // sort-select unique-select
  function sortMultiSelect() {
    var $selects = $("select.sort-select");

    var replaceChars = { ü: "u", ö: "o", ä: "a", è: "e", é: "e", à: "a" };
    var regex = new RegExp(Object.keys(replaceChars).join("|"), "g");

    $selects.each(function (idx) {
      var $select = $(this);
      var my_options = $select.find("option");
      var selected = $select.val();

      my_options.sort(function (a, b) {
        var _a = a.text.replace(regex, function (match) {
          return replaceChars[match];
        });
        var _b = b.text.replace(regex, function (match) {
          return replaceChars[match];
        });
        if (_a > _b) return 1;
        if (_a < _b) return -1;
        return 0;
      });

      $select.empty().append(my_options);
      $select.val(selected);
    });
  }

  function uniqueMultiSelect() {
    var $selects = $("select.unique-select");

    $selects.each(function (idx) {
      var $select = $(this);
      var usedVals = {};
      $select.find("> option").each(function () {
        if (usedVals[this.value]) {
          $(this).remove();
        } else {
          usedVals[this.value] = this.value;
        }
      });
    });
  }

  function mediaPreviews() {
    var imgFormats = ["jpg", "jpeg", "gif", "png", "bmp", "webp"];
    var vectorFormats = ["svg", "eps", "ai"];

    var $mediaInputs = $(".rex-js-widget-media");

    var preview = function ($div) {
      var file = $div.find("input").val();
      //console.log(file);
      if (!file) return;
      //console.log($div.data('file'), file);
      if ($div.data("file") === file) return;
      $div.find(".thumbnail").remove();
      $div.data("file", file);
      var ext = file.split(".").pop();

      if ($.inArray(ext, imgFormats) !== -1) {
        $div.prepend(
          '<img src="index.php?rex_media_type=rex_mediapool_preview&rex_media_file=' +
            file +
            '" class="thumbnail" style="max-height: 34px" />'
        );
      } else if ($.inArray(ext, vectorFormats) !== -1) {
        $div.prepend(
          '<img src="/media/' +
            file +
            '" class="thumbnail" style="max-height: 34px" />'
        );
      }
      $div.addClass("massif-preview");
    };

    $mediaInputs.each(function (idx) {
      var $div = $(this);
      preview($div);
      $div.on("mouseenter mouseleave", function () {
        preview($div);
      });
    });
  }

  function selectizeMassif() {
    Selectize.define("silent_drag_and_drop", function (options) {
      var self = this;
      // defang the internal search method when change has been emitted
      this.on("change", function () {
        this.plugin_silent_drag_and_drop_in_change = true;
      });

      this.search = (function () {
        var original = self.search;
        return function () {
          if (
            typeof this.plugin_silent_drag_and_drop_in_change !== "undefined"
          ) {
            // re-enable normal searching
            delete this.plugin_silent_drag_and_drop_in_change;
            return {
              items: {},
              query: [],
              tokens: [],
            };
          } else {
            return original.apply(this, arguments);
          }
        };
      })();
    });

    var $el = $(".selectize-massif");
    $el.selectize({
      plugins: ["silent_drag_and_drop", "drag_drop", "remove_button"],
      delimiter: ",",
      persist: false /*,
        create: function(input) {
            return {
                value: input,
                text: input
            }
        }*/,
    });
  }

  async function copyToClipboard(text) {
    // Secure-context & modern API available?
    if (navigator.clipboard && window.isSecureContext) {
      try {
        await navigator.clipboard.writeText(text);
        alert("✅ Copied to clipboard!");
      } catch (err) {
        alert("❌ Clipboard write failed:", err);
      }
    } else {
      // fallback for non-HTTPS or older browsers
      const textarea = document.createElement("textarea");
      textarea.value = text;
      // avoid scrolling to bottom
      textarea.style.position = "fixed";
      textarea.style.top = 0;
      textarea.style.left = 0;
      textarea.style.width = "1px";
      textarea.style.height = "1px";
      textarea.style.padding = 0;
      textarea.style.border = "none";
      textarea.style.outline = "none";
      textarea.style.boxShadow = "none";
      textarea.style.background = "transparent";
      document.body.appendChild(textarea);
      textarea.focus();
      textarea.select();

      try {
        document.execCommand("copy");
        alert("✅ Fallback copy succeeded");
      } catch (err) {
        alert("❌ Fallback copy failed:", err);
      }
      document.body.removeChild(textarea);
    }
  }
})(jQuery);
