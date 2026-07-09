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
   01. SIDEBAR TOGGLE
========================================== */

document.addEventListener("DOMContentLoaded", function () {

    const toggleButton = document.getElementById("xdSuperAdminMenuToggle");

    const sidebar = document.getElementById("xdSuperAdminSidebar");

    if (!toggleButton || !sidebar) {

        return;

    }

    toggleButton.addEventListener("click", function () {

        sidebar.classList.toggle("open");

    });

});
