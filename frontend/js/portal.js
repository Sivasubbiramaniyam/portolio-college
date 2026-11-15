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

  function toast(message, tone) {
    var stack = doc.getElementById("toast-stack");
    if (!stack || !message) {
      return;
    }

    var node = doc.createElement("div");
    node.className = "toast toast--" + (tone === "error" ? "error" : "success");
    node.setAttribute("role", "status");

    var icon = doc.createElement("i");
    icon.className = "bi bi-" + (tone === "error" ? "exclamation-octagon-fill" : "check2-circle");
    icon.setAttribute("aria-hidden", "true");

    var text = doc.createElement("span");
    text.textContent = message;

    node.appendChild(icon);
    node.appendChild(text);
    stack.appendChild(node);

    window.setTimeout(function () {
      node.classList.add("is-leaving");
      window.setTimeout(function () {
        if (node.parentNode) {
          node.parentNode.removeChild(node);
        }
      }, 240);
    }, 4200);
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

  function initConfirmations() {
    all("[data-confirm]").forEach(function (element) {
      var message = element.getAttribute("data-confirm") || "Are you sure?";

      if (element.tagName === "FORM") {
        on(element, "submit", function (event) {
          if (!window.confirm(message)) {
            event.preventDefault();
          }
        });
        return;
      }

      if (element.tagName === "A") {
        on(element, "click", function (event) {
          if (!window.confirm(message)) {
            event.preventDefault();
          }
        });
        return;
      }

      var form = closest(element, "form");
      if (form) {
        on(form, "submit", function (event) {
          if (event.defaultPrevented) {
            return;
          }
          if (!window.confirm(message)) {
            event.preventDefault();
          }
        });
      }
    });
  }

  /* ---------------------------------------------------------------
     Image / upload previews
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

  function initUploadPreviews() {
    all("[data-preview-image]").forEach(function (input) {
      on(input, "change", function () {
        setPreview(input);
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
    if (!window.confirm(message)) {
      return;
    }

    item.parentNode.removeChild(item);
    refreshCollection(container);
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
        if (!dirty || submitting) {
          return;
        }
        event.preventDefault();
        event.returnValue = "";
        return "";
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
