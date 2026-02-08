var massifUsability = (($) => {
	("use strict");

	$(document).on("rex:ready rex:selectMedia rex:YForm_selectData", () => {
		uniqueMultiSelect();
		sortMultiSelect();
		mediaPreviews();

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

	$(document).on("shown.bs.tab", () => {
		setTimeout(() => {
			window.dispatchEvent(new Event("resize"));
		}, 500);
	});

	$(document).on("rex:ready", (event, container) => {
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
			callback,
		);
	}

	function initStatusToggle(container) {
		// status toggle
		if (container.find(".status-toggle").length) {
			var statusToggle = function () {
				var $this = $(this);

				updateDatasetStatus($this, $this.data("status"), (resp) => {
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

				updateDatasetStatus($this, $this.val(), (resp) => {
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
			callback,
		);
	}

	function initDuplicateTriggers(container) {
		// initDuplicateTriggers
		if (container.find(".duplicate-trigger").length) {
			var duplicateTrigger = function () {
				var $this = $(this);

				duplicateDataset($this, $this.data("id"), (resp) => {
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
			callback,
		);
	}

	function apiCall(method, data, callback) {
		$("#rex-js-ajax-loader").addClass("rex-visible");
		const { pathname, search } = location;
		const url = `${pathname}${search}&rex-api-call=massif_usability&method=${method}`;
		$.post(url, { ...data }, async (resp) => {
			const json = await JSON.parse(resp);
			callback(json);
			$("#rex-js-ajax-loader").removeClass("rex-visible");
		});
	}

	function initCustomToggles(container) {
		const $toggles = container.find(".custom-toggle");
		$toggles.each(function () {
			const $this = $(this);
			var customToggle = function () {
				var $_this = $(this);
				updateDatasetCustom($_this, async (json) => {
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

			my_options.sort((a, b) => {
				var _a = a.text.replace(regex, (match) => replaceChars[match]);
				var _b = b.text.replace(regex, (match) => replaceChars[match]);
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
		const imgExtensions = ["jpg", "jpeg", "gif", "png", "bmp", "webp"];
		const vectorExtensions = ["svg", "eps", "ai"];
		const videoExtensions = ["mp4", "webm", "ogg"];

		const $mediaInputs = $(".rex-js-widget-media");
		const $mediaListInputs = $(".rex-js-widget-medialist");
		const descriptor = Object.getOwnPropertyDescriptor(
			HTMLInputElement.prototype,
			"value",
		);

		const preview = (element) => {
			const $element = $(element);
			var file = $element.find("input").val();
			const $thumb = $element.find("img.thumbnail");
			if ($thumb.length) {
				$thumb.remove();
			}
			//console.log(file);
			if (!file) return;
			//console.log($div.data('file'), file);
			if ($element.data("file") === file) return;
			$element.data("file", file);
			var ext = file.split(".").pop();

			if ($.inArray(ext, imgExtensions) !== -1) {
				$element.prepend(
					'<img src="index.php?rex_media_type=rex_mediapool_preview&rex_media_file=' +
						file +
						'" class="thumbnail" style="max-height: 34px" />',
				);
			} else if ($.inArray(ext, vectorExtensions) !== -1) {
				$element.prepend(
					'<img src="/media/' +
						file +
						'" class="thumbnail" style="max-height: 34px" />',
				);
			} else if ($.inArray(ext, videoExtensions) !== -1) {
				$element.prepend(
					'<video class="thumbnail" style="max-height: 34px" muted loop autoplay><source src="/media/' +
						file +
						'" type="video/' +
						ext +
						'"></video>',
				);
			}
			$element.addClass("massif-preview");
		};

		$mediaInputs.each(function () {
			const self = this;
			preview(self);
			const input = self.querySelector("input");
			if (!input || !descriptor || !descriptor.configurable) {
				return;
			}
			try {
				Object.defineProperty(input, "value", {
					get() {
						return descriptor.get.call(this);
					},
					set(val) {
						descriptor.set.call(this, val);
						preview(self);
					},
				});
			} catch (e) {}
		});

		$mediaListInputs.each(function () {
			const $this = $(this);
			const hasPreview = $this.find("select[data-preview]");
			if (hasPreview.length == 0) return;
			$this.addClass("massif-preview");
		});

		function rexShowMediaListPreview(event) {
			const div = $(".rex-js-media-preview", this);
			if (event.type === "mouseleave") {
				if (div.css("height") != "auto") {
					div.find("*").remove();
				}
				return;
			}
			const value = $("select :selected", this).text();
			const img_type = "rex_media_small";

			let url;
			const extension = value.split(".").pop().toLowerCase();
			const videoExtensions = ["mp4", "webm", "ogg"];
			if (!["svg", ...videoExtensions].includes(extension))
				url =
					"./index.php?rex_media_type=" + img_type + "&rex_media_file=" + value;
			else {
				url = "../media/" + value;
			}

			if (
				value &&
				value.length != 0 &&
				rex.imageExtensions.includes(extension)
			) {
				// img tag nur einmalig einfuegen, ggf erzeugen wenn nicht vorhanden
				let img = $("img", div);
				if (img.length == 0) {
					div.html("<img />");
					img = $("img", div);
				}
				img.attr("src", url);
				div.css("display", "block");
			} else if (
				value &&
				value.length != 0 &&
				videoExtensions.includes(extension)
			) {
				const video = document.createElement("video");
				video.setAttribute("src", url);
				video.setAttribute("muted", "muted");
				video.setAttribute("loop", "loop");
				video.setAttribute("autoplay", "autoplay");
				// video.setAttribute('width', width || 246);
				div.html(video);
				div.css("display", "block");
			}
		}

		// Medialist preview neu anzeigen, beim wechsel der auswahl
		$("body")
			.on(
				"click",
				".rex-js-widget-medialist.massif-preview",
				rexShowMediaListPreview,
			)
			.on(
				"mouseenter",
				".rex-js-widget-medialist.massif-preview",
				rexShowMediaListPreview,
			)
			.on(
				"mouseleave",
				".rex-js-widget-medialist.massif-preview",
				rexShowMediaListPreview,
			);
	}

	function selectizeMassif() {
		Selectize.define("silent_drag_and_drop", function (options) {
			// defang the internal search method when change has been emitted
			this.on("change", function () {
				this.plugin_silent_drag_and_drop_in_change = true;
			});

			this.search = (() => {
				var original = this.search;
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
