/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : chat.js
Module  : Dashboard Live Chat
Status  : Development
Author  : Umesh + ChatGPT
Created : 06 July 2026
==================================================
*/


/* ==========================================
   01. GLOBAL VARIABLES
========================================== */

let activeChatId = 0;


/* ==========================================
   02. DOCUMENT READY
========================================== */

document.addEventListener("DOMContentLoaded", function () {

    loadChatList();

    registerSendEvents();

});


/* ==========================================
   03. LOAD CHAT LIST
========================================== */

function loadChatList() {

    fetch("chat/ajax/chat-list.php")

        .then(function (response) {
            return response.text();
        })

        .then(function (html) {

            document.getElementById("xdChatList").innerHTML = html;

            const totalChats =
                document.querySelectorAll(".xd-chat-list-item").length;

            document.getElementById("xdConversationCount").innerText =
                totalChats;

            registerChatEvents();

            keepActiveChatSelected();

        })

        .catch(function (error) {
            console.error(error);
        });

}


/* ==========================================
   04. REGISTER CLICK EVENTS
========================================== */

function registerChatEvents() {

    const items = document.querySelectorAll(".xd-chat-list-item");

    items.forEach(function (item) {

        item.addEventListener("click", function () {

            items.forEach(function (chat) {
                chat.classList.remove("active");
            });

            this.classList.add("active");

            activeChatId = this.dataset.chatId;

            document.getElementById("xdChatVisitorName").innerText =
                this.dataset.visitorName;

            document.getElementById("xdChatVisitorStatus").innerText =
                "Loading conversation...";

            loadChat(activeChatId);

        });

    });

}


/* ==========================================
   05. KEEP ACTIVE CHAT SELECTED
========================================== */

function keepActiveChatSelected() {

    if (activeChatId <= 0) {
        return;
    }

    const activeItem = document.querySelector(
        '.xd-chat-list-item[data-chat-id="' + activeChatId + '"]'
    );

    if (activeItem) {
        activeItem.classList.add("active");
    }

}


/* ==========================================
   06. LOAD CHAT MESSAGES
========================================== */

function loadChat(chatId) {

    fetch("chat/ajax/load-chat.php?chat_id=" + chatId)

        .then(function (response) {
            return response.text();
        })

        .then(function (html) {

            document.getElementById("xdChatMessages").innerHTML = html;

            document.getElementById("xdChatInput").disabled = false;

            document.getElementById("xdChatSend").disabled = false;

            document.getElementById("xdChatVisitorStatus").innerText =
                "Online";

            scrollMessagesBottom();

        })

        .catch(function (error) {
            console.error(error);
        });

}


/* ==========================================
   07. REGISTER SEND EVENTS
========================================== */

function registerSendEvents() {

    const input = document.getElementById("xdChatInput");

    const sendButton = document.getElementById("xdChatSend");

    sendButton.addEventListener("click", function () {

        sendAgentMessage();

    });

    input.addEventListener("keydown", function (event) {

        if (event.key === "Enter") {

            sendAgentMessage();

        }

    });

}


/* ==========================================
   08. SEND AGENT MESSAGE
========================================== */

function sendAgentMessage() {

    const input = document.getElementById("xdChatInput");

    const message = input.value.trim();

    if (activeChatId <= 0 || message === "") {
        return;
    }

    const formData = new FormData();

    formData.append("chat_id", activeChatId);

    formData.append("message", message);

    input.value = "";

    fetch("chat/ajax/send-message.php", {
        method: "POST",
        body: formData
    })

        .then(function (response) {
            return response.json();
        })

        .then(function (data) {

            if (!data.success) {
                console.error(data.message);
                return;
            }

            loadChat(activeChatId);

            loadChatList();

        })

        .catch(function (error) {
            console.error(error);
        });

}


/* ==========================================
   09. SCROLL MESSAGES BOTTOM
========================================== */

function scrollMessagesBottom() {

    const messageBox = document.getElementById("xdChatMessages");

    messageBox.scrollTop = messageBox.scrollHeight;

}


/* ==========================================
   10. AUTO REFRESH
========================================== */

setInterval(function () {

    loadChatList();

}, 3000);


setInterval(function () {

    if (activeChatId > 0) {

        loadChat(activeChatId);

    }

}, 2000);