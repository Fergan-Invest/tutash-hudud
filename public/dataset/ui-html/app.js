document.addEventListener("DOMContentLoaded", () => {
  initMenu();
  initClickableRows();
  initForms();
  initCreateSteps();
  initLeafletMaps();
  initDrawerTabs();
});

function initMenu() {
  const sidebar = document.querySelector(".sidebar");
  const menuButton = document.querySelector(".menu-button");

  if (!menuButton || !sidebar) return;

  menuButton.addEventListener("click", () => {
    const isOpen = sidebar.classList.toggle("is-open");
    menuButton.setAttribute("aria-expanded", String(isOpen));
  });
}

function initClickableRows() {
  document.querySelectorAll(".clickable-row").forEach((row) => {
    const go = () => {
      const href = row.dataset.href;
      if (!href) return;

      if (href.startsWith("#")) {
        window.location.hash = href;
        return;
      }

      window.location.href = href;
    };

    row.addEventListener("click", (event) => {
      if (event.target.closest("a, button, input, select, textarea")) return;
      go();
    });

    row.addEventListener("keydown", (event) => {
      if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        go();
      }
    });
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && window.location.hash) {
      history.pushState("", document.title, window.location.pathname + window.location.search);
    }
  });
}

function initForms() {
  document.querySelectorAll("form").forEach((form) => {
    form.addEventListener("submit", (event) => {
      event.preventDefault();
      showToast("Действие выполнено. В реальном приложении данные отправятся на сервер.");
    });
  });

  document.querySelectorAll('button[type="submit"]').forEach((button) => {
    button.addEventListener("click", (event) => {
      event.preventDefault();
      showToast("Действие выполнено. В реальном приложении данные отправятся на сервер.");
    });
  });
}

function initCreateSteps() {
  const steps = [...document.querySelectorAll("[data-step-target]")];
  const panels = [...document.querySelectorAll("[data-step-panel]")];
  const nextButton = document.querySelector("[data-step-next]");
  const prevButton = document.querySelector("[data-step-prev]");
  const submitButton = document.querySelector('button[type="submit"]');

  if (!steps.length || !panels.length) return;

  let currentStep = 1;

  const setStep = (step) => {
    currentStep = Math.max(1, Math.min(step, panels.length));

    steps.forEach((button) => {
      const buttonStep = Number(button.dataset.stepTarget);
      button.classList.toggle("active", buttonStep === currentStep);
      button.classList.toggle("complete", buttonStep < currentStep);
    });

    panels.forEach((panel) => {
      panel.classList.toggle("active", Number(panel.dataset.stepPanel) === currentStep);
    });

    if (prevButton) prevButton.disabled = currentStep === 1;
    if (nextButton) {
      nextButton.hidden = currentStep === panels.length;
      nextButton.textContent = currentStep === panels.length - 1 ? "Документларга ўтиш" : "Кейингиси";
    }
    if (submitButton) submitButton.hidden = currentStep !== panels.length;
  };

  steps.forEach((button) => {
    button.addEventListener("click", () => setStep(Number(button.dataset.stepTarget)));
  });

  nextButton?.addEventListener("click", () => setStep(currentStep + 1));
  prevButton?.addEventListener("click", () => setStep(currentStep - 1));

  setStep(currentStep);
}

function initLeafletMaps() {
  const mapContainers = [
    { id: "create-map", zoom: 17.3 },
    { id: "modal-map", zoom: 17.8 }
  ];

  const hasLeaflet = typeof L !== "undefined";

  mapContainers.forEach(({ id, zoom }) => {
    const container = document.getElementById(id);
    if (!container) return;

    const shell = container.closest(".leaflet-shell, .measuring-map");

    if (!hasLeaflet) {
      shell?.classList.add("leaflet-fallback");
      return;
    }

    const map = L.map(id, {
      center: [40.3777, 71.7978],
      zoom,
      zoomControl: false,
      attributionControl: true
    });

    L.control.zoom({ position: "topright" }).addTo(map);

    L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    const polygonPoints = [
      [40.37795, 71.79728],
      [40.37804, 71.79818],
      [40.37752, 71.79836],
      [40.37743, 71.79748]
    ];

    L.polygon(polygonPoints, {
      color: "#ffffff",
      weight: 3,
      fillColor: "#19b997",
      fillOpacity: 0.42
    }).addTo(map);

    polygonPoints.forEach((point) => {
      L.circleMarker(point, {
        radius: 6,
        color: "#ffffff",
        weight: 2,
        fillColor: "#19b997",
        fillOpacity: 1
      }).addTo(map);
    });

    shell?.classList.add("leaflet-ready");

    window.addEventListener("hashchange", () => {
      window.setTimeout(() => map.invalidateSize(), 120);
    });

    window.setTimeout(() => map.invalidateSize(), 150);
  });
}

function initDrawerTabs() {
  document.querySelectorAll(".drawer-panel").forEach((drawer) => {
    const tabs = [...drawer.querySelectorAll("[data-drawer-tab]")];
    const panels = [...drawer.querySelectorAll("[data-drawer-panel]")];

    if (!tabs.length || !panels.length) return;

    const activateTab = (target) => {
      tabs.forEach((tab) => {
        const isActive = tab.dataset.drawerTab === target;
        tab.classList.toggle("active", isActive);
        tab.setAttribute("aria-selected", String(isActive));
      });

      panels.forEach((panel) => {
        panel.classList.toggle("active", panel.dataset.drawerPanel === target);
      });
    };

    tabs.forEach((tab) => {
      tab.addEventListener("click", () => activateTab(tab.dataset.drawerTab));
    });

    activateTab(drawer.querySelector(".drawer-tabs .active")?.dataset.drawerTab || tabs[0].dataset.drawerTab);
  });
}

function showToast(message) {
  let toast = document.querySelector(".toast");

  if (!toast) {
    toast = document.createElement("div");
    toast.className = "toast";
    toast.setAttribute("role", "status");
    document.body.appendChild(toast);
  }

  toast.textContent = message;
  toast.classList.add("show");

  window.clearTimeout(showToast.timer);
  showToast.timer = window.setTimeout(() => {
    toast.classList.remove("show");
  }, 2800);
}
