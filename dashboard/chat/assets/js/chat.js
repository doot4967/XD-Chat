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

let activeChatStatus = "open";

let chatListStatusFilter = "open";

let isFirstChatLoad = true;

let hasUnreadMessages = false;

let previousMessageSignature = "";

let newMessagesButton = null;

let lastTypingPingTime = 0;

const typingThrottleDelay = 1500;

let isVisitorTyping = false;

let notificationAudioContext = null;

let isNotificationSoundEnabled = false;

let isChatMessageNotificationReady = false;

let isChatListNotificationReady = false;

let previousIncomingMessageSignature = "";

let previousUnreadCounts = {};


/* ==========================================
   02. DOCUMENT READY
========================================== */

document.addEventListener("DOMContentLoaded", function () {

    loadChatList();

    registerSendEvents();

    registerMessageScrollEvent();

    registerVisitorInfoToggle();

    registerFilterEvents();

    registerCloseChatEvent();

    registerImageLightbox();

});


/* ==========================================
   03. NOTIFICATION HELPER
========================================== */

function enableNotificationHelper() {

    const AudioContextClass = window.AudioContext || window.webkitAudioContext;

    isNotificationSoundEnabled = true;

    if (!notificationAudioContext && AudioContextClass) {
        notificationAudioContext = new AudioContextClass();
    }

    if (
        notificationAudioContext &&
        notificationAudioContext.state === "suspended"
    ) {
        notificationAudioContext.resume().catch(function () {});
    }

}


function playNotificationSound() {

    const AudioContextClass = window.AudioContext || window.webkitAudioContext;

    if (!isNotificationSoundEnabled || !AudioContextClass) {
        return;
    }

    if (!notificationAudioContext) {
        notificationAudioContext = new AudioContextClass();
    }

    const oscillator = notificationAudioContext.createOscillator();

    const gain = notificationAudioContext.createGain();

    const now = notificationAudioContext.currentTime;

    oscillator.type = "sine";
    oscillator.frequency.setValueAtTime(760, now);
    oscillator.frequency.exponentialRampToValueAtTime(520, now + 0.12);

    gain.gain.setValueAtTime(0.0001, now);
    gain.gain.exponentialRampToValueAtTime(0.12, now + 0.01);
    gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.18);

    oscillator.connect(gain);
    gain.connect(notificationAudioContext.destination);

    oscillator.start(now);
    oscillator.stop(now + 0.2);

}


/* ==========================================
   03. LOAD CHAT LIST
========================================== */

function loadChatList() {

    fetch("chat/ajax/chat-list.php?status=" + encodeURIComponent(chatListStatusFilter))

        .then(function (response) {
            return response.text();
        })

        .then(function (html) {

            const currentUnreadCounts = getUnreadCountsFromChatList(html);

            if (
                isChatListNotificationReady &&
                hasNewUnreadMessages(currentUnreadCounts)
            ) {
                playNotificationSound();
            }

            previousUnreadCounts = currentUnreadCounts;

            isChatListNotificationReady = true;

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

            enableNotificationHelper();

            items.forEach(function (chat) {
                chat.classList.remove("active");
            });

            this.classList.add("active");

            activeChatId = this.dataset.chatId;

            activeChatStatus = this.dataset.chatStatus || "open";

            isFirstChatLoad = true;

            hasUnreadMessages = false;

            previousMessageSignature = "";

            previousIncomingMessageSignature = "";

            isChatMessageNotificationReady = false;

            isChatListNotificationReady = false;

            previousUnreadCounts = {};

            isVisitorTyping = false;

            hideNewMessagesButton();

            closeVisitorInfoBox();

            document.getElementById("xdChatVisitorName").innerText =
                this.dataset.visitorName;

            document.getElementById("xdChatVisitorStatus").innerText =
                "Loading conversation...";

            updateChatControls(activeChatStatus);

            loadChat(activeChatId, "open");

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


function getUnreadCountsFromChatList(html) {

    const template = document.createElement("template");

    template.innerHTML = html.trim();

    const unreadCounts = {};

    template.content.querySelectorAll(".xd-chat-list-item").forEach(function (item) {

        const chatId = item.dataset.chatId;

        const unreadBadge = item.querySelector(".xd-chat-unread-badge");

        unreadCounts[chatId] = unreadBadge
            ? parseInt(unreadBadge.textContent.trim(), 10) || 0
            : 0;

    });

    return unreadCounts;

}


function hasNewUnreadMessages(currentUnreadCounts) {

    let hasNewUnread = false;

    Object.keys(currentUnreadCounts).forEach(function (chatId) {

        if (parseInt(chatId, 10) === parseInt(activeChatId, 10)) {
            return;
        }

        const previousCount = previousUnreadCounts[chatId] || 0;

        if (currentUnreadCounts[chatId] > previousCount) {
            hasNewUnread = true;
        }

    });

    return hasNewUnread;

}


/* ==========================================
   06. FILTER EVENTS
========================================== */

function registerFilterEvents() {

    const filterButtons = document.querySelectorAll(".xd-chat-filter");

    filterButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            enableNotificationHelper();

            filterButtons.forEach(function (item) {
                item.classList.remove("active");
            });

            this.classList.add("active");

            chatListStatusFilter = this.dataset.status || "open";

            activeChatId = 0;

            activeChatStatus = "open";

            previousMessageSignature = "";

            previousIncomingMessageSignature = "";

            isChatMessageNotificationReady = false;

            isChatListNotificationReady = false;

            previousUnreadCounts = {};

            isVisitorTyping = false;

            closeVisitorInfoBox();

            updateChatControls(activeChatStatus);

            resetChatWindow();

            loadChatList();

        });

    });

}


/* ==========================================
   07. LOAD CHAT MESSAGES
========================================== */

function loadChat(chatId, loadReason) {

    const messageBox = document.getElementById("xdChatMessages");

    const wasAtBottom = isMessageBoxAtBottom(messageBox);

    const previousScrollTop = messageBox.scrollTop;

    fetch("chat/ajax/load-chat.php?chat_id=" + chatId)

        .then(function (response) {
            return response.text();
        })

        .then(function (html) {

            const currentMessageSignature = getMessageSignature(html);

            const currentIncomingMessageSignature =
                getIncomingMessageSignature(html);

            const hasNewMessages = previousMessageSignature !== ""
                && previousMessageSignature !== currentMessageSignature;

            const hasNewIncomingMessage =
                previousIncomingMessageSignature !== ""
                && previousIncomingMessageSignature !== currentIncomingMessageSignature;

            messageBox.innerHTML = html;

            updateVisitorInfoBox(messageBox);

            updateChatControls(activeChatStatus);

            previousMessageSignature = currentMessageSignature;

            if (
                isChatMessageNotificationReady &&
                hasNewIncomingMessage &&
                loadReason !== "open" &&
                loadReason !== "send"
            ) {
                playNotificationSound();
            }

            previousIncomingMessageSignature = currentIncomingMessageSignature;

            isChatMessageNotificationReady = true;

            handleSmartScroll(
                messageBox,
                loadReason || "poll",
                wasAtBottom,
                hasNewMessages,
                previousScrollTop
            );

            isFirstChatLoad = false;

            markChatSeen(chatId);

            updateDashboardPresence();

        })

        .catch(function (error) {
            console.error(error);
        });

}


/* ==========================================
   07. VISITOR DETAILS
========================================== */

function updateVisitorInfoBox(messageBox) {

    const visitorInfoBox = document.getElementById("xdChatVisitorInfo");

    const visitorPayload = messageBox.querySelector(".xd-chat-visitor-payload");

    if (!visitorInfoBox || !visitorPayload) {
        return;
    }

    const visitorName = visitorPayload.dataset.name || "Guest Visitor";

    const visitorEmail = visitorPayload.dataset.email || "";

    const visitorPageUrl = visitorPayload.dataset.pageUrl || "Not captured";

    const visitorReferrer = visitorPayload.dataset.referrer || "Direct visit";

    const visitorDevice = visitorPayload.dataset.device || "Unknown";

    const visitorBrowser = visitorPayload.dataset.browser || "Unknown";

    activeChatStatus = visitorPayload.dataset.status || activeChatStatus;

    visitorInfoBox.innerHTML = `
        <div class="xd-visitor-info-card">

            <div class="xd-visitor-info-top">

                <strong>
                    ${escapeHTML(visitorName)}
                </strong>

                <span>
                    ${escapeHTML(visitorDevice)}
                </span>

            </div>

            ${visitorEmail !== "Not provided" ? `
                <p>
                    <b>Email:</b> ${escapeHTML(visitorEmail)}
                </p>
            ` : ""}

            <p title="${escapeHTML(visitorPageUrl)}">
                <b>Page:</b> ${escapeHTML(visitorPageUrl)}
            </p>

            <p title="${escapeHTML(visitorReferrer)}">
                <b>Referrer:</b> ${escapeHTML(visitorReferrer)}
            </p>

            <p title="${escapeHTML(visitorBrowser)}">
                <b>Browser:</b> ${escapeHTML(visitorBrowser)}
            </p>

        </div>
    `;

    visitorPayload.remove();

}


function registerVisitorInfoToggle() {

    const detailsButton = document.getElementById("xdChatDetailsToggle");

    const visitorInfoBox = document.getElementById("xdChatVisitorInfo");

    if (!detailsButton || !visitorInfoBox) {
        return;
    }

    detailsButton.addEventListener("click", function () {

        visitorInfoBox.classList.toggle("active");

    });

}


function closeVisitorInfoBox() {

    const visitorInfoBox = document.getElementById("xdChatVisitorInfo");

    if (visitorInfoBox) {
        visitorInfoBox.classList.remove("active");
    }

}


function escapeHTML(value) {

    const div = document.createElement("div");

    div.textContent = value;

    return div.innerHTML;

}


/* ==========================================
   08. CHAT CONTROLS
========================================== */

function registerCloseChatEvent() {

    const closeButton = document.getElementById("xdChatCloseButton");

    if (!closeButton) {
        return;
    }

    closeButton.addEventListener("click", function () {

        enableNotificationHelper();

        closeActiveChat();

    });

}


function updateChatControls(chatStatus) {

    const input = document.getElementById("xdChatInput");

    const sendButton = document.getElementById("xdChatSend");

    const attachButton = document.getElementById("xdChatAttach");

    const closeButton = document.getElementById("xdChatCloseButton");

    const isClosed = chatStatus === "closed";

    const hasActiveChat = activeChatId > 0;

    input.disabled = !hasActiveChat || isClosed;

    sendButton.disabled = !hasActiveChat || isClosed;

    attachButton.disabled = !hasActiveChat || isClosed;

    closeButton.disabled = !hasActiveChat || isClosed;

    input.placeholder = isClosed
        ? "This chat is closed."
        : "Type your reply...";

}


function closeActiveChat() {

    if (activeChatId <= 0 || activeChatStatus === "closed") {
        return;
    }

    const formData = new FormData();

    formData.append("chat_id", activeChatId);

    formData.append("status", "closed");

    fetch("chat/ajax/update-status.php", {
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

            activeChatStatus = "closed";

            updateChatControls(activeChatStatus);

            loadChat(activeChatId, "open");

            loadChatList();

        })

        .catch(function (error) {
            console.error(error);
        });

}


function markChatSeen(chatId) {

    const formData = new FormData();

    formData.append("chat_id", chatId);

    fetch("chat/ajax/mark-seen.php", {
        method: "POST",
        body: formData
    })

        .then(function (response) {
            return response.json();
        })

        .then(function (data) {

            if (data.success) {
                loadChatList();
            }

        })

        .catch(function (error) {
            console.error(error);
        });

}


function resetChatWindow() {

    isVisitorTyping = false;

    document.getElementById("xdChatVisitorName").innerText =
        "Select a conversation";

    document.getElementById("xdChatVisitorStatus").innerText =
        "Visitor details will appear here.";

    document.getElementById("xdChatMessages").innerHTML = `
        <div class="xd-chat-empty-state large">
            Select a visitor from the left side to start chatting.
        </div>
    `;

}


function updateDashboardPresence(isTyping) {

    if (activeChatId <= 0 || activeChatStatus === "closed") {
        return;
    }

    const formData = new FormData();

    formData.append("chat_id", activeChatId);
    formData.append("is_typing", isTyping ? "1" : "0");

    fetch("chat/ajax/presence.php", {
        method: "POST",
        body: formData
    })

        .then(function (response) {
            return response.json();
        })

        .then(function (data) {

            if (!data.success || activeChatStatus === "closed") {
                return;
            }

            updateVisitorPresenceStatus(data.visitor_online, data.visitor_typing);

        })

        .catch(function (error) {
            console.error(error);
        });

}


function updateVisitorPresenceStatus(isOnline, isTyping) {

    isVisitorTyping = !!isTyping;

    if (activeChatStatus === "closed") {

        document.getElementById("xdChatVisitorStatus").innerText =
            "Closed";

        return;

    }

    if (isTyping) {

        document.getElementById("xdChatVisitorStatus").innerText =
            "Visitor is typing...";

        return;

    }

    document.getElementById("xdChatVisitorStatus").innerText =
        isOnline ? "Online" : "Offline";

}


function sendAdminTypingPresence() {

    const now = Date.now();

    if (now - lastTypingPingTime < typingThrottleDelay) {
        return;
    }

    lastTypingPingTime = now;

    updateDashboardPresence(true);

}


/* ==========================================
   09. REGISTER SEND EVENTS
========================================== */

function registerSendEvents() {

    const input = document.getElementById("xdChatInput");

    const sendButton = document.getElementById("xdChatSend");

    const attachButton = document.getElementById("xdChatAttach");

    const fileInput = document.getElementById("xdChatFileInput");

    sendButton.addEventListener("click", function () {

        enableNotificationHelper();

        sendAgentMessage();

    });

    input.addEventListener("keydown", function (event) {

        enableNotificationHelper();

        if (event.key === "Enter") {

            sendAgentMessage();

        }

    });

    input.addEventListener("input", function () {

        enableNotificationHelper();

        if (
            activeChatId > 0 &&
            activeChatStatus !== "closed" &&
            input.value.trim() !== ""
        ) {
            sendAdminTypingPresence();
        }

    });

    attachButton.addEventListener("click", function () {

        enableNotificationHelper();

        if (activeChatId <= 0 || activeChatStatus === "closed") {
            return;
        }

        fileInput.click();

    });

    fileInput.addEventListener("change", function () {

        enableNotificationHelper();

        if (fileInput.files.length === 0) {
            return;
        }

        sendAgentFile(fileInput.files[0]);

        fileInput.value = "";

    });

}


/* ==========================================
   09. SEND AGENT MESSAGE
========================================== */

function sendAgentMessage() {

    const input = document.getElementById("xdChatInput");

    const message = input.value.trim();

    if (activeChatId <= 0 || activeChatStatus === "closed" || message === "") {
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

            loadChat(activeChatId, "send");

            loadChatList();

        })

        .catch(function (error) {
            console.error(error);
        });

}


/* ==========================================
   10. SCROLL MESSAGES BOTTOM
========================================== */

function scrollMessagesBottom() {

    const messageBox = document.getElementById("xdChatMessages");

    messageBox.scrollTop = messageBox.scrollHeight;

    hasUnreadMessages = false;

    hideNewMessagesButton();

}


function sendAgentFile(file) {

    if (activeChatId <= 0 || activeChatStatus === "closed") {
        return;
    }

    const formData = new FormData();

    formData.append("chat_id", activeChatId);

    formData.append("chat_file", file);

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

            loadChat(activeChatId, "send");

            loadChatList();

        })

        .catch(function (error) {
            console.error(error);
        });

}


/* ==========================================
   11. SMART AUTO SCROLL
========================================== */

function isMessageBoxAtBottom(messageBox) {

    if (!messageBox) {
        return true;
    }

    const bottomOffset =
        messageBox.scrollHeight - messageBox.scrollTop - messageBox.clientHeight;

    return bottomOffset <= 80;

}


function getMessageSignature(html) {

    const template = document.createElement("template");

    template.innerHTML = html.trim();

    const messages = template.content.querySelectorAll(".xd-admin-message");

    if (messages.length === 0) {
        return "";
    }

    const lastMessage = messages[messages.length - 1];

    return messages.length + "|" + lastMessage.textContent.trim();

}


function getIncomingMessageSignature(html) {

    const template = document.createElement("template");

    template.innerHTML = html.trim();

    const messages = template.content.querySelectorAll(".xd-admin-message.visitor");

    if (messages.length === 0) {
        return "";
    }

    const lastMessage = messages[messages.length - 1];

    return messages.length + "|" + lastMessage.textContent.trim();

}


function handleSmartScroll(
    messageBox,
    loadReason,
    wasAtBottom,
    hasNewMessages,
    previousScrollTop
) {

    if (loadReason === "open" || loadReason === "send" || isFirstChatLoad || wasAtBottom) {

        scrollMessagesBottom();

        return;

    }

    restoreMessageScrollPosition(
        messageBox,
        previousScrollTop
    );

    if (hasNewMessages) {

        hasUnreadMessages = true;

        showNewMessagesButton();

    }

}


function restoreMessageScrollPosition(messageBox, previousScrollTop) {

    messageBox.scrollTop = previousScrollTop;

}


function registerMessageScrollEvent() {

    const messageBox = document.getElementById("xdChatMessages");

    if (!messageBox) {
        return;
    }

    messageBox.addEventListener("scroll", function () {

        if (isMessageBoxAtBottom(messageBox)) {

            hasUnreadMessages = false;

            hideNewMessagesButton();

        }

    });

}


function registerImageLightbox() {

    const messageBox = document.getElementById("xdChatMessages");

    if (!messageBox) {
        return;
    }

    messageBox.addEventListener("click", function (event) {

        const imageLink = event.target.closest(".xd-chat-image-link");

        if (!imageLink) {
            return;
        }

        event.preventDefault();

        openChatImageLightbox(imageLink.href);

    });

}


function openChatImageLightbox(imageUrl) {

    let lightbox = document.getElementById("xdChatImageLightbox");

    if (!lightbox) {

        lightbox = document.createElement("div");
        lightbox.id = "xdChatImageLightbox";
        lightbox.className = "xd-chat-image-lightbox";
        lightbox.innerHTML = `
            <button type="button">
                &times;
            </button>
            <img src="" alt="Image preview">
        `;

        document.body.appendChild(lightbox);

        lightbox.addEventListener("click", function (event) {

            if (event.target === lightbox || event.target.tagName === "BUTTON") {
                lightbox.classList.remove("active");
            }

        });

    }

    lightbox.querySelector("img").src = imageUrl;
    lightbox.classList.add("active");

}


function createNewMessagesButton() {

    const messageBox = document.getElementById("xdChatMessages");

    if (!messageBox || newMessagesButton) {
        return;
    }

    newMessagesButton = document.createElement("button");

    newMessagesButton.type = "button";

    newMessagesButton.className = "xd-new-messages-button";

    newMessagesButton.innerHTML = "New Messages &darr;";

    newMessagesButton.addEventListener("click", function () {

        enableNotificationHelper();

        messageBox.scrollTo({
            top: messageBox.scrollHeight,
            behavior: "smooth"
        });

        hasUnreadMessages = false;

        hideNewMessagesButton();

    });

    messageBox.parentElement.appendChild(newMessagesButton);

}


function showNewMessagesButton() {

    createNewMessagesButton();

    if (newMessagesButton && hasUnreadMessages) {
        newMessagesButton.classList.add("active");
    }

}


function hideNewMessagesButton() {

    if (newMessagesButton) {
        newMessagesButton.classList.remove("active");
    }

}


/* ==========================================
   12. AUTO REFRESH
========================================== */

setInterval(function () {

    loadChatList();

}, 3000);


setInterval(function () {

    if (activeChatId > 0) {

        loadChat(activeChatId);

    }

}, 2000);


setInterval(function () {

    updateDashboardPresence();

}, 1000);
