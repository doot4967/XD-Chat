/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : super-admin.js
Module  : Super Admin JavaScript
Status  : Development
Author  : Umesh + ChatGPT
Created : 10 July 2026
==================================================
*/


/* ==========================================
   01. INITIALIZE MOBILE SIDEBAR
========================================== */

(function () {

    "use strict";

    function initializeSuperAdminSidebar() {

        const body = document.body;
        const sidebar = document.getElementById("xdSuperAdminSidebar");
        const toggleButton = document.getElementById("xdSuperAdminMenuToggle");
        const closeButton = document.getElementById("xdSuperAdminSidebarClose");
        const backdrop = document.getElementById("xdSuperAdminSidebarBackdrop");

        if (!body || !sidebar || !toggleButton || !closeButton || !backdrop) {
            return;
        }

        const mobileQuery = window.matchMedia("(max-width: 860px)");
        const sidebarLinks = Array.from(sidebar.querySelectorAll("a"));
        const sidebarFocusables = Array.from(sidebar.querySelectorAll(
            "a, button, input, select, textarea, [tabindex]"
        ));
        const originalTabIndexes = new Map();

        sidebarFocusables.forEach(function (element) {
            originalTabIndexes.set(element, element.getAttribute("tabindex"));
        });

        body.classList.add("xd-sa-sidebar-enhanced");


        /* ==========================================
           02. ACCESSIBILITY STATE
        =========================================== */

        function setSidebarFocusability(enabled) {

            sidebarFocusables.forEach(function (element) {

                const originalTabIndex = originalTabIndexes.get(element);

                if (!enabled) {
                    element.setAttribute("tabindex", "-1");
                    return;
                }

                if (originalTabIndex === null) {
                    element.removeAttribute("tabindex");
                } else {
                    element.setAttribute("tabindex", originalTabIndex);
                }

            });

        }

        function setSidebarAccessibility(isOpen) {

            if (!mobileQuery.matches) {
                sidebar.setAttribute("aria-hidden", "false");
                toggleButton.setAttribute("aria-expanded", "true");
                toggleButton.setAttribute("aria-label", "Navigation is visible");
                setSidebarFocusability(true);
                return;
            }

            sidebar.setAttribute("aria-hidden", isOpen ? "false" : "true");
            toggleButton.setAttribute("aria-expanded", isOpen ? "true" : "false");
            toggleButton.setAttribute("aria-label", isOpen ? "Close navigation" : "Open navigation");
            setSidebarFocusability(isOpen);

        }


        /* ==========================================
           03. OPEN AND CLOSE ACTIONS
        =========================================== */

        function openSidebar() {

            if (!mobileQuery.matches) {
                return;
            }

            setSidebarAccessibility(true);
            body.classList.add("xd-sa-sidebar-open");

            window.requestAnimationFrame(function () {
                closeButton.focus();
            });

        }

        function closeSidebar(restoreFocus) {

            if (restoreFocus && mobileQuery.matches) {
                toggleButton.focus({ preventScroll: true });
            }

            body.classList.remove("xd-sa-sidebar-open");
            setSidebarAccessibility(false);

        }


        /* ==========================================
           04. EVENT HANDLERS
        =========================================== */

        toggleButton.addEventListener("click", function () {

            if (!mobileQuery.matches) {
                return;
            }

            if (body.classList.contains("xd-sa-sidebar-open")) {
                closeSidebar(true);
            } else {
                openSidebar();
            }

        });

        closeButton.addEventListener("click", function () {
            closeSidebar(true);
        });

        backdrop.addEventListener("click", function () {
            closeSidebar(true);
        });

        sidebarLinks.forEach(function (link) {
            link.addEventListener("click", function () {
                closeSidebar(true);
            });
        });

        document.addEventListener("keydown", function (event) {

            if (event.key === "Escape" && body.classList.contains("xd-sa-sidebar-open")) {
                closeSidebar(true);
                return;
            }

            if (event.key !== "Tab" || !body.classList.contains("xd-sa-sidebar-open")) {
                return;
            }

            const availableFocusables = sidebarFocusables.filter(function (element) {
                return !element.disabled && element.getAttribute("aria-hidden") !== "true";
            });

            if (availableFocusables.length === 0) {
                event.preventDefault();
                closeButton.focus();
                return;
            }

            const firstFocusable = availableFocusables[0];
            const lastFocusable = availableFocusables[availableFocusables.length - 1];

            if (event.shiftKey && document.activeElement === firstFocusable) {
                event.preventDefault();
                lastFocusable.focus();
            } else if (!event.shiftKey && document.activeElement === lastFocusable) {
                event.preventDefault();
                firstFocusable.focus();
            }

        });

        function handleViewportChange() {
            body.classList.remove("xd-sa-sidebar-open");
            setSidebarAccessibility(false);
        }

        if (typeof mobileQuery.addEventListener === "function") {
            mobileQuery.addEventListener("change", handleViewportChange);
        } else {
            mobileQuery.addListener(handleViewportChange);
        }

        window.addEventListener("orientationchange", function () {
            handleViewportChange();
        });


        /* ==========================================
           05. INITIAL STATE
        =========================================== */

        setSidebarAccessibility(false);

    }

    initializeSuperAdminSidebar();

}());
