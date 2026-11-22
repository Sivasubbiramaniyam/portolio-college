/* =====================================================================
   UCEN Faculty Portal — progressive enhancements for PHP pages
   ---------------------------------------------------------------------
   No frameworks. Everything here is optional: every page still works
   without JavaScript (forms post normally, collections can be edited as
   rendered). The script only adds convenience behaviour.
   ===================================================================== */

(function () {
  "use strict";

  var doc = document;
  var suppressUnsavedWarning = false;

  /* ---------------------------------------------------------------
     Small helpers
     --------------------------------------------------------------- */

  function on(target, event, handler, options) {
    if (target) {
      target.addEventListener(event, handler, options || false);
    }
  }

  function all(selector, root) {
    return Array.prototype.slice.call((root || doc).querySelectorAll(selector));
  }

  function closest(element, selector) {
    while (element && element.nodeType === 1) {
      if (element.matches(selector)) {
        return element;
      }
      element = element.parentElement;
    }
    return null;
  }

  /* ---------------------------------------------------------------
     Toast notifications
     --------------------------------------------------------------- */

  var toastIcons = {
    success: "check2-circle",
    error: "exclamation-octagon-fill",
    info: "info-circle-fill"
  };

  function toast(message, tone) {
    var stack = doc.getElementById("toast-stack");
    if (!stack || !message) {
      return;
    }

    var normalizedTone = tone === "error" || tone === "info" ? tone : "success";
    var node = doc.createElement("div");
    node.className = "toast toast--" + normalizedTone;
    node.setAttribute("role", normalizedTone === "error" ? "alert" : "status");

    var icon = doc.createElement("i");
    icon.className = "bi bi-" + toastIcons[normalizedTone];
    icon.setAttribute("aria-hidden", "true");

    var text = doc.createElement("span");
    text.textContent = message;

    node.appendChild(icon);
    node.appendChild(text);
    stack.appendChild(node);
    window.requestAnimationFrame(function () {
      node.classList.add("show");
    });

    window.setTimeout(function () {
      node.classList.add("is-leaving");
      window.setTimeout(function () {
        if (node.parentNode) {
          node.parentNode.removeChild(node);
        }
      }, 240);
    }, 3000);
  }

  function initServerToasts() {
    all("[data-server-toast]").forEach(function (node) {
      window.setTimeout(function () {
        node.classList.add("is-leaving");
        window.setTimeout(function () {
          if (node.parentNode) {
            node.parentNode.removeChild(node);
          }
        }, 240);
      }, 3000);
    });
  }

  window.portalToast = toast;

  /* ---------------------------------------------------------------
     Sidebar (expand / collapse, mobile backdrop)
     --------------------------------------------------------------- */

  function initSidebar() {
    var sidebar = doc.getElementById("sidebar");
    var toggle = doc.getElementById("sidebar-toggle");
    var backdrop = doc.getElementById("sidebar-backdrop");
    if (!sidebar || !toggle) {
      return;
    }

    function setExpanded(expanded) {
      sidebar.classList.toggle("is-expanded", expanded);
      doc.body.classList.toggle("sidebar-expanded", expanded);
      toggle.setAttribute("aria-expanded", expanded ? "true" : "false");
      toggle.setAttribute("title", expanded ? "Collapse sidebar" : "Expand sidebar");

      var icon = doc.getElementById("sidebar-toggle-icon");
      if (icon) {
        icon.className = icon.className.replace(/bi-chevron-(right|left)/, expanded ? "bi-chevron-left" : "bi-chevron-right");
      }

      var label = toggle.querySelector(".sidebar__toggle-label");
      if (label) {
        label.textContent = expanded ? "Collapse" : "Expand";
      }
    }

    on(toggle, "click", function () {
      setExpanded(!sidebar.classList.contains("is-expanded"));
    });

    on(backdrop, "click", function () {
      setExpanded(false);
    });

    on(doc, "keydown", function (event) {
      if (event.key === "Escape" && sidebar.classList.contains("is-expanded") && window.innerWidth <= 900) {
        setExpanded(false);
      }
    });

    if (window.innerWidth <= 900) {
      setExpanded(false);
    }
  }

  /* ---------------------------------------------------------------
     Step the sidebar list one item at a time with a hidden scrollbar:
     the mouse wheel and the up/down arrow keys move to the next item.

     When the sidebar is a table of contents (its items point at sections
     on this page), the sidebar and the page move together: stepping the
     list scrolls the page to that section, and scrolling the page keeps
     the matching sidebar item highlighted and in view.
     --------------------------------------------------------------- */

  function initSidebarScroll() {
    var nav = doc.querySelector(".sidebar__nav");
    if (!nav) {
      return;
    }

    var items = all("[data-sidebar-item]", nav);
    if (!items.length) {
      return;
    }

    var pad = 6;
    var lastWheel = 0;

    // Resolve each item to the in-page section it points at (if any).
    var targets = items.map(function (item) {
      var href = item.getAttribute("href") || "";
      if (href.charAt(0) !== "#" || href === "#") {
        return null;
      }
      return doc.getElementById(href.slice(1));
    });
    var tocMode = targets.some(function (target) {
      return Boolean(target);
    });

    function hasOverflow() {
      return nav.scrollHeight > nav.clientHeight + 1;
    }

    function nearestIndex(offset) {
      var navTop = nav.getBoundingClientRect().top;
      var best = 0;
      var bestDist = Infinity;
      items.forEach(function (item, index) {
        var distance = Math.abs(item.getBoundingClientRect().top - navTop - pad);
        if (distance < bestDist) {
          bestDist = distance;
          best = index;
        }
      });
      return Math.min(items.length - 1, Math.max(0, best + offset));
    }

    function goto(index, smooth) {
      var target = items[Math.min(items.length - 1, Math.max(0, index))];
      if (!target) {
        return;
      }
      var delta = target.getBoundingClientRect().top - nav.getBoundingClientRect().top - pad;
      nav.scrollBy({ top: delta, behavior: smooth ? "smooth" : "auto" });
    }

    function setActive(index) {
      items.forEach(function (item, i) {
        var active = i === index;
        item.classList.toggle("is-active", active);
        if (active) {
          item.setAttribute("aria-current", "true");
        } else {
          item.removeAttribute("aria-current");
        }
      });
    }

    // Index of the section currently at the top of the viewport, or -1
    // while the page is still above the first section (the hero area).
    function currentSection() {
      var line = 30;
      var found = -1;
      targets.forEach(function (target, index) {
        if (target && target.getBoundingClientRect().top - line <= 0) {
          found = index;
        }
      });
      return found;
    }

    function goToSection(index) {
      if (index < 0) {
        setActive(-1);
        goto(0, true);
        window.scrollTo({ top: 0, behavior: "smooth" });
        return;
      }
      var clamped = Math.min(items.length - 1, Math.max(0, index));
      var target = targets[clamped];
      if (!target) {
        return;
      }
      setActive(clamped);
      goto(clamped, true);
      target.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    function step(direction) {
      if (!tocMode) {
        goto(nearestIndex(direction), true);
        return;
      }

      var current = currentSection();
      var next = current + direction;
      // Scrolling up from partway down a section snaps to that section's top
      // before moving on to the previous one.
      if (direction < 0 && current >= 0 && targets[current]) {
        if (targets[current].getBoundingClientRect().top < 0) {
          next = current;
        }
      }
      goToSection(next);
    }

    on(nav, "wheel", function (event) {
      if (!hasOverflow()) {
        return;
      }
      event.preventDefault();
      var now = Date.now();
      if (now - lastWheel < 120) {
        return;
      }
      lastWheel = now;
      step(event.deltaY > 0 ? 1 : -1);
    }, { passive: false });

    on(doc, "keydown", function (event) {
      if (event.key !== "ArrowDown" && event.key !== "ArrowUp") {
        return;
      }
      var target = event.target;
      if (target && (target.tagName === "INPUT" || target.tagName === "TEXTAREA" || target.tagName === "SELECT" || target.isContentEditable)) {
        return;
      }
      if (!tocMode && !hasOverflow()) {
        return;
      }
      event.preventDefault();
      step(event.key === "ArrowDown" ? 1 : -1);
    });

    // Keep the sidebar highlight (and the visible slice of the list) in
    // step with the page as the reader scrolls it.
    if (tocMode) {
      var ticking = false;
      var sync = function () {
        ticking = false;
        var current = currentSection();
        setActive(current);
        // Above the first section the list sits back at its start.
        goto(current < 0 ? 0 : current, false);
      };
      on(window, "scroll", function () {
        if (!ticking) {
          ticking = true;
          window.requestAnimationFrame(sync);
        }
      }, { passive: true });
      sync();
    }
  }

  /* ---------------------------------------------------------------
     Password visibility toggles
     --------------------------------------------------------------- */

  function initPasswordToggles() {
    all("[data-toggle-password]").forEach(function (button) {
      var input = doc.getElementById(button.getAttribute("data-toggle-password"));
      if (!input) {
        return;
      }

      on(button, "click", function () {
        var show = input.type === "password";
        input.type = show ? "text" : "password";
        button.setAttribute("aria-label", show ? "Hide password" : "Show password");
        button.setAttribute("aria-pressed", show ? "true" : "false");
        var icon = button.querySelector("i");
        if (icon) {
          icon.className = icon.className.replace(/bi-eye(-slash)?/, show ? "bi-eye-slash" : "bi-eye");
        }
      });
    });
  }

  /* ---------------------------------------------------------------
     Print buttons
     --------------------------------------------------------------- */

  function initPrint() {
    all("[data-print-page]").forEach(function (button) {
      on(button, "click", function (event) {
        event.preventDefault();
        window.print();
      });
    });
  }

  /* ---------------------------------------------------------------
     Confirm destructive actions
     --------------------------------------------------------------- */

  function showConfirm(message) {
    return new Promise(function (resolve) {
      var backdrop = doc.createElement("div");
      backdrop.className = "confirm-backdrop";

      var dialog = doc.createElement("div");
      dialog.className = "confirm-dialog";
      dialog.setAttribute("role", "alertdialog");
      dialog.setAttribute("aria-modal", "true");

      var icon = doc.createElement("span");
      icon.className = "confirm-dialog__icon";
      icon.setAttribute("aria-hidden", "true");
      icon.innerHTML = '<i class="bi bi-exclamation-triangle"></i>';

      var text = doc.createElement("p");
      text.className = "confirm-dialog__message";
      text.textContent = message;

      var actions = doc.createElement("div");
      actions.className = "confirm-dialog__actions";

      var no = doc.createElement("button");
      no.type = "button";
      no.className = "btn btn-ghost";
      no.textContent = "No";

      var yes = doc.createElement("button");
      yes.type = "button";
      yes.className = "btn btn-danger";
      yes.textContent = "Yes";

      actions.appendChild(no);
      actions.appendChild(yes);
      dialog.appendChild(icon);
      dialog.appendChild(text);
      dialog.appendChild(actions);
      backdrop.appendChild(dialog);
      doc.body.appendChild(backdrop);

      function close(result) {
        if (backdrop.parentNode) {
          backdrop.parentNode.removeChild(backdrop);
        }
        doc.removeEventListener("keydown", onKey);
        resolve(result);
      }

      function onKey(event) {
        if (event.key === "Escape") {
          close(false);
        }
      }

      on(no, "click", function () { close(false); });
      on(yes, "click", function () { close(true); });
      on(backdrop, "click", function (event) {
        if (event.target === backdrop) {
          close(false);
        }
      });
      doc.addEventListener("keydown", onKey);
      yes.focus();
      window.requestAnimationFrame(function () {
        backdrop.classList.add("is-open");
      });
    });
  }

  function initConfirmations() {
    all("[data-confirm]").forEach(function (element) {
      var message = element.getAttribute("data-confirm") || "Are you sure?";

      if (element.tagName === "FORM") {
        on(element, "submit", function (event) {
          if (event.defaultPrevented) {
            return;
          }
          event.preventDefault();
          showConfirm(message).then(function (confirmed) {
            if (confirmed) {
              element.submit();
            }
          });
        });
        return;
      }

      if (element.tagName === "A") {
        on(element, "click", function (event) {
          event.preventDefault();
          showConfirm(message).then(function (confirmed) {
            if (confirmed) {
              window.location.href = element.href;
            }
          });
        });
        return;
      }

      var form = closest(element, "form");
      if (form) {
        on(form, "submit", function (event) {
          if (event.defaultPrevented) {
            return;
          }
          event.preventDefault();
          showConfirm(message).then(function (confirmed) {
            if (confirmed) {
              form.submit();
            }
          });
        });
      }
    });
  }

  /* ---------------------------------------------------------------
     Image / upload previews + position adjuster
     --------------------------------------------------------------- */

  function setPreview(input) {
    var selector = input.getAttribute("data-preview-image");
    var emptySelector = input.getAttribute("data-preview-empty");
    if (!selector) {
      return;
    }

    var image = doc.querySelector(selector);
    var empty = emptySelector ? doc.querySelector(emptySelector) : null;
    var file = input.files && input.files[0];

    if (!file) {
      return;
    }

    if (file.type && file.type.indexOf("image/") !== 0) {
      toast("Choose a JPEG, PNG or WebP image.", "error");
      input.value = "";
      return;
    }

    if (image) {
      if (image.dataset.objectUrl) {
        URL.revokeObjectURL(image.dataset.objectUrl);
      }
      var url = URL.createObjectURL(file);
      image.dataset.objectUrl = url;
      image.src = url;
      image.hidden = false;
    }

    if (empty) {
      empty.hidden = true;
    }
  }

  var imageAdjuster = null;

  function ensureImageAdjuster() {
    if (imageAdjuster) {
      return imageAdjuster;
    }

    var root = doc.createElement("div");
    root.className = "media-adjust";
    root.hidden = true;
    root.innerHTML = ''
      + '<div class="media-adjust__dialog" role="dialog" aria-modal="true" aria-labelledby="media-adjust-title">'
      + '<header class="media-adjust__head">'
      + '<div><h2 id="media-adjust-title" data-adjust-title>Adjust image</h2>'
      + '<p data-adjust-hint>Drag to position, zoom to frame, then apply.</p></div>'
      + '<button type="button" class="media-adjust__close" data-adjust-cancel aria-label="Close adjuster"><i class="bi bi-x-lg" aria-hidden="true"></i></button>'
      + '</header>'
      + '<div class="media-adjust__stage"><canvas data-adjust-canvas width="900" height="900"></canvas></div>'
      + '<div class="media-adjust__controls">'
      + '<label class="media-adjust__zoom">Zoom <input type="range" min="1" max="3" step="0.01" value="1" data-adjust-zoom></label>'
      + '<span class="media-adjust__value" data-adjust-zoom-value>100%</span>'
      + '<button type="button" class="btn btn-ghost btn-sm" data-adjust-reset>Reset</button>'
      + '</div>'
      + '<footer class="media-adjust__actions">'
      + '<button type="button" class="btn btn-ghost" data-adjust-cancel>Cancel</button>'
      + '<button type="button" class="btn btn-primary" data-adjust-apply>Apply image</button>'
      + '</footer>'
      + '</div>';
    doc.body.appendChild(root);

    var state = {
      root: root,
      canvas: root.querySelector("[data-adjust-canvas]"),
      zoom: root.querySelector("[data-adjust-zoom]"),
      zoomValue: root.querySelector("[data-adjust-zoom-value]"),
      title: root.querySelector("[data-adjust-title]"),
      hint: root.querySelector("[data-adjust-hint]"),
      image: null,
      input: null,
      mode: "photo",
      mime: "image/jpeg",
      scale: 1,
      offsetX: 0,
      offsetY: 0,
      dragging: false,
      startX: 0,
      startY: 0,
      startOffsetX: 0,
      startOffsetY: 0,
      tempUrl: null,
      fromFileSelection: false
    };

    function baseSize(canvasWidth, canvasHeight) {
      if (!state.image) {
        return { width: canvasWidth, height: canvasHeight };
      }
      var imageRatio = state.image.naturalWidth / state.image.naturalHeight;
      var canvasRatio = canvasWidth / canvasHeight;
      if (imageRatio > canvasRatio) {
        return { width: canvasHeight * imageRatio, height: canvasHeight };
      }
      return { width: canvasWidth, height: canvasWidth / imageRatio };
    }

    function clampOffsets(canvasWidth, canvasHeight) {
      var base = baseSize(canvasWidth, canvasHeight);
      var maxX = Math.max(0, (base.width * state.scale - canvasWidth) / 2);
      var maxY = Math.max(0, (base.height * state.scale - canvasHeight) / 2);
      state.offsetX = Math.min(maxX, Math.max(-maxX, state.offsetX));
      state.offsetY = Math.min(maxY, Math.max(-maxY, state.offsetY));
    }

    function draw() {
      if (!state.image) {
        return;
      }
      var canvas = state.canvas;
      var ctx = canvas.getContext("2d");
      if (!ctx) {
        return;
      }
      var canvasWidth = canvas.width;
      var canvasHeight = canvas.height;
      clampOffsets(canvasWidth, canvasHeight);
      var base = baseSize(canvasWidth, canvasHeight);
      var drawWidth = base.width * state.scale;
      var drawHeight = base.height * state.scale;
      var x = (canvasWidth - drawWidth) / 2 + state.offsetX;
      var y = (canvasHeight - drawHeight) / 2 + state.offsetY;
      ctx.clearRect(0, 0, canvasWidth, canvasHeight);
      ctx.drawImage(state.image, x, y, drawWidth, drawHeight);
    }

    state.baseSize = baseSize;
    state.clampOffsets = clampOffsets;
    state.draw = draw;

    function updateZoomLabel() {
      state.zoomValue.textContent = Math.round(state.scale * 100) + "%";
    }

    on(state.zoom, "input", function () {
      state.scale = parseFloat(state.zoom.value) || 1;
      updateZoomLabel();
      draw();
    });

    on(root.querySelector("[data-adjust-reset]"), "click", function () {
      state.scale = 1;
      state.offsetX = 0;
      state.offsetY = 0;
      state.zoom.value = "1";
      updateZoomLabel();
      draw();
    });

    all("[data-adjust-cancel]", root).forEach(function (button) {
      on(button, "click", function () {
        closeImageAdjuster();
      });
    });

    on(root.querySelector("[data-adjust-apply]"), "click", applyImageAdjustment);

    on(root, "click", function (event) {
      if (event.target === root) {
        closeImageAdjuster();
      }
    });

    on(doc, "keydown", function (event) {
      if (root.hidden) {
        return;
      }
      if (event.key === "Escape") {
        closeImageAdjuster();
        return;
      }
      if (!state.image) {
        return;
      }
      var step = event.shiftKey ? 20 : 6;
      var handled = true;
      if (event.key === "ArrowLeft") {
        state.offsetX -= step;
      } else if (event.key === "ArrowRight") {
        state.offsetX += step;
      } else if (event.key === "ArrowUp") {
        state.offsetY -= step;
      } else if (event.key === "ArrowDown") {
        state.offsetY += step;
      } else {
        handled = false;
      }
      if (handled) {
        event.preventDefault();
        draw();
      }
    });

    on(state.canvas, "pointerdown", function (event) {
      if (!state.image) {
        return;
      }
      event.preventDefault();
      state.dragging = true;
      state.startX = event.clientX;
      state.startY = event.clientY;
      state.startOffsetX = state.offsetX;
      state.startOffsetY = state.offsetY;
      state.canvas.classList.add("is-dragging");
      if (state.canvas.setPointerCapture) {
        state.canvas.setPointerCapture(event.pointerId);
      }
    });

    on(state.canvas, "pointermove", function (event) {
      if (!state.dragging) {
        return;
      }
      var rect = state.canvas.getBoundingClientRect();
      var factorX = rect.width ? state.canvas.width / rect.width : 1;
      var factorY = rect.height ? state.canvas.height / rect.height : 1;
      state.offsetX = state.startOffsetX + (event.clientX - state.startX) * factorX;
      state.offsetY = state.startOffsetY + (event.clientY - state.startY) * factorY;
      draw();
    });

    function endDrag(event) {
      if (!state.dragging) {
        return;
      }
      state.dragging = false;
      state.canvas.classList.remove("is-dragging");
      if (state.canvas.releasePointerCapture && event.pointerId !== undefined) {
        try {
          state.canvas.releasePointerCapture(event.pointerId);
        } catch (ignored) {
          /* Pointer capture may already be gone. */
        }
      }
    }

    on(state.canvas, "pointerup", endDrag);
    on(state.canvas, "pointercancel", endDrag);

    imageAdjuster = state;
    return state;
  }

  function openImageAdjuster(config) {
    var state = ensureImageAdjuster();
    if (state.tempUrl) {
      URL.revokeObjectURL(state.tempUrl);
      state.tempUrl = null;
    }

    state.input = config.input || null;
    state.mode = config.mode === "banner" ? "banner" : "photo";
    state.mime = config.mime || "image/jpeg";
    state.fromFileSelection = config.fromFileSelection === true;
    state.tempUrl = config.tempUrl ? config.src : null;
    state.scale = 1;
    state.offsetX = 0;
    state.offsetY = 0;
    state.zoom.value = "1";
    state.zoomValue.textContent = "100%";
    state.canvas.width = state.mode === "banner" ? 1200 : 900;
    state.canvas.height = state.mode === "banner" ? 400 : 900;
    state.title.textContent = state.mode === "banner" ? "Adjust profile banner" : "Adjust profile photo";
    state.hint.textContent = state.mode === "banner"
      ? "Drag to reposition and zoom to frame. Saved as a wide 3:1 banner."
      : "Drag to reposition and zoom to frame. Saved as a square profile photo.";

    state.root.hidden = false;
    window.requestAnimationFrame(function () {
      state.root.classList.add("is-open");
    });

    var image = new Image();
    state.image = null;
    image.onload = function () {
      state.image = image;
      state.draw();
    };
    image.onerror = function () {
      toast("That image could not be loaded.", "error");
      closeImageAdjuster();
    };
    image.src = config.src;
  }

  function closeImageAdjuster(keepFile) {
    var state = imageAdjuster;
    if (!state) {
      return;
    }
    if (state.tempUrl) {
      URL.revokeObjectURL(state.tempUrl);
      state.tempUrl = null;
    }
    state.root.classList.remove("is-open");
    state.root.hidden = true;
    state.image = null;
    if (!keepFile && state.fromFileSelection && state.input) {
      state.input.value = "";
    }
    state.input = null;
    state.fromFileSelection = false;
  }

  function applyImageAdjustment() {
    var state = imageAdjuster;
    if (!state || !state.image || !state.input) {
      return;
    }

    var input = state.input;
    var mode = state.mode;
    var previewCanvas = state.canvas;
    var outWidth = mode === "banner" ? 1800 : 800;
    var outHeight = mode === "banner" ? 600 : 800;
    var output = doc.createElement("canvas");
    output.width = outWidth;
    output.height = outHeight;
    var ctx = output.getContext("2d");
    if (!ctx) {
      toast("Your browser could not process the image.", "error");
      return;
    }

    var base = state.baseSize(outWidth, outHeight);
    var factorX = outWidth / previewCanvas.width;
    var factorY = outHeight / previewCanvas.height;
    var offsetX = state.offsetX * factorX;
    var offsetY = state.offsetY * factorY;
    var drawWidth = base.width * state.scale;
    var drawHeight = base.height * state.scale;
    var maxX = Math.max(0, (drawWidth - outWidth) / 2);
    var maxY = Math.max(0, (drawHeight - outHeight) / 2);
    offsetX = Math.min(maxX, Math.max(-maxX, offsetX));
    offsetY = Math.min(maxY, Math.max(-maxY, offsetY));

    var mime = state.mime === "image/png" || state.mime === "image/webp" ? state.mime : "image/jpeg";
    if (mime === "image/jpeg") {
      ctx.fillStyle = "#ffffff";
      ctx.fillRect(0, 0, outWidth, outHeight);
    }
    ctx.drawImage(
      state.image,
      (outWidth - drawWidth) / 2 + offsetX,
      (outHeight - drawHeight) / 2 + offsetY,
      drawWidth,
      drawHeight
    );

    var finish = function (blob) {
      if (!blob) {
        toast("The image could not be processed. Try another file.", "error");
        return;
      }
      if (typeof DataTransfer === "undefined") {
        toast("Your browser cannot apply the crop automatically. Please choose the file again.", "error");
        return;
      }

      var extension = mime === "image/png" ? "png" : mime === "image/webp" ? "webp" : "jpg";
      var fileName = (mode === "banner" ? "banner" : "photo") + "-cropped." + extension;
      var file;
      try {
        file = new File([blob], fileName, { type: mime, lastModified: Date.now() });
        var transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
      } catch (error) {
        toast("Your browser could not prepare the cropped image.", "error");
        return;
      }

      var name = input.getAttribute("data-upload-file") || (mode === "banner" ? "banner" : "photo");
      var previewSelector = input.getAttribute("data-preview-image");
      var preview = previewSelector ? doc.querySelector(previewSelector) : null;
      var emptySelector = input.getAttribute("data-preview-empty");
      var empty = emptySelector ? doc.querySelector(emptySelector) : null;
      var previewReader = new FileReader();
      previewReader.onload = function () {
        var previewUrl = String(previewReader.result);

        if (preview) {
          if (preview.dataset.objectUrl) {
            URL.revokeObjectURL(preview.dataset.objectUrl);
            delete preview.dataset.objectUrl;
          }
          preview.src = previewUrl;
          preview.hidden = false;
          preview.setAttribute("data-original-src", previewUrl);
        }
        if (empty) {
          empty.hidden = true;
        }

        var clearBox = doc.querySelector('[data-clear-upload="' + name + '"]');
        if (clearBox) {
          clearBox.checked = false;
        }

        var adjustButton = doc.querySelector('[data-adjust-upload="' + name + '"]');
        if (adjustButton) {
          adjustButton.setAttribute("data-adjust-src", previewUrl);
        } else {
          var actions = closest(input, ".upload-field__actions");
          if (actions) {
            var newButton = doc.createElement("button");
            newButton.type = "button";
            newButton.className = "btn btn-ghost btn-sm";
            newButton.setAttribute("data-adjust-upload", name);
            newButton.setAttribute("data-adjust-src", previewUrl);
            newButton.setAttribute("data-crop-mode", mode);
            newButton.innerHTML = '<i class="bi bi-sliders" aria-hidden="true"></i> Adjust';
            on(newButton, "click", function () {
              openAdjusterFromButton(newButton);
            });
            actions.insertBefore(newButton, actions.firstChild);
          }
        }

        input.dispatchEvent(new Event("input", { bubbles: true }));
        closeImageAdjuster(true);
        toast("Image adjusted. Save the profile to apply it.", "success");
      };
      previewReader.onerror = function () {
        input.dispatchEvent(new Event("input", { bubbles: true }));
        closeImageAdjuster(true);
        toast("Image adjusted. Save the profile to apply it.", "success");
      };
      previewReader.readAsDataURL(file);
    };

    try {
      output.toBlob(finish, mime, 0.92);
    } catch (error) {
      toast("Your browser could not process the image.", "error");
    }
  }

  function openAdjusterFromButton(button) {
    var name = button.getAttribute("data-adjust-upload");
    var input = doc.querySelector('[data-upload-file="' + name + '"]');
    var src = button.getAttribute("data-adjust-src");
    if (!input || !src) {
      return;
    }
    openImageAdjuster({
      input: input,
      mode: button.getAttribute("data-crop-mode") || "photo",
      src: src,
      tempUrl: false,
      mime: "image/jpeg",
      fromFileSelection: false
    });
  }

  function initUploadPreviews() {
    all("[data-preview-image]").forEach(function (input) {
      on(input, "change", function () {
        var cropMode = input.getAttribute("data-crop-mode");
        if (cropMode) {
          var file = input.files && input.files[0];
          if (!file) {
            return;
          }
          if (file.type && file.type.indexOf("image/") !== 0) {
            toast("Choose a JPEG, PNG or WebP image.", "error");
            input.value = "";
            return;
          }
          var reader = new FileReader();
          reader.onload = function () {
            openImageAdjuster({
              input: input,
              mode: cropMode,
              src: String(reader.result),
              tempUrl: false,
              mime: file.type,
              fromFileSelection: true
            });
          };
          reader.onerror = function () {
            toast("That image could not be read. Please choose another file.", "error");
            input.value = "";
          };
          reader.readAsDataURL(file);
          return;
        }
        setPreview(input);
      });
    });

    all("[data-adjust-upload]").forEach(function (button) {
      on(button, "click", function () {
        openAdjusterFromButton(button);
      });
    });

    all("[data-clear-upload]").forEach(function (checkbox) {
      var targetName = checkbox.getAttribute("data-clear-upload");
      on(checkbox, "change", function () {
        var image = doc.querySelector('[data-upload-preview="' + targetName + '"]');
        var empty = doc.querySelector('[data-upload-empty="' + targetName + '"]');
        var fileInput = doc.querySelector('[data-upload-file="' + targetName + '"]');

        if (checkbox.checked) {
          if (image) {
            image.hidden = true;
            image.removeAttribute("src");
          }
          if (empty) {
            empty.hidden = false;
          }
          if (fileInput) {
            fileInput.value = "";
          }
        } else {
          if (image && image.getAttribute("data-original-src")) {
            image.src = image.getAttribute("data-original-src");
            image.hidden = false;
          }
          if (empty) {
            empty.hidden = true;
          }
        }
      });
    });
  }

  /* ---------------------------------------------------------------
     Dynamic editor collections
     --------------------------------------------------------------- */

  function itemLabel(item, fallback) {
    var title = item.getAttribute("data-item-title");
    if (title && title !== "") {
      return title;
    }
    var firstInput = item.querySelector('input[type="text"], input:not([type]), textarea, select');
    if (firstInput) {
      var value = (firstInput.value || "").trim();
      if (value !== "") {
        return value.length > 48 ? value.slice(0, 48) + "…" : value;
      }
    }
    return fallback;
  }

  function refreshCollection(container) {
    var list = container.querySelector("[data-collection-items]");
    if (!list) {
      return;
    }

    var items = all(".collection-item", list);
    var empty = container.querySelector("[data-collection-empty]");
    if (empty) {
      empty.hidden = items.length > 0;
    }

    var label = container.getAttribute("data-item-label") || "Item";
    items.forEach(function (item, index) {
      var ordinal = item.querySelector("[data-item-ordinal]");
      if (ordinal) {
        ordinal.textContent = label + " " + (index + 1);
      }
      var title = item.querySelector("[data-item-title]");
      if (title) {
        title.textContent = itemLabel(item, label + " " + (index + 1));
      }
    });
  }

  function nextIndex(container) {
    var list = container.querySelector("[data-collection-items]");
    var highest = -1;
    all("[name]", list).forEach(function (input) {
      var match = /\[(\d+)\]/.exec(input.getAttribute("name") || "");
      if (match) {
        highest = Math.max(highest, parseInt(match[1], 10));
      }
    });

    return highest + 1;
  }

  function addCollectionItem(container) {
    var templateId = container.getAttribute("data-template");
    var template = templateId ? doc.getElementById(templateId) : null;
    var list = container.querySelector("[data-collection-items]");
    if (!template || !list) {
      return;
    }

    var index = nextIndex(container);
    var html = template.innerHTML.replace(/__INDEX__/g, String(index));
    var holder = doc.createElement("div");
    holder.innerHTML = html;
    var item = holder.firstElementChild;
    if (!item) {
      return;
    }

    list.appendChild(item);
    refreshCollection(container);

    var firstField = item.querySelector("input, textarea, select");
    if (firstField) {
      firstField.focus({ preventScroll: true });
      item.scrollIntoView({ behavior: "smooth", block: "center" });
    }

    toast("Entry added. Complete the fields and save the profile.");
  }

  function removeCollectionItem(button) {
    var item = closest(button, ".collection-item");
    if (!item) {
      return;
    }
    var container = closest(item, "[data-collection]");
    if (!container) {
      item.parentNode.removeChild(item);
      return;
    }

    var message = item.getAttribute("data-remove-message") || "Remove this entry? Existing saved values will be deleted when you save.";
    showConfirm(message).then(function (confirmed) {
      if (!confirmed) {
        return;
      }
      item.parentNode.removeChild(item);
      refreshCollection(container);
    });
  }

  function initCollections() {
    all("[data-collection]").forEach(function (container) {
      refreshCollection(container);

      var addButton = container.querySelector("[data-add-collection]");
      on(addButton, "click", function (event) {
        event.preventDefault();
        addCollectionItem(container);
      });

      on(container, "click", function (event) {
        var remove = closest(event.target, "[data-remove-item]");
        if (remove) {
          event.preventDefault();
          removeCollectionItem(remove);
        }
      });

      on(container, "input", function (event) {
        var item = closest(event.target, ".collection-item");
        if (!item) {
          return;
        }
        var title = item.querySelector("[data-item-title]");
        if (title) {
          var label = container.getAttribute("data-item-label") || "Item";
          title.textContent = itemLabel(item, label);
        }
      });
    });
  }

  /* ---------------------------------------------------------------
     Unsaved-changes guard
     --------------------------------------------------------------- */

  function initUnsavedGuard() {
    all("form[data-guard-unsaved]").forEach(function (form) {
      var dirty = false;
      var submitting = false;

      on(form, "input", function () {
        dirty = true;
      });
      on(form, "change", function () {
        dirty = true;
      });
      on(form, "submit", function () {
        submitting = true;
      });

      on(window, "beforeunload", function (event) {
        if (!dirty || submitting || suppressUnsavedWarning) {
          return;
        }
        event.preventDefault();
        event.returnValue = "";
        return "";
      });
    });

    all("a[data-cancel-guard]").forEach(function (link) {
      on(link, "click", function (event) {
        event.preventDefault();
        showConfirm("Changes you made may not be saved?").then(function (confirmed) {
          if (!confirmed) {
            return;
          }
          suppressUnsavedWarning = true;
          window.location.href = link.href;
        });
      });
    });
  }

  /* ---------------------------------------------------------------
     Scroll the target profile section into view on checklist clicks
     --------------------------------------------------------------- */

  function initAnchors() {
    all('a[href^="#"]').forEach(function (link) {
      on(link, "click", function (event) {
        var id = link.getAttribute("href");
        if (!id || id === "#") {
          return;
        }
        var target = doc.querySelector(id);
        if (!target) {
          return;
        }
        event.preventDefault();
        target.scrollIntoView({ behavior: "smooth", block: "start" });
        if (history.replaceState) {
          history.replaceState(null, "", id);
        }
      });
    });
  }

  /* ---------------------------------------------------------------
     Bootstrap
     --------------------------------------------------------------- */

  function init() {
    initServerToasts();
    initSidebar();
    initSidebarScroll();
    initPasswordToggles();
    initPrint();
    initConfirmations();
    initUploadPreviews();
    initCollections();
    initUnsavedGuard();
    initAnchors();
  }

  if (doc.readyState === "loading") {
    doc.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
