document.addEventListener("DOMContentLoaded", () => {
  const body = document.body;
  const menuButton = document.querySelector(".mobile-toggle");
  const mobileMenu = document.querySelector(".mobile-menu");
  const overlay = document.querySelector(".menu-overlay");
  const closeButton = document.querySelector(".menu-close");
  const accountDropdown = document.querySelector(".nav-right .account-dropdown");
  const accountTrigger = accountDropdown ? accountDropdown.querySelector("li.account > a") : null;

  const setMenuOpen = (isOpen) => {
    if (!mobileMenu || !overlay || !menuButton) {
      return;
    }

    mobileMenu.classList.toggle("open", isOpen);
    overlay.classList.toggle("active", isOpen);
    body.classList.toggle("menu-open", isOpen);
    menuButton.setAttribute("aria-expanded", isOpen ? "true" : "false");
  };

  document.querySelectorAll(".account-menu li").forEach((item) => {
    if (item.querySelector("ul")) {
      item.classList.add("has-sub");
    }
  });

  document.querySelectorAll(".account-menu").forEach((menu) => {
    menu.addEventListener("click", (event) => {
      const link = event.target.closest("a");
      if (!link) {
        return;
      }

      const parentItem = link.parentElement;
      const submenu = parentItem ? parentItem.querySelector("ul") : null;
      if (!submenu) {
        return;
      }

      event.preventDefault();
      parentItem.classList.toggle("open");
    });
  });

  if (accountTrigger) {
    accountTrigger.addEventListener("click", (event) => {
      event.preventDefault();
      accountDropdown.classList.toggle("open");
    });
  }

  document.addEventListener("click", (event) => {
    if (accountDropdown && !accountDropdown.contains(event.target)) {
      accountDropdown.classList.remove("open");
    }
  });

  menuButton?.addEventListener("click", (event) => {
    event.preventDefault();
    event.stopPropagation();
    setMenuOpen(!mobileMenu?.classList.contains("open"));
  });

  overlay?.addEventListener("click", () => {
    setMenuOpen(false);
  });

  closeButton?.addEventListener("click", () => {
    setMenuOpen(false);
  });

  mobileMenu?.querySelectorAll("li").forEach((item) => {
    if (item.querySelector("ul")) {
      item.classList.add("has-sub");
    }
  });

  mobileMenu?.addEventListener("click", (event) => {
    const link = event.target.closest("a");
    if (!link) {
      return;
    }

    const parentItem = link.parentElement;
    const submenu = parentItem ? parentItem.querySelector("ul") : null;
    if (!submenu) {
      return;
    }

    event.preventDefault();
    mobileMenu.querySelectorAll("li.open").forEach((item) => {
      if (item !== parentItem) {
        item.classList.remove("open");
      }
    });
    parentItem.classList.toggle("open");
  });
});
