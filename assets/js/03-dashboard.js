/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : 03-dashboard.js
Module  : Dashboard Common JavaScript
Status  : Development
Author  : Umesh + ChatGPT
Created : 06 July 2026
==================================================
*/


/* ==========================================
   01. AUTO HIDE SUCCESS ALERT
========================================== */

document.addEventListener("DOMContentLoaded", function () {

    const alertBox = document.querySelector(".xd-alert");

    if (alertBox) {

        setTimeout(function () {

            alertBox.style.transition = "opacity .4s ease";

            alertBox.style.opacity = "0";

            setTimeout(function () {

                alertBox.remove();

            }, 400);

        }, 3000);

    }

});


/* ==========================================
   02. DELETE CONFIRMATION MODAL
========================================== */

document.addEventListener("DOMContentLoaded", function () {

    const deleteButtons = document.querySelectorAll(".xd-delete-trigger");

    const modal = document.getElementById("xdDeleteModal");

    const deleteName = document.getElementById("xdDeleteName");

    const deleteConfirm = document.getElementById("xdDeleteConfirm");

    const deleteForm = document.getElementById("xdDeleteForm");

    const deleteId = document.getElementById("xdDeleteId");

    const cancelButton = document.querySelector(".xd-modal-cancel");

    if (!modal || !deleteForm || !deleteId || !deleteConfirm || !cancelButton) {

        return;

    }

    const focusableSelector = [
        "a[href]",
        "button:not([disabled])",
        "input:not([type='hidden']):not([disabled])",
        "select:not([disabled])",
        "textarea:not([disabled])",
        "[tabindex]:not([tabindex='-1'])"
    ].join(",");

    let lastFocusedElement = null;

    modal.setAttribute("role", "dialog");
    modal.setAttribute("aria-modal", "true");
    modal.setAttribute("aria-label", "Confirm deletion");
    modal.setAttribute("aria-hidden", "true");

    function openModal(button) {

        lastFocusedElement = button;

        if (deleteName) {
            deleteName.textContent = button.dataset.name || "this item";
        }

        deleteId.value = button.dataset.id || "";

        modal.classList.add("active");
        modal.setAttribute("aria-hidden", "false");
        document.body.classList.add("xd-modal-open");

        window.requestAnimationFrame(function () {
            cancelButton.focus();
        });

    }

    function closeModal() {

        if (!modal.classList.contains("active")) {
            return;
        }

        if (lastFocusedElement && typeof lastFocusedElement.focus === "function") {
            lastFocusedElement.focus({ preventScroll: true });
        }

        modal.classList.remove("active");
        modal.setAttribute("aria-hidden", "true");
        document.body.classList.remove("xd-modal-open");

        lastFocusedElement = null;

    }

    deleteButtons.forEach(function (button) {

        button.addEventListener("click", function (event) {

            event.preventDefault();

            openModal(button);

        });

    });

    deleteConfirm.addEventListener("click", function (event) {

        event.preventDefault();

        deleteForm.submit();

    });

    cancelButton.addEventListener("click", function () {

        closeModal();

    });

    modal.addEventListener("click", function (event) {

        if (event.target === modal) {

            closeModal();

        }

    });

    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape" && modal.classList.contains("active")) {

            closeModal();

            return;

        }

        if (event.key !== "Tab" || !modal.classList.contains("active")) {
            return;
        }

        const focusableElements = Array.from(modal.querySelectorAll(focusableSelector)).filter(
            function (element) {
                return element.offsetParent !== null;
            }
        );

        if (focusableElements.length === 0) {
            event.preventDefault();
            return;
        }

        const firstFocusable = focusableElements[0];
        const lastFocusable = focusableElements[focusableElements.length - 1];

        if (event.shiftKey && document.activeElement === firstFocusable) {
            event.preventDefault();
            lastFocusable.focus();
        } else if (!event.shiftKey && document.activeElement === lastFocusable) {
            event.preventDefault();
            firstFocusable.focus();

        }

    });

});
