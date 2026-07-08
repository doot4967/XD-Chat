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

    if (!modal || !deleteForm || !deleteId) {

        return;

    }

    deleteButtons.forEach(function (button) {

        button.addEventListener("click", function (event) {

            event.preventDefault();

            deleteName.textContent = button.dataset.name;

            deleteId.value = button.dataset.id;

            modal.classList.add("active");

        });

    });

    deleteConfirm.addEventListener("click", function (event) {

        event.preventDefault();

        deleteForm.submit();

    });

    cancelButton.addEventListener("click", function () {

        modal.classList.remove("active");

    });

    modal.addEventListener("click", function (event) {

        if (event.target === modal) {

            modal.classList.remove("active");

        }

    });

    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape") {

            modal.classList.remove("active");

        }

    });

});
