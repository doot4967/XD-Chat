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

let isChatStatusUpdating = false;

let chatListSearchQuery = "";

let chatSearchDebounceTimer = null;

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

let activeReplyMessage = null;

const dashboardCsrfToken = window.XD_CSRF_TOKEN || "";

const chatMicIconSvg = `
    <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 14c1.7 0 3-1.3 3-3V6c0-1.7-1.3-3-3-3S9 4.3 9 6v5c0 1.7 1.3 3 3 3z"></path>
        <path d="M17 11c0 2.8-2.2 5-5 5s-5-2.2-5-5H5c0 3.5 2.6 6.4 6 6.9V21h2v-3.1c3.4-.5 6-3.4 6-6.9h-2z"></path>
    </svg>
`;

const chatStopIconSvg = `
    <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M7 7h10v10H7z"></path>
    </svg>
`;

let activeEmojiCategory = "Recent";

let mediaRecorder = null;

let recordingChunks = [];

let recordedVoiceBlob = null;

let recordingStartedAt = 0;

let recordingTimerInterval = null;

let discardStoppedVoicePreview = false;

let voiceRecordingRequestId = 0;

let isVoiceRecordingRequestPending = false;

let chatVoiceFeedbackTimer = null;

const chatMobileQuery = window.matchMedia("(max-width: 992px)");

const chatMobileHistoryKey = "xdChatMobileView";

const chatFilterFocusableTabindex = new Map();

let isChatFilterPopoverOpen = false;

let chatMobileView = "list";

let chatMobileHistoryOwned = false;

let chatMobileViewportFrame = null;

let chatMobileDetailsTrigger = null;

let chatCloseDialogTrigger = null;

let chatBottomStackObserver = null;

let chatMobileNeedsSeenSync = false;

let chatMarkSeenController = null;

let chatListController = null;

let chatListRequestId = 0;

let isChatListRequestPending = false;

let chatLoadController = null;

let chatLoadRequestId = 0;

let isChatLoadRequestPending = false;

let chatPresenceController = null;

let chatPresenceRequestId = 0;

let isChatPresenceRequestPending = false;

let dashboardMessageActionState = null;

let dashboardMessageActionPendingFocusId = "";


/* ==========================================
   02. DOCUMENT READY
========================================== */

document.addEventListener("DOMContentLoaded", function () {

    registerChatMobileLayout();

    loadChatList();

    registerSendEvents();

    registerMessageScrollEvent();

    registerVisitorInfoToggle();

    registerFilterEvents();

    registerSearchEvents();

    registerCloseChatEvent();

    registerImageLightbox();

    registerSingleMediaPlayback();

    registerMessageActions();

    registerReplyPreviewEvents();

});


/* ==========================================
   03. CSRF HELPER
========================================== */

function appendDashboardCsrfToken(formData) {

    formData.append("csrf_token", dashboardCsrfToken);

}


function parseDashboardResponse(response, expectedType) {

    if (
        response.redirected &&
        /\/auth\/login\.php(?:[?#]|$)/i.test(response.url)
    ) {
        window.location.assign(response.url);
        throw new Error("Your session has expired.");
    }

    if (!response.ok) {
        throw new Error("Request failed with status " + response.status + ".");
    }

    if (expectedType === "json") {
        const contentType = response.headers.get("content-type") || "";

        if (!contentType.toLowerCase().includes("application/json")) {
            throw new Error("The server returned an unexpected response.");
        }

        return response.json();
    }

    return response.text();

}


function isAbortedDashboardRequest(error) {

    return Boolean(error && error.name === "AbortError");

}


/* ==========================================
   04. NOTIFICATION HELPER
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

function loadChatList(loadReason) {

    if (loadReason === "poll" && isChatListRequestPending) {
        return;
    }

    const requestId = ++chatListRequestId;

    if (chatListController) {
        chatListController.abort();
    }

    chatListController = window.AbortController
        ? new AbortController()
        : null;

    const params = new URLSearchParams();

    params.append("status", chatListStatusFilter);
    params.append("search", chatListSearchQuery);

    const requestOptions = chatListController
        ? { signal: chatListController.signal }
        : {};

    isChatListRequestPending = true;

    fetch("chat/ajax/chat-list.php?" + params.toString(), requestOptions)

        .then(function (response) {
            return parseDashboardResponse(response, "html");
        })

        .then(function (html) {

            if (requestId !== chatListRequestId) {
                return;
            }

            const focusedChatItem = document.activeElement
                && document.activeElement.classList.contains("xd-chat-list-item")
                ? document.activeElement.dataset.chatId
                : "";

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

            if (focusedChatItem && chatMobileView === "list") {
                const refreshedFocusedItem = document.querySelector(
                    '.xd-chat-list-item[data-chat-id="' + focusedChatItem + '"]'
                );

                if (refreshedFocusedItem) {
                    refreshedFocusedItem.focus({ preventScroll: true });
                }
            }

        })

        .catch(function (error) {
            if (isAbortedDashboardRequest(error)) {
                return;
            }

            console.error(error);
        })

        .finally(function () {
            if (requestId === chatListRequestId) {
                chatListController = null;
                isChatListRequestPending = false;
            }
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
            closeChatFilterPopover(false);

            items.forEach(function (chat) {
                chat.classList.remove("active");
            });

            this.classList.add("active");

            const nextChatId = this.dataset.chatId;

            if (String(activeChatId) !== String(nextChatId)) {
                resetComposerForChatSwitch();
            }

            activeChatId = nextChatId;

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
            clearActiveReplyMessage();

            document.getElementById("xdChatVisitorName").innerText =
                this.dataset.visitorName;

            document.getElementById("xdChatVisitorStatus").innerText =
                "Loading conversation...";

            updateChatControls(activeChatStatus);

            openChatMobileConversation();

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

            chatListStatusFilter = this.dataset.status || "open";

            updateChatFilterActiveState();

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

            resetChatMobileListHistory();

            loadChatList();
            closeChatFilterPopover(true);

        });

    });

}


function updateChatFilterActiveState() {

    const filterButtons = document.querySelectorAll(".xd-chat-filter");

    filterButtons.forEach(function (item) {
        const isActive = (item.dataset.status || "open") === chatListStatusFilter;

        item.classList.toggle("active", isActive);
        item.setAttribute("aria-pressed", isActive ? "true" : "false");
    });

    updateChatFilterToggleLabel();

}


function syncActiveChatFilterWithStatus() {

    const filterCanFollowStatus =
        chatListStatusFilter === "open" ||
        chatListStatusFilter === "closed";

    const activeStatusCanBeListed =
        activeChatStatus === "open" ||
        activeChatStatus === "closed";

    if (
        activeChatId <= 0 ||
        isChatStatusUpdating ||
        !filterCanFollowStatus ||
        !activeStatusCanBeListed ||
        chatListStatusFilter === activeChatStatus
    ) {
        return;
    }

    chatListStatusFilter = activeChatStatus;

    updateChatFilterActiveState();

    loadChatList();

}


function registerSearchEvents() {

    const searchInput = document.getElementById("xdChatSearch");

    if (!searchInput) {
        return;
    }

    searchInput.addEventListener("input", function () {

        clearTimeout(chatSearchDebounceTimer);
        updateChatFilterToggleLabel();

        chatSearchDebounceTimer = setTimeout(function () {

            chatListSearchQuery = searchInput.value.trim();

            loadChatList();

        }, 300);

    });

}


function registerChatFilterPopover() {

    const toggleButton = document.getElementById("xdChatFilterToggle");
    const closeButton = document.getElementById("xdChatFilterClose");
    const popover = document.getElementById("xdChatFilterPopover");

    if (!toggleButton || !closeButton || !popover) {
        return;
    }

    popover.querySelectorAll("input, button").forEach(function (element) {
        chatFilterFocusableTabindex.set(
            element,
            element.hasAttribute("tabindex") ? element.getAttribute("tabindex") : null
        );
    });

    toggleButton.addEventListener("click", function () {
        if (isChatFilterPopoverOpen) {
            closeChatFilterPopover(true);
            return;
        }

        openChatFilterPopover();
    });

    closeButton.addEventListener("click", function () {
        closeChatFilterPopover(true);
    });

    document.addEventListener("click", function (event) {
        if (
            !isChatFilterPopoverOpen ||
            popover.contains(event.target) ||
            toggleButton.contains(event.target)
        ) {
            return;
        }

        closeChatFilterPopover(true);
    });

    syncChatFilterPopoverForViewport();
    updateChatFilterToggleLabel();

}


function openChatFilterPopover() {

    if (!chatMobileQuery.matches || chatMobileView !== "list") {
        return;
    }

    const toggleButton = document.getElementById("xdChatFilterToggle");
    const popover = document.getElementById("xdChatFilterPopover");
    const searchInput = document.getElementById("xdChatSearch");

    if (!toggleButton || !popover || !searchInput) {
        return;
    }

    isChatFilterPopoverOpen = true;
    popover.hidden = false;
    popover.classList.add("is-open");
    popover.setAttribute("aria-hidden", "false");
    toggleButton.setAttribute("aria-expanded", "true");
    setChatFilterPopoverFocusability(true);
    searchInput.focus({ preventScroll: true });

}


function closeChatFilterPopover(restoreFocus) {

    const toggleButton = document.getElementById("xdChatFilterToggle");
    const popover = document.getElementById("xdChatFilterPopover");

    if (!toggleButton || !popover) {
        return;
    }

    const wasOpen = isChatFilterPopoverOpen;
    const shouldRestoreFocus = restoreFocus && wasOpen && chatMobileQuery.matches;

    if (shouldRestoreFocus) {
        toggleButton.focus({ preventScroll: true });
    }

    isChatFilterPopoverOpen = false;
    popover.classList.remove("is-open");
    toggleButton.setAttribute("aria-expanded", "false");

    if (chatMobileQuery.matches) {
        popover.setAttribute("aria-hidden", "true");
        setChatFilterPopoverFocusability(false);
        popover.hidden = true;
    } else {
        popover.hidden = false;
        popover.setAttribute("aria-hidden", "false");
        setChatFilterPopoverFocusability(true);
    }

}


function syncChatFilterPopoverForViewport() {

    if (chatMobileQuery.matches) {
        closeChatFilterPopover(false);
        return;
    }

    isChatFilterPopoverOpen = false;

    const toggleButton = document.getElementById("xdChatFilterToggle");
    const popover = document.getElementById("xdChatFilterPopover");

    if (toggleButton) {
        toggleButton.setAttribute("aria-expanded", "false");
    }

    if (popover) {
        popover.hidden = false;
        popover.classList.remove("is-open");
        popover.setAttribute("aria-hidden", "false");
    }

    setChatFilterPopoverFocusability(true);

}


function setChatFilterPopoverFocusability(isEnabled) {

    chatFilterFocusableTabindex.forEach(function (originalTabindex, element) {
        if (isEnabled) {
            if (originalTabindex === null) {
                element.removeAttribute("tabindex");
            } else {
                element.setAttribute("tabindex", originalTabindex);
            }
            return;
        }

        element.setAttribute("tabindex", "-1");
    });

}


function updateChatFilterToggleLabel() {

    const label = document.getElementById("xdChatFilterToggleLabel");
    const toggleButton = document.getElementById("xdChatFilterToggle");
    const searchInput = document.getElementById("xdChatSearch");
    const statusLabel = chatListStatusFilter.charAt(0).toUpperCase()
        + chatListStatusFilter.slice(1);

    if (label) {
        label.textContent = "Filter \u00B7 " + statusLabel;
    }

    if (toggleButton) {
        toggleButton.setAttribute(
            "aria-label",
            "Filter conversations: " + statusLabel
        );
        toggleButton.classList.toggle(
            "has-search",
            Boolean(searchInput && searchInput.value.trim() !== "")
        );
    }

}


/* ==========================================
   07. LOAD CHAT MESSAGES
========================================== */

function loadChat(chatId, loadReason) {

    if (loadReason === "poll" && isChatLoadRequestPending) {
        return;
    }

    const requestedChatId = String(chatId);
    const requestId = ++chatLoadRequestId;

    if (chatLoadController) {
        chatLoadController.abort();
    }

    chatLoadController = window.AbortController
        ? new AbortController()
        : null;

    const messageBox = document.getElementById("xdChatMessages");

    const wasAtBottom = isMessageBoxAtBottom(messageBox);

    const previousScrollTop = messageBox.scrollTop;

    const requestOptions = chatLoadController
        ? { signal: chatLoadController.signal }
        : {};

    isChatLoadRequestPending = true;

    fetch("chat/ajax/load-chat.php?chat_id=" + encodeURIComponent(chatId), requestOptions)

        .then(function (response) {
            return parseDashboardResponse(response, "html");
        })

        .then(function (html) {

            if (
                requestId !== chatLoadRequestId ||
                requestedChatId !== String(activeChatId)
            ) {
                return;
            }

            const currentMessageSignature = getMessageSignature(html);

            const currentIncomingMessageSignature =
                getIncomingMessageSignature(html);

            const hasNewMessages = previousMessageSignature !== ""
                && previousMessageSignature !== currentMessageSignature;

            const hasNewIncomingMessage =
                previousIncomingMessageSignature !== ""
                && previousIncomingMessageSignature !== currentIncomingMessageSignature;

            const shouldPreserveMedia = isChatMediaActive(messageBox);

            if (previousMessageSignature === currentMessageSignature) {
                updateChatControls(activeChatStatus);
                isChatMessageNotificationReady = true;
                isFirstChatLoad = false;
                updateDashboardPresence(false, "poll");

                if (chatMobileNeedsSeenSync && shouldMarkChatSeen(chatId)) {
                    markChatSeen(chatId);
                    chatMobileNeedsSeenSync = false;
                }

                return;
            }

            closeDashboardMessageActionMenus({ deferFocus: true });

            if (shouldPreserveMedia && previousMessageSignature !== "") {
                appendNewChatMessages(messageBox, html);
                syncDeletedDashboardMessages(messageBox, html);
            } else {
                messageBox.innerHTML = html;
            }

            updateVisitorInfoBox(messageBox);

            syncActiveChatFilterWithStatus();

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

            restoreDashboardMessageActionFocus(messageBox);

            isFirstChatLoad = false;

            if (shouldMarkChatSeen(chatId)) {
                markChatSeen(chatId);
                chatMobileNeedsSeenSync = false;
            }

            updateDashboardPresence(false, "poll");

        })

        .catch(function (error) {
            if (isAbortedDashboardRequest(error)) {
                return;
            }

            console.error(error);
        })

        .finally(function () {
            if (requestId === chatLoadRequestId) {
                chatLoadController = null;
                isChatLoadRequestPending = false;
            }
        });

}


/* ==========================================
   07. VISITOR DETAILS
========================================== */

function updateVisitorInfoBox(messageBox) {

    const visitorInfoBox = document.getElementById("xdChatVisitorInfoContent");

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


function isChatMediaActive(messageBox) {

    const mediaItems = messageBox.querySelectorAll("audio, video");

    return Array.from(mediaItems).some(function (media) {
        return !media.paused || media.currentTime > 0;
    });

}


function appendNewChatMessages(messageBox, html) {

    const template = document.createElement("template");

    template.innerHTML = html.trim();

    template.content.querySelectorAll(".xd-admin-message").forEach(function (message) {

        const messageId = getChatMessageId(message);

        if (messageId && messageBox.querySelector('[data-message-id="' + messageId + '"]')) {
            return;
        }

        messageBox.appendChild(message.cloneNode(true));

    });

}


function syncDeletedDashboardMessages(messageBox, html) {

    const template = document.createElement("template");

    template.innerHTML = html.trim();

    template.content
        .querySelectorAll(".xd-admin-message.deleted")
        .forEach(function (deletedMessage) {

            const messageId = getChatMessageId(deletedMessage);

            if (!messageId) {
                return;
            }

            const currentMessage = messageBox.querySelector(
                '[data-message-id="' + messageId + '"]'
            );

            if (!currentMessage || currentMessage.classList.contains("deleted")) {
                syncDeletedDashboardReplyQuotes(messageBox, messageId);
                return;
            }

            currentMessage.replaceWith(deletedMessage.cloneNode(true));
            syncDeletedDashboardReplyQuotes(messageBox, messageId);

        });

}


function syncDeletedDashboardReplyQuotes(messageBox, messageId) {

    if (
        activeReplyMessage &&
        String(activeReplyMessage.id) === String(messageId)
    ) {
        clearActiveReplyMessage();
    }

    messageBox
        .querySelectorAll('.xd-message-quote[data-reply-id="' + messageId + '"] span')
        .forEach(function (quoteText) {
            quoteText.textContent = "Deleted message";
        });

}


function getChatMessageId(messageElement) {

    if (messageElement.dataset.messageId) {
        return messageElement.dataset.messageId;
    }

    const messageNode = messageElement.querySelector("[data-message-id]");

    if (!messageNode) {
        return "";
    }

    return messageNode.dataset.messageId || "";

}


function registerVisitorInfoToggle() {

    const detailsButton = document.getElementById("xdChatDetailsToggle");

    const visitorInfoBox = document.getElementById("xdChatVisitorInfo");

    const detailsCloseButton = document.getElementById("xdChatDetailsClose");

    const detailsBackdrop = document.getElementById("xdChatDetailsBackdrop");

    if (!detailsButton || !visitorInfoBox) {
        return;
    }

    detailsButton.addEventListener("click", function () {

        if (visitorInfoBox.classList.contains("active")) {
            closeVisitorInfoBox(true);
            return;
        }

        openVisitorInfoBox(detailsButton);

    });

    if (detailsCloseButton) {
        detailsCloseButton.addEventListener("click", function () {
            closeVisitorInfoBox(true);
        });
    }

    if (detailsBackdrop) {
        detailsBackdrop.addEventListener("click", function () {
            closeVisitorInfoBox(true);
        });
    }

}


function openVisitorInfoBox(trigger) {

    const visitorInfoBox = document.getElementById("xdChatVisitorInfo");
    const detailsButton = document.getElementById("xdChatDetailsToggle");
    const detailsBackdrop = document.getElementById("xdChatDetailsBackdrop");
    const detailsCloseButton = document.getElementById("xdChatDetailsClose");

    if (!visitorInfoBox) {
        return;
    }

    closeChatMobileActions();
    chatMobileDetailsTrigger = trigger || detailsButton;
    visitorInfoBox.classList.add("active");
    visitorInfoBox.setAttribute("aria-hidden", "false");
    visitorInfoBox.setAttribute(
        "aria-modal",
        chatMobileQuery.matches ? "true" : "false"
    );

    if (detailsButton) {
        detailsButton.setAttribute("aria-expanded", "true");
    }

    if (chatMobileQuery.matches && detailsBackdrop) {
        detailsBackdrop.classList.add("active");
    }

    if (chatMobileQuery.matches && detailsCloseButton) {
        detailsCloseButton.focus();
    }

}


function closeVisitorInfoBox(restoreFocus) {

    const visitorInfoBox = document.getElementById("xdChatVisitorInfo");

    const detailsButton = document.getElementById("xdChatDetailsToggle");

    const detailsBackdrop = document.getElementById("xdChatDetailsBackdrop");

    if (visitorInfoBox) {
        visitorInfoBox.classList.remove("active");
        visitorInfoBox.setAttribute("aria-hidden", "true");
        visitorInfoBox.setAttribute("aria-modal", "false");
    }

    if (detailsButton) {
        detailsButton.setAttribute("aria-expanded", "false");
    }

    if (detailsBackdrop) {
        detailsBackdrop.classList.remove("active");
    }

    if (
        restoreFocus &&
        chatMobileDetailsTrigger &&
        document.contains(chatMobileDetailsTrigger)
    ) {
        chatMobileDetailsTrigger.focus();
    }

    chatMobileDetailsTrigger = null;

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

        closeChatMobileActions();

        if (activeChatStatus === "closed") {
            closeActiveChat();
            return;
        }

        openChatCloseDialog(closeButton);

    });

    registerChatCloseDialogEvents();

}


function registerChatCloseDialogEvents() {

    const dialog = document.getElementById("xdChatCloseDialog");
    const cancelButton = document.getElementById("xdChatCloseDialogCancel");
    const confirmButton = document.getElementById("xdChatCloseDialogConfirm");

    if (!dialog || !cancelButton || !confirmButton) {
        return;
    }

    cancelButton.addEventListener("click", function () {
        closeChatCloseDialog(true);
    });

    confirmButton.addEventListener("click", function () {
        closeChatCloseDialog(false);
        closeActiveChat();
    });

    dialog.addEventListener("click", function (event) {
        if (event.target === dialog) {
            closeChatCloseDialog(true);
        }
    });

}


function openChatCloseDialog(trigger) {

    const dialog = document.getElementById("xdChatCloseDialog");
    const cancelButton = document.getElementById("xdChatCloseDialogCancel");

    if (!dialog || !cancelButton || activeChatId <= 0) {
        return;
    }

    closeVisitorInfoBox(false);
    chatCloseDialogTrigger = trigger || document.getElementById("xdChatCloseButton");
    dialog.classList.add("active");
    dialog.setAttribute("aria-hidden", "false");
    document.body.classList.add("xd-chat-dialog-open");
    cancelButton.focus();

}


function closeChatCloseDialog(restoreFocus) {

    const dialog = document.getElementById("xdChatCloseDialog");

    if (!dialog) {
        return;
    }

    dialog.classList.remove("active");
    dialog.setAttribute("aria-hidden", "true");
    document.body.classList.remove("xd-chat-dialog-open");

    if (
        restoreFocus &&
        chatCloseDialogTrigger &&
        document.contains(chatCloseDialogTrigger)
    ) {
        chatCloseDialogTrigger.focus();
    }

    chatCloseDialogTrigger = null;

}


function updateChatControls(chatStatus) {

    const input = document.getElementById("xdChatInput");

    const sendButton = document.getElementById("xdChatSend");

    const attachButton = document.getElementById("xdChatAttach");

    const emojiButton = document.getElementById("xdChatEmoji");

    const recordButton = document.getElementById("xdChatRecord");

    const closeButton = document.getElementById("xdChatCloseButton");

    const isClosed = chatStatus === "closed";

    const hasActiveChat = activeChatId > 0;

    input.disabled = !hasActiveChat || isClosed;

    sendButton.disabled = !hasActiveChat || isClosed;

    attachButton.disabled = !hasActiveChat || isClosed;

    emojiButton.disabled = !hasActiveChat || isClosed;

    recordButton.disabled = !hasActiveChat || isClosed || isVoiceRecordingRequestPending;

    closeButton.disabled = !hasActiveChat || isChatStatusUpdating;

    if (closeButton) {
        if (isChatStatusUpdating) {
            closeButton.innerText = isClosed ? "Reopening..." : "Closing...";
        } else {
            closeButton.innerText = isClosed ? "Reopen Chat" : "Close Chat";
        }
    }

    input.placeholder = isClosed
        ? "This chat is closed."
        : "Type your reply...";

    if (!hasActiveChat || isClosed) {
        closeChatComposerPanels();
    }

    updateChatComposerPrimaryAction();

}


function updateChatComposerPrimaryAction() {

    const composer = document.querySelector(".xd-live-chat-composer");
    const input = document.getElementById("xdChatInput");

    if (!composer || !input) {
        return;
    }

    composer.classList.toggle("has-message", input.value.trim() !== "");

}


function closeChatComposerPanels() {

    const attachMenu = document.getElementById("xdChatAttachMenu");
    const emojiPicker = document.getElementById("xdChatEmojiPicker");

    if (attachMenu) {
        attachMenu.classList.remove("active");
    }

    if (emojiPicker) {
        emojiPicker.classList.remove("active");
    }

    if (mediaRecorder && mediaRecorder.state === "recording") {
        stopChatVoiceRecording();
    }

    cancelChatVoiceRecording();

}


function closeActiveChat() {

    if (activeChatId <= 0 || isChatStatusUpdating) {
        const attachMenu = document.getElementById("xdChatAttachMenu");

        if (attachMenu) {
            attachMenu.classList.remove("active");
        }

        return;
    }

    const nextStatus = activeChatStatus === "closed" ? "open" : "closed";

    const formData = new FormData();

    formData.append("chat_id", activeChatId);

    formData.append("status", nextStatus);
    appendDashboardCsrfToken(formData);

    isChatStatusUpdating = true;

    updateChatControls(activeChatStatus);

    fetch("chat/ajax/update-status.php", {
        method: "POST",
        body: formData
    })

        .then(function (response) {
            return parseDashboardResponse(response, "json");
        })

        .then(function (data) {

            if (!data.success) {
                console.error(data.message);
                return;
            }

            activeChatStatus = data.status || nextStatus;

            chatListStatusFilter = activeChatStatus;

            updateChatFilterActiveState();

            updateChatControls(activeChatStatus);

            loadChat(activeChatId, "open");

            loadChatList();

        })

        .catch(function (error) {
            console.error(error);
        })

        .finally(function () {
            isChatStatusUpdating = false;
            updateChatControls(activeChatStatus);
        });

}


function shouldMarkChatSeen(chatId) {

    if (
        parseInt(chatId, 10) !== parseInt(activeChatId, 10) ||
        document.visibilityState !== "visible"
    ) {
        return false;
    }

    if (!chatMobileQuery.matches) {
        return true;
    }

    const conversationPanel = document.getElementById("xdChatConversationPanel");

    return chatMobileView === "conversation"
        && conversationPanel
        && !conversationPanel.hidden;

}


function markChatSeen(chatId) {

    const formData = new FormData();

    formData.append("chat_id", chatId);
    appendDashboardCsrfToken(formData);

    if (chatMarkSeenController) {
        chatMarkSeenController.abort();
    }

    const markSeenController = window.AbortController
        ? new AbortController()
        : null;

    chatMarkSeenController = markSeenController;

    const markSeenRequestOptions = {
        method: "POST",
        body: formData
    };

    if (markSeenController) {
        markSeenRequestOptions.signal = markSeenController.signal;
    }

    fetch("chat/ajax/mark-seen.php", markSeenRequestOptions)

        .then(function (response) {
            return parseDashboardResponse(response, "json");
        })

        .then(function (data) {

            if (data.success) {
                loadChatList();
            }

        })

        .catch(function (error) {
            if (error && error.name === "AbortError") {
                return;
            }

            console.error(error);
        })

        .finally(function () {
            if (chatMarkSeenController === markSeenController) {
                chatMarkSeenController = null;
            }
        });

}


function resetChatWindow() {

    isVisitorTyping = false;
    clearActiveReplyMessage();

    document.getElementById("xdChatVisitorName").innerText =
        "Select a conversation";

    document.getElementById("xdChatVisitorStatus").innerText =
        "Visitor details will appear here.";

    document.getElementById("xdChatMessages").innerHTML = `
        <div class="xd-chat-empty-state large">
            Select a conversation to start chatting.
        </div>
    `;

}


function updateDashboardPresence(isTyping, requestReason) {

    if (activeChatId <= 0 || activeChatStatus === "closed") {
        return;
    }

    if (requestReason === "poll" && isChatPresenceRequestPending) {
        return;
    }

    const requestId = ++chatPresenceRequestId;

    const formData = new FormData();

    formData.append("chat_id", activeChatId);
    formData.append("is_typing", isTyping ? "1" : "0");
    appendDashboardCsrfToken(formData);

    if (chatPresenceController) {
        chatPresenceController.abort();
    }

    const presenceController = window.AbortController
        ? new AbortController()
        : null;

    chatPresenceController = presenceController;

    const requestedChatId = String(activeChatId);

    const requestOptions = {
        method: "POST",
        body: formData
    };

    if (presenceController) {
        requestOptions.signal = presenceController.signal;
    }

    isChatPresenceRequestPending = true;

    fetch("chat/ajax/presence.php", requestOptions)

        .then(function (response) {
            return parseDashboardResponse(response, "json");
        })

        .then(function (data) {

            if (
                requestId !== chatPresenceRequestId ||
                !data.success ||
                activeChatStatus === "closed" ||
                requestedChatId !== String(activeChatId)
            ) {
                return;
            }

            updateVisitorPresenceStatus(data.visitor_online, data.visitor_typing);

        })

        .catch(function (error) {
            if (isAbortedDashboardRequest(error)) {
                return;
            }

            console.error(error);
        })

        .finally(function () {
            if (requestId === chatPresenceRequestId) {
                chatPresenceController = null;
                isChatPresenceRequestPending = false;
            }
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
   09. EMOJI PICKER
========================================== */

const chatEmojiCategories = {
    Recent: [],
    Smileys: ["😀", "😁", "😂", "🤣", "😊", "😍", "😘", "😎", "🙂", "😉", "😢", "😭", "😡", "👍", "🙏", "👏"],
    People: ["👋", "👌", "✌️", "💪", "🤝", "🙋", "🙌", "👨", "👩", "👧", "👦", "🧑", "👮", "👩‍💻", "👨‍💻", "🧑‍💼"],
    Animals: ["🐶", "🐱", "🐭", "🐹", "🐰", "🦊", "🐻", "🐼", "🐨", "🐯", "🦁", "🐮", "🐷", "🐸", "🐵", "🐔"],
    Food: ["🍎", "🍌", "🍇", "🍓", "🍕", "🍔", "🍟", "🌭", "🥪", "🌮", "🍰", "🍫", "☕", "🍵", "🥤", "🍽️"],
    Travel: ["🚗", "🚕", "🚌", "🚆", "✈️", "🚀", "⛵", "🏠", "🏢", "🏥", "🏫", "🏖️", "⛰️", "🌍", "🗺️", "⏰"],
    Objects: ["📱", "💻", "⌨️", "🖱️", "📷", "🎧", "🎁", "💡", "📌", "📎", "✏️", "📁", "🔒", "🔑", "🛒", "💳"],
    Symbols: ["❤️", "💙", "💚", "💛", "⭐", "🔥", "✨", "✅", "❌", "⚠️", "❓", "❗", "💯", "🔔", "📣", "➡️"]
};

const chatEmojiSearchKeywords = {
    "😀": "grin happy smile", "😁": "grin happy", "😂": "laugh joy", "🤣": "laugh rolling", "😊": "smile blush", "😍": "love eyes", "😘": "kiss", "😎": "cool", "🙂": "smile", "😉": "wink", "😢": "sad cry", "😭": "cry", "😡": "angry", "👍": "like thumbs up", "🙏": "thanks pray", "👏": "clap",
    "👋": "hello wave", "👌": "ok", "✌️": "peace", "💪": "strong", "🤝": "handshake", "🙋": "raise hand", "🙌": "celebrate", "👨": "man", "👩": "woman", "👧": "girl", "👦": "boy", "🧑": "person", "👮": "police", "👩‍💻": "developer laptop", "👨‍💻": "developer laptop", "🧑‍💼": "business",
    "🍎": "apple fruit", "🍌": "banana fruit", "🍇": "grapes fruit", "🍓": "strawberry fruit", "🍕": "pizza", "🍔": "burger", "🍟": "fries", "🌭": "hot dog", "🥪": "sandwich", "🌮": "taco", "🍰": "cake", "🍫": "chocolate", "☕": "coffee", "🍵": "tea", "🥤": "drink", "🍽️": "food plate",
    "🚗": "car travel", "🚕": "taxi travel", "🚌": "bus travel", "🚆": "train travel", "✈️": "flight plane travel", "🚀": "rocket", "⛵": "boat", "🏠": "home", "🏢": "office", "🏥": "hospital", "🏫": "school", "🏖️": "beach", "⛰️": "mountain", "🌍": "earth world", "🗺️": "map", "⏰": "time clock",
    "📱": "phone mobile", "💻": "laptop computer", "⌨️": "keyboard", "🖱️": "mouse", "📷": "camera", "🎧": "headphone audio", "🎁": "gift", "💡": "idea light", "📌": "pin", "📎": "clip attachment", "✏️": "pencil", "📁": "folder", "🔒": "lock", "🔑": "key", "🛒": "cart shopping", "💳": "card payment",
    "❤️": "heart love", "💙": "blue heart", "💚": "green heart", "💛": "yellow heart", "⭐": "star", "🔥": "fire", "✨": "sparkle", "✅": "check done", "❌": "cross wrong", "⚠️": "warning", "❓": "question", "❗": "alert", "💯": "hundred", "🔔": "bell notification", "📣": "announcement", "➡️": "right arrow"
};


function getChatRecentEmojis() {

    try {
        return JSON.parse(localStorage.getItem("xd_chat_admin_recent_emojis") || "[]");
    } catch (error) {
        return [];
    }

}


function saveChatRecentEmoji(emoji) {

    const recentEmojis = getChatRecentEmojis().filter(function (item) {
        return item !== emoji;
    });

    recentEmojis.unshift(emoji);

    localStorage.setItem(
        "xd_chat_admin_recent_emojis",
        JSON.stringify(recentEmojis.slice(0, 18))
    );

}


function renderChatEmojiPicker(searchText) {

    const emojiTabs = document.getElementById("xdChatEmojiTabs");
    const emojiGrid = document.getElementById("xdChatEmojiGrid");
    const query = String(searchText || "").toLowerCase();
    const categoryNames = Object.keys(chatEmojiCategories);

    chatEmojiCategories.Recent = getChatRecentEmojis();

    emojiTabs.innerHTML = categoryNames.map(function (categoryName) {
        return `
            <button type="button"
                    class="${activeEmojiCategory === categoryName ? "active" : ""}"
                    data-category="${escapeHTML(categoryName)}">
                ${escapeHTML(categoryName)}
            </button>
        `;
    }).join("");

    let emojis = chatEmojiCategories[activeEmojiCategory] || [];

    if (query !== "") {
        emojis = categoryNames.reduce(function (items, categoryName) {
            return items.concat(chatEmojiCategories[categoryName]);
        }, []);
    }

    emojis = emojis.filter(function (emoji, index) {
        return emojis.indexOf(emoji) === index;
    });

    if (query !== "") {
        emojis = emojis.filter(function (emoji) {
            return getChatEmojiSearchText(emoji).indexOf(query) !== -1;
        });
    }

    if (emojis.length === 0) {
        emojiGrid.innerHTML = `<p class="xd-chat-emoji-empty">No emojis yet.</p>`;
        return;
    }

    emojiGrid.innerHTML = emojis.map(function (emoji) {
        return `
            <button type="button"
                    data-emoji="${escapeHTML(emoji)}">
                ${emoji}
            </button>
        `;
    }).join("");

}


function getChatEmojiSearchText(emoji) {

    let categoryText = "";

    Object.keys(chatEmojiCategories).forEach(function (categoryName) {
        if (chatEmojiCategories[categoryName].indexOf(emoji) !== -1) {
            categoryText += " " + categoryName.toLowerCase();
        }
    });

    return (emoji + " " + categoryText + " " + (chatEmojiSearchKeywords[emoji] || "")).toLowerCase();

}


function insertChatEmojiAtCursor(emoji) {

    const input = document.getElementById("xdChatInput");
    const start = input.selectionStart || 0;
    const end = input.selectionEnd || 0;
    const currentValue = input.value;

    input.value = currentValue.slice(0, start) + emoji + currentValue.slice(end);
    input.focus();
    input.selectionStart = start + emoji.length;
    input.selectionEnd = start + emoji.length;
    updateChatComposerPrimaryAction();

    saveChatRecentEmoji(emoji);
    renderChatEmojiPicker(document.getElementById("xdChatEmojiSearch").value);

}


/* ==========================================
   10. VOICE RECORDING
========================================== */

function getChatVoiceMimeType() {

    const supportedTypes = ["audio/webm", "audio/ogg", "audio/wav"];

    return supportedTypes.find(function (type) {
        return window.MediaRecorder && MediaRecorder.isTypeSupported(type);
    }) || "";

}


function getChatVoiceFileExtension(mimeType) {

    if (mimeType.indexOf("ogg") !== -1) {
        return "ogg";
    }

    if (mimeType.indexOf("wav") !== -1) {
        return "wav";
    }

    return "webm";

}


function startChatVoiceRecording() {

    if (activeChatId <= 0 || activeChatStatus === "closed") {
        return;
    }

    if (!window.isSecureContext || !navigator.mediaDevices) {
        showChatVoiceFeedback(
            "Microphone access requires HTTPS or localhost. Open this dashboard over a secure connection.",
            "error"
        );
        return;
    }

    if (!window.MediaRecorder) {
        showChatVoiceFeedback(
            "Voice recording is not supported by this browser.",
            "error"
        );
        return;
    }

    const recordingRequestId = ++voiceRecordingRequestId;
    const recordButton = document.getElementById("xdChatRecord");

    isVoiceRecordingRequestPending = true;
    recordButton.disabled = true;
    recordButton.setAttribute("aria-busy", "true");
    showChatVoiceFeedback("Waiting for microphone permission...", "info", false);

    navigator.mediaDevices.getUserMedia({ audio: true })
        .then(function (stream) {

            if (
                recordingRequestId !== voiceRecordingRequestId ||
                (chatMobileQuery.matches && chatMobileView !== "conversation")
            ) {
                stream.getTracks().forEach(function (track) {
                    track.stop();
                });
                return;
            }

            isVoiceRecordingRequestPending = false;
            recordButton.removeAttribute("aria-busy");
            updateChatControls(activeChatStatus);

            const mimeType = getChatVoiceMimeType();
            const recorderOptions = mimeType ? { mimeType: mimeType } : {};

            recordingChunks = [];
            recordedVoiceBlob = null;
            mediaRecorder = new MediaRecorder(stream, recorderOptions);

            mediaRecorder.addEventListener("dataavailable", function (event) {
                if (event.data && event.data.size > 0) {
                    recordingChunks.push(event.data);
                }
            });

            mediaRecorder.addEventListener("stop", function () {
                stream.getTracks().forEach(function (track) {
                    track.stop();
                });

                if (discardStoppedVoicePreview) {
                    discardStoppedVoicePreview = false;
                    recordingChunks = [];
                    recordedVoiceBlob = null;
                    cancelChatVoiceRecording();
                    return;
                }

                recordedVoiceBlob = new Blob(recordingChunks, {
                    type: mediaRecorder.mimeType || "audio/webm"
                });

                showChatVoicePreview();
            });

            mediaRecorder.start();
            recordingStartedAt = Date.now();
            document.getElementById("xdChatRecordPanel").classList.add("active");
            document.getElementById("xdChatRecordPreview").classList.remove("active");
            recordButton.classList.add("recording");
            recordButton.setAttribute("aria-pressed", "true");
            recordButton.innerHTML = chatStopIconSvg;
            hideChatVoiceFeedback();
            startChatRecordingTimer();

        })
        .catch(function (error) {
            if (recordingRequestId !== voiceRecordingRequestId) {
                return;
            }

            isVoiceRecordingRequestPending = false;
            recordButton.removeAttribute("aria-busy");
            updateChatControls(activeChatStatus);
            showChatVoiceFeedback(getChatVoiceErrorMessage(error), "error");
            console.error("Voice recording could not start.", error);
        });

}


function stopChatVoiceRecording() {

    const recordButton = document.getElementById("xdChatRecord");

    if (mediaRecorder && mediaRecorder.state === "recording") {
        mediaRecorder.stop();
    }

    stopChatRecordingTimer();
    document.getElementById("xdChatRecordPanel").classList.remove("active");
    recordButton.classList.remove("recording");
    recordButton.setAttribute("aria-pressed", "false");
    recordButton.innerHTML = chatMicIconSvg;

}


function getChatVoiceErrorMessage(error) {

    if (!error || !error.name) {
        return "Voice recording could not start. Please try again.";
    }

    if (error.name === "NotAllowedError" || error.name === "SecurityError") {
        return "Microphone permission is blocked. Allow microphone access in your browser settings.";
    }

    if (error.name === "NotFoundError") {
        return "No microphone was found on this device.";
    }

    if (error.name === "NotReadableError" || error.name === "AbortError") {
        return "The microphone is unavailable or being used by another application.";
    }

    return "Voice recording could not start. Please try again.";

}


function showChatVoiceFeedback(message, type, autoHide) {

    const feedback = document.getElementById("xdChatVoiceFeedback");

    if (!feedback) {
        return;
    }

    window.clearTimeout(chatVoiceFeedbackTimer);
    feedback.textContent = message;
    feedback.hidden = false;
    feedback.classList.toggle("is-info", type === "info");

    if (autoHide !== false) {
        chatVoiceFeedbackTimer = window.setTimeout(hideChatVoiceFeedback, 7000);
    }

    updateChatBottomStackHeight();

}


function hideChatVoiceFeedback() {

    const feedback = document.getElementById("xdChatVoiceFeedback");

    window.clearTimeout(chatVoiceFeedbackTimer);
    chatVoiceFeedbackTimer = null;

    if (feedback) {
        feedback.hidden = true;
        feedback.textContent = "";
        feedback.classList.remove("is-info");
    }

    updateChatBottomStackHeight();

}


function startChatRecordingTimer() {

    stopChatRecordingTimer();

    recordingTimerInterval = setInterval(function () {
        const seconds = Math.floor((Date.now() - recordingStartedAt) / 1000);
        document.getElementById("xdChatRecordTime").innerText = formatChatRecordingTime(seconds);
    }, 250);

}


function stopChatRecordingTimer() {

    if (recordingTimerInterval) {
        clearInterval(recordingTimerInterval);
        recordingTimerInterval = null;
    }

    document.getElementById("xdChatRecordTime").innerText = "00:00";

}


function formatChatRecordingTime(seconds) {

    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;

    return String(minutes).padStart(2, "0")
        + ":"
        + String(remainingSeconds).padStart(2, "0");

}


function showChatVoicePreview() {

    if (!recordedVoiceBlob || recordedVoiceBlob.size === 0) {
        return;
    }

    const previewBox = document.getElementById("xdChatRecordPreview");
    const previewAudio = previewBox.querySelector("audio");

    previewAudio.src = URL.createObjectURL(recordedVoiceBlob);
    previewBox.classList.add("active");

}


function cancelChatVoiceRecording() {

    const previewBox = document.getElementById("xdChatRecordPreview");
    const previewAudio = previewBox.querySelector("audio");

    recordedVoiceBlob = null;
    previewAudio.removeAttribute("src");
    previewBox.classList.remove("active");
    hideChatVoiceFeedback();

}


function sendChatVoiceRecording() {

    if (!recordedVoiceBlob || activeChatId <= 0 || activeChatStatus === "closed") {
        return;
    }

    const mimeType = recordedVoiceBlob.type || "audio/webm";
    const extension = getChatVoiceFileExtension(mimeType);
    const voiceFile = new File(
        [recordedVoiceBlob],
        "voice-message-" + Date.now() + "." + extension,
        { type: mimeType }
    );

    sendAgentFile(voiceFile);
    cancelChatVoiceRecording();

}


/* ==========================================
   11. REGISTER SEND EVENTS
========================================== */

function registerSendEvents() {

    const input = document.getElementById("xdChatInput");

    const sendButton = document.getElementById("xdChatSend");

    const attachButton = document.getElementById("xdChatAttach");

    const fileInput = document.getElementById("xdChatFileInput");

    const attachMenu = document.getElementById("xdChatAttachMenu");

    const attachOptions = document.querySelectorAll("#xdChatAttachMenu button");

    const emojiButton = document.getElementById("xdChatEmoji");

    const emojiPicker = document.getElementById("xdChatEmojiPicker");

    const emojiSearch = document.getElementById("xdChatEmojiSearch");

    const emojiTabs = document.getElementById("xdChatEmojiTabs");

    const emojiGrid = document.getElementById("xdChatEmojiGrid");

    const recordButton = document.getElementById("xdChatRecord");

    const recordCancel = document.getElementById("xdChatRecordCancel");

    const recordSend = document.getElementById("xdChatRecordSend");

    renderChatEmojiPicker("");

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
        updateChatComposerPrimaryAction();

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

        attachMenu.classList.toggle("active");
        emojiPicker.classList.remove("active");

    });

    attachOptions.forEach(function (option) {

        option.addEventListener("click", function () {

            enableNotificationHelper();

            if (activeChatId <= 0 || activeChatStatus === "closed") {
                return;
            }

            fileInput.setAttribute("accept", this.dataset.accept || "");
            attachMenu.classList.remove("active");
            fileInput.click();

        });

    });

    emojiButton.addEventListener("click", function () {

        enableNotificationHelper();

        if (activeChatId <= 0 || activeChatStatus === "closed") {
            return;
        }

        emojiPicker.classList.toggle("active");
        attachMenu.classList.remove("active");
        renderChatEmojiPicker(emojiSearch.value);

    });

    emojiSearch.addEventListener("input", function () {
        renderChatEmojiPicker(emojiSearch.value);
    });

    emojiTabs.addEventListener("click", function (event) {

        const tab = event.target.closest("button");

        if (!tab) {
            return;
        }

        activeEmojiCategory = tab.dataset.category || "Recent";
        renderChatEmojiPicker(emojiSearch.value);

    });

    emojiGrid.addEventListener("click", function (event) {

        const emojiButton = event.target.closest("button");

        if (!emojiButton) {
            return;
        }

        insertChatEmojiAtCursor(emojiButton.dataset.emoji || "");

    });

    recordButton.addEventListener("click", function () {

        enableNotificationHelper();

        if (activeChatId <= 0 || activeChatStatus === "closed") {
            return;
        }

        attachMenu.classList.remove("active");
        emojiPicker.classList.remove("active");

        if (mediaRecorder && mediaRecorder.state === "recording") {
            stopChatVoiceRecording();
            return;
        }

        startChatVoiceRecording();

    });

    recordCancel.addEventListener("click", function () {
        cancelChatVoiceRecording();
    });

    recordSend.addEventListener("click", function () {
        sendChatVoiceRecording();
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
    appendReplyToFormData(formData);
    appendDashboardCsrfToken(formData);

    input.value = "";
    updateChatComposerPrimaryAction();

    fetch("chat/ajax/send-message.php", {
        method: "POST",
        body: formData
    })

        .then(function (response) {
            return parseDashboardResponse(response, "json");
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
    appendReplyToFormData(formData);
    appendDashboardCsrfToken(formData);

    fetch("chat/ajax/send-message.php", {
        method: "POST",
        body: formData
    })

        .then(function (response) {
            return parseDashboardResponse(response, "json");
        })

        .then(function (data) {

            if (!data.success) {
                console.error(data.message);
                return;
            }

            loadChat(activeChatId, "send");

            loadChatList();
            clearActiveReplyMessage();

        })

        .catch(function (error) {
            console.error(error);
        });

}


/* ==========================================
   11. MESSAGE ACTIONS
========================================== */

function registerMessageActions() {

    const messageBox = document.getElementById("xdChatMessages");

    if (!messageBox) {
        return;
    }

    document.addEventListener("click", function (event) {

        const actionButton = event.target.closest(".xd-message-actions button");

        if (!actionButton) {
            return;
        }

        const messageItem = actionButton.closest(".xd-admin-message")
            || (dashboardMessageActionState && dashboardMessageActionState.messageItem);

        if (!messageItem) {
            return;
        }

        if (actionButton.dataset.action === "reply") {
            closeDashboardMessageActionMenus();
            setActiveReplyMessage(messageItem);
            return;
        }

        if (actionButton.dataset.action === "copy") {
            closeDashboardMessageActionMenus();
            copyMessageText(messageItem);
            return;
        }

        if (actionButton.dataset.action === "download") {
            closeDashboardMessageActionMenus();
            downloadDashboardVoiceMessage(actionButton);
            return;
        }

        if (actionButton.dataset.action === "delete") {
            closeDashboardMessageActionMenus();
            confirmDashboardMessageDelete(function () {
                deleteDashboardMessage(messageItem);
            });
        }

    });

    messageBox.addEventListener("click", function (event) {

        if (shouldSkipMessageClick) {
            shouldSkipMessageClick = false;
            event.preventDefault();
            return;
        }

        if (event.target.closest(".xd-message-actions")) {
            return;
        }

        const triggerButton = event.target.closest(".xd-message-menu-trigger");

        if (triggerButton) {
            const messageItem = triggerButton.closest(".xd-admin-message");

            if (messageItem) {
                event.stopPropagation();
                openDashboardMessageActionMenu(messageItem, messageBox);
            }

            return;
        }

        if (!event.target.closest(".xd-admin-message")) {
            closeDashboardMessageActionMenus();
            return;
        }

        if (!event.target.closest(".xd-message-action-wrap")) {
            closeDashboardMessageActionMenus();
        }

    });

    messageBox.addEventListener("contextmenu", function (event) {

        const messageItem = event.target.closest(".xd-admin-message");

        if (
            !messageItem ||
            event.target.closest("a, button, input, audio, video")
        ) {
            return;
        }

        event.preventDefault();
        openDashboardMessageActionMenu(messageItem, messageBox);

    });

    document.addEventListener("click", function (event) {

        if (event.target.closest(".xd-message-actions")) {
            return;
        }

        if (
            !document.getElementById("xdChatMessages").contains(event.target) ||
            (
                !event.target.closest(".xd-admin-message") &&
                !event.target.closest(".xd-message-action-wrap")
            )
        ) {
            closeDashboardMessageActionMenus();
        }

    });

    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape") {
            closeDashboardMessageActionMenus();
        }

    });

    /* Close the fixed-position portal before any scroll can detach it from its trigger. */
    document.addEventListener("scroll", function () {
        closeDashboardMessageActionMenus();
    }, true);

    let messageLongPressTimer = null;
    let shouldSkipMessageClick = false;

    messageBox.addEventListener("pointerdown", function (event) {

        const messageItem = event.target.closest(".xd-admin-message");

        if (
            !messageItem ||
            event.target.closest("a, button, input, audio, video, .xd-message-action-wrap")
        ) {
            return;
        }

        clearTimeout(messageLongPressTimer);

        messageLongPressTimer = setTimeout(function () {
            shouldSkipMessageClick = true;
            openDashboardMessageActionMenu(messageItem, messageBox);
        }, 550);

    });

    ["pointerup", "pointercancel", "pointerleave", "pointermove"].forEach(function (eventName) {

        messageBox.addEventListener(eventName, function () {
            clearTimeout(messageLongPressTimer);
        });

    });

}


function downloadDashboardVoiceMessage(actionButton) {

    const downloadUrl = actionButton.dataset.downloadUrl || "";

    if (downloadUrl === "") {
        return;
    }

    const resolvedUrl = new URL(downloadUrl, window.location.href);

    if (
        resolvedUrl.origin !== window.location.origin ||
        !resolvedUrl.pathname.endsWith("/dashboard/chat/ajax/download-file.php")
    ) {
        return;
    }

    const downloadLink = document.createElement("a");
    downloadLink.href = resolvedUrl.href;
    downloadLink.download = actionButton.dataset.downloadName || "voice-message";
    downloadLink.hidden = true;
    document.body.appendChild(downloadLink);
    downloadLink.click();
    downloadLink.remove();

}


function registerReplyPreviewEvents() {

    const cancelButton = document.getElementById("xdChatReplyCancel");

    if (cancelButton) {
        cancelButton.addEventListener("click", clearActiveReplyMessage);
    }

}


function setActiveReplyMessage(messageItem) {

    activeReplyMessage = {
        id: messageItem.dataset.messageId,
        sender: messageItem.dataset.messageSender === "agent" ? "Admin" : "Visitor",
        text: getShortMessageText(messageItem.dataset.messageText || "")
    };

    document.getElementById("xdChatReplySender").innerText = activeReplyMessage.sender;
    document.getElementById("xdChatReplyText").innerText = activeReplyMessage.text;
    document.getElementById("xdChatReplyPreview").classList.add("active");
    document.getElementById("xdChatInput").focus();

}


function clearActiveReplyMessage() {

    activeReplyMessage = null;

    const replyPreview = document.getElementById("xdChatReplyPreview");

    if (replyPreview) {
        replyPreview.classList.remove("active");
    }

}


function appendReplyToFormData(formData) {

    if (activeReplyMessage && activeReplyMessage.id) {
        formData.append("reply_to_message_id", activeReplyMessage.id);
    }

}


function deleteDashboardMessage(messageItem) {

    if (!messageItem) {
        return;
    }

    const formData = new FormData();

    formData.append("message_id", messageItem.dataset.messageId || "0");
    appendDashboardCsrfToken(formData);

    fetch("chat/ajax/delete-message.php", {
        method: "POST",
        body: formData
    })

        .then(function (response) {
            return parseDashboardResponse(response, "json");
        })

        .then(function (data) {

            if (!data.success) {
                alert(data.message || "Message could not be deleted.");
                return;
            }

            hideDashboardMessageForCurrentUser(messageItem);
            loadChatList();
            return;

        })

        .catch(function (error) {
            console.error(error);
        });

}


function hideDashboardMessageForCurrentUser(messageItem) {

    const messageId = messageItem.dataset.messageId || "";

    if (
        activeReplyMessage &&
        String(activeReplyMessage.id) === String(messageId)
    ) {
        clearActiveReplyMessage();
    }

    messageItem.remove();

}


function confirmDashboardMessageDelete(onConfirm) {

    closeDashboardMessageActionMenus();

    let dialog = document.getElementById("xdDeleteMessageDialog");

    if (!dialog) {

        dialog = document.createElement("div");
        dialog.id = "xdDeleteMessageDialog";
        dialog.className = "xd-delete-dialog";
        dialog.innerHTML = `
            <div class="xd-delete-dialog-card">
                <strong>Delete this message?</strong>
                <div>
                    <button type="button" data-action="cancel">
                        Cancel
                    </button>
                    <button type="button" data-action="delete">
                        Delete
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(dialog);

    }

    dialog.classList.add("active");

    const closeDialog = function () {
        dialog.classList.remove("active");
    };

    dialog.onclick = function (event) {

        const actionButton = event.target.closest("button");

        if (!actionButton) {
            if (event.target === dialog) {
                closeDialog();
            }
            return;
        }

        if (actionButton.dataset.action === "cancel") {
            closeDialog();
            return;
        }

        if (actionButton.dataset.action === "delete") {
            closeDialog();
            onConfirm();
        }

    };

}


function copyMessageText(messageItem) {

    const copyText = messageItem.dataset.messageText || "";

    copyTextToClipboard(copyText).then(function () {
        showCopyFeedback(messageItem);
    });

}


function copyTextToClipboard(text) {

    if (navigator.clipboard && navigator.clipboard.writeText) {
        return navigator.clipboard.writeText(text);
    }

    const textarea = document.createElement("textarea");

    textarea.value = text;
    textarea.style.position = "fixed";
    textarea.style.opacity = "0";

    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand("copy");
    textarea.remove();

    return Promise.resolve();

}


function showCopyFeedback(messageItem) {

    messageItem.classList.add("copied");

    setTimeout(function () {
        messageItem.classList.remove("copied");
    }, 1500);

}


function closeDashboardMessageActionMenus(options) {

    const settings = options || {};
    const shouldRestoreFocus = settings.restoreFocus !== false;
    const currentState = dashboardMessageActionState;

    if (currentState) {
        const messageItem = currentState.messageItem;
        const menu = currentState.menu;
        const originalParent = currentState.originalParent;

        messageItem.classList.remove("actions-open", "actions-up", "actions-left");
        menu.classList.remove("is-portal-open");
        menu.removeAttribute("data-placement");
        menu.removeAttribute("style");
        menu.setAttribute("aria-hidden", "true");

        if (originalParent && document.contains(originalParent)) {
            originalParent.appendChild(menu);
        }

        if (shouldRestoreFocus) {
            if (settings.deferFocus) {
                dashboardMessageActionPendingFocusId = currentState.messageId;
            } else if (
                currentState.trigger &&
                document.contains(currentState.trigger)
            ) {
                currentState.trigger.focus({ preventScroll: true });
            }
        }

        dashboardMessageActionState = null;
    }

    document
        .querySelectorAll(".xd-admin-message.actions-open")
        .forEach(function (messageItem) {
            messageItem.classList.remove("actions-open", "actions-up", "actions-left");

            const menu = messageItem.querySelector(".xd-message-actions");

            if (menu) {
                menu.setAttribute("aria-hidden", "true");
            }
        });

}


function openDashboardMessageActionMenu(messageItem, messageBox) {

    closeDashboardMessageActionMenus({ restoreFocus: false });

    const menu = messageItem.querySelector(".xd-message-actions");
    const trigger = messageItem.querySelector(".xd-message-menu-trigger");

    if (!menu || !trigger) {
        return;
    }

    dashboardMessageActionPendingFocusId = "";
    dashboardMessageActionState = {
        messageItem: messageItem,
        messageId: messageItem.dataset.messageId || "",
        menu: menu,
        originalParent: menu.parentElement,
        trigger: trigger
    };

    messageItem.classList.add("actions-open");
    menu.setAttribute("aria-hidden", "false");
    menu.classList.add("is-portal-open");
    menu.style.visibility = "hidden";
    document.body.appendChild(menu);

    positionDashboardMessageActionMenu(messageItem, messageBox);
    menu.style.removeProperty("visibility");

    const firstAction = menu.querySelector("button:not(:disabled)");

    if (firstAction) {
        firstAction.focus({ preventScroll: true });
    }

}


function positionDashboardMessageActionMenu(messageItem, messageBox) {

    if (!dashboardMessageActionState) {
        return;
    }

    const menu = dashboardMessageActionState.menu;
    const trigger = dashboardMessageActionState.trigger;
    const triggerRect = trigger.getBoundingClientRect();
    const boxRect = messageBox.getBoundingClientRect();
    const composer = document.querySelector(".xd-live-chat-composer");
    const composerRect = composer && composer.getClientRects().length > 0
        ? composer.getBoundingClientRect()
        : null;
    const viewport = window.visualViewport;
    const viewportTop = viewport ? viewport.offsetTop : 0;
    const viewportLeft = viewport ? viewport.offsetLeft : 0;
    const viewportWidth = viewport ? viewport.width : window.innerWidth;
    const viewportHeight = viewport ? viewport.height : window.innerHeight;
    const safeGap = 8;
    const safeTop = Math.max(viewportTop + safeGap, boxRect.top + safeGap);
    let safeBottom = Math.min(
        viewportTop + viewportHeight - safeGap,
        boxRect.bottom - safeGap
    );

    if (composerRect) {
        safeBottom = Math.min(safeBottom, composerRect.top - safeGap);
    }

    if (safeBottom <= safeTop) {
        safeBottom = viewportTop + viewportHeight - safeGap;
    }

    const availableHeight = Math.max(44, safeBottom - safeTop);
    menu.style.maxHeight = Math.floor(availableHeight) + "px";
    menu.style.overflowY = "auto";

    const menuWidth = menu.offsetWidth || 150;
    const menuHeight = Math.min(menu.offsetHeight || 150, availableHeight);
    const spaceBelow = safeBottom - triggerRect.bottom;
    const spaceAbove = triggerRect.top - safeTop;
    const shouldOpenUp =
        spaceBelow < menuHeight + safeGap &&
        spaceAbove > spaceBelow;
    const maximumTop = Math.max(safeTop, safeBottom - menuHeight);
    const desiredTop = shouldOpenUp
        ? triggerRect.top - menuHeight - safeGap
        : triggerRect.bottom + safeGap;
    const menuTop = Math.min(Math.max(desiredTop, safeTop), maximumTop);
    const safeLeft = viewportLeft + safeGap;
    const safeRight = viewportLeft + viewportWidth - safeGap;
    const maximumLeft = Math.max(safeLeft, safeRight - menuWidth);
    const desiredLeft = triggerRect.right - menuWidth;
    const menuLeft = Math.min(Math.max(desiredLeft, safeLeft), maximumLeft);

    messageItem.classList.toggle("actions-up", shouldOpenUp);
    menu.dataset.placement = shouldOpenUp ? "up" : "down";
    menu.style.top = Math.round(menuTop) + "px";
    menu.style.right = "auto";
    menu.style.bottom = "auto";
    menu.style.left = Math.round(menuLeft) + "px";

}


function restoreDashboardMessageActionFocus(messageBox) {

    if (!dashboardMessageActionPendingFocusId || !messageBox) {
        return;
    }

    const pendingMessageId = dashboardMessageActionPendingFocusId;
    dashboardMessageActionPendingFocusId = "";

    const messageItem = Array.from(
        messageBox.querySelectorAll(".xd-admin-message[data-message-id]")
    ).find(function (item) {
        return String(item.dataset.messageId) === String(pendingMessageId);
    });

    const trigger = messageItem
        ? messageItem.querySelector(".xd-message-menu-trigger")
        : null;

    if (trigger) {
        trigger.focus({ preventScroll: true });
    }

}


function getShortMessageText(text) {

    const cleanText = String(text || "Attachment").replace(/\s+/g, " ").trim();

    return cleanText.length > 80
        ? cleanText.slice(0, 77) + "..."
        : cleanText;

}


/* ==========================================
   12. SMART AUTO SCROLL
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

        closeDashboardMessageActionMenus();

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


function registerSingleMediaPlayback() {

    document.addEventListener("play", function (event) {

        if (!event.target.matches("audio, video")) {
            return;
        }

        document.querySelectorAll("audio, video").forEach(function (media) {

            if (media !== event.target && !media.paused) {
                media.pause();
            }

        });

    }, true);

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
   12. MOBILE CHAT LAYOUT CONTROLLER
========================================== */

function registerChatMobileLayout() {

    const root = document.getElementById("xdLiveChat");
    const backButton = document.getElementById("xdChatMobileBack");
    const moreButton = document.getElementById("xdChatMoreActions");
    const closeDialog = document.getElementById("xdChatCloseDialog");

    if (!root || !backButton || !moreButton) {
        return;
    }

    document.body.classList.add("xd-chat-mobile-enhanced");
    registerChatFilterPopover();

    if (closeDialog && closeDialog.parentElement !== document.body) {
        document.body.appendChild(closeDialog);
    }

    backButton.addEventListener("click", function () {
        returnToChatMobileList();
    });

    moreButton.addEventListener("click", function () {
        const actions = document.getElementById("xdChatHeaderActions");

        if (actions && actions.classList.contains("active")) {
            closeChatMobileActions(true);
            return;
        }

        openChatMobileActions();
    });

    document.addEventListener("click", function (event) {
        const actions = document.getElementById("xdChatHeaderActions");

        if (
            actions &&
            actions.classList.contains("active") &&
            !actions.contains(event.target) &&
            !moreButton.contains(event.target)
        ) {
            closeChatMobileActions(false);
        }
    });

    document.addEventListener("keydown", handleChatMobileOverlayKeydown);
    window.addEventListener("popstate", handleChatMobileHistoryChange);

    if (typeof chatMobileQuery.addEventListener === "function") {
        chatMobileQuery.addEventListener("change", handleChatMobileBreakpointChange);
    } else {
        chatMobileQuery.addListener(handleChatMobileBreakpointChange);
    }

    if (window.visualViewport) {
        window.visualViewport.addEventListener("resize", scheduleChatMobileViewportUpdate);
        window.visualViewport.addEventListener("scroll", scheduleChatMobileViewportUpdate);
    }

    registerChatBottomStackObserver();

    if (chatMobileQuery.matches) {
        replaceChatMobileHistoryView("list");
        showChatMobileList({ restoreFocus: false, replaceHistory: false });
    } else {
        showChatDesktopLayout();
    }

}


function openChatMobileConversation() {

    if (!chatMobileQuery.matches || activeChatId <= 0) {
        return;
    }

    showChatMobileConversation({ pushHistory: true, focusBack: true });

}


function showChatMobileConversation(options) {

    const settings = options || {};
    const root = document.getElementById("xdLiveChat");
    const listPanel = document.getElementById("xdChatConversationListPanel");
    const conversationPanel = document.getElementById("xdChatConversationPanel");

    if (!chatMobileQuery.matches || !root || !listPanel || !conversationPanel || activeChatId <= 0) {
        return;
    }

    chatMobileView = "conversation";
    chatMobileNeedsSeenSync = true;
    root.dataset.mobileView = "conversation";
    root.classList.remove("is-list-view");
    root.classList.add("is-conversation-view");
    conversationPanel.hidden = false;
    conversationPanel.setAttribute("aria-hidden", "false");
    document.body.classList.add("xd-chat-conversation-open");

    let movedFocusToConversation = false;

    if (settings.focusBack) {
        const backButton = document.getElementById("xdChatMobileBack");

        if (backButton) {
            backButton.focus({ preventScroll: true });
            movedFocusToConversation = true;
        }
    }

    if (!movedFocusToConversation && listPanel.contains(document.activeElement)) {
        document.activeElement.blur();
    }

    listPanel.hidden = true;
    listPanel.setAttribute("aria-hidden", "true");

    closeChatMobileActions(false);
    scheduleChatMobileViewportUpdate();
    updateChatBottomStackHeight();

    if (settings.pushHistory && getChatMobileHistoryView() !== "conversation") {
        pushChatMobileHistoryView("conversation");
        chatMobileHistoryOwned = true;
    }

}


function showChatMobileList(options) {

    const settings = options || {};
    const root = document.getElementById("xdLiveChat");
    const listPanel = document.getElementById("xdChatConversationListPanel");
    const conversationPanel = document.getElementById("xdChatConversationPanel");

    if (!chatMobileQuery.matches || !root || !listPanel || !conversationPanel) {
        return;
    }

    prepareChatMobileConversationExit();
    chatMobileView = "list";
    root.dataset.mobileView = "list";
    root.classList.remove("is-conversation-view");
    root.classList.add("is-list-view");
    listPanel.hidden = false;
    listPanel.setAttribute("aria-hidden", "false");

    const movedFocusToList = settings.restoreFocus
        ? focusActiveChatListItem({ immediate: true })
        : false;

    if (!movedFocusToList && conversationPanel.contains(document.activeElement)) {
        document.activeElement.blur();
    }

    conversationPanel.hidden = true;
    conversationPanel.setAttribute("aria-hidden", "true");
    document.body.classList.remove("xd-chat-conversation-open");

    if (settings.replaceHistory) {
        replaceChatMobileHistoryView("list");
        chatMobileHistoryOwned = false;
    }

    scheduleChatMobileViewportUpdate();

}


function returnToChatMobileList() {

    if (!chatMobileQuery.matches || chatMobileView !== "conversation") {
        return;
    }

    prepareChatMobileConversationExit();

    if (
        chatMobileHistoryOwned &&
        getChatMobileHistoryView() === "conversation"
    ) {
        chatMobileHistoryOwned = false;
        window.history.back();
        return;
    }

    showChatMobileList({ restoreFocus: true, replaceHistory: true });

}


function resetChatMobileListHistory() {

    if (!chatMobileQuery.matches) {
        return;
    }

    showChatMobileList({ restoreFocus: false, replaceHistory: true });

}


function handleChatMobileHistoryChange(event) {

    if (!chatMobileQuery.matches) {
        removeChatMobileHistoryState();
        return;
    }

    const historyView = getChatMobileHistoryView(event.state);

    if (historyView === "conversation" && activeChatId > 0) {
        chatMobileHistoryOwned = true;
        showChatMobileConversation({ pushHistory: false, focusBack: true });
        loadChat(activeChatId, "open");
        return;
    }

    chatMobileHistoryOwned = false;
    showChatMobileList({ restoreFocus: true, replaceHistory: false });

}


function handleChatMobileBreakpointChange(event) {

    if (event.matches) {
        syncChatFilterPopoverForViewport();
        replaceChatMobileHistoryView("list");
        chatMobileHistoryOwned = false;
        showChatMobileList({ restoreFocus: false, replaceHistory: false });
        return;
    }

    const shouldCollapseOwnedEntry =
        chatMobileHistoryOwned &&
        getChatMobileHistoryView() === "conversation";

    chatMobileHistoryOwned = false;
    syncChatFilterPopoverForViewport();
    showChatDesktopLayout();

    if (shouldCollapseOwnedEntry) {
        window.history.back();
    } else {
        removeChatMobileHistoryState();
    }

}


function showChatDesktopLayout() {

    const root = document.getElementById("xdLiveChat");
    const listPanel = document.getElementById("xdChatConversationListPanel");
    const conversationPanel = document.getElementById("xdChatConversationPanel");

    prepareChatMobileConversationExit();
    syncChatFilterPopoverForViewport();
    chatMobileView = "list";
    document.body.classList.remove("xd-chat-conversation-open");

    if (root) {
        root.dataset.mobileView = "desktop";
        root.classList.remove("is-list-view", "is-conversation-view");
        root.style.removeProperty("--xd-chat-list-height");
        root.style.removeProperty("--xd-chat-viewport-height");
        root.style.removeProperty("--xd-chat-viewport-offset-top");
    }

    if (listPanel) {
        listPanel.hidden = false;
        listPanel.setAttribute("aria-hidden", "false");
    }

    if (conversationPanel) {
        conversationPanel.hidden = false;
        conversationPanel.setAttribute("aria-hidden", "false");
    }

}


function prepareChatMobileConversationExit() {

    if (chatMarkSeenController) {
        chatMarkSeenController.abort();
        chatMarkSeenController = null;
    }

    closeChatMobileActions(false);
    closeVisitorInfoBox(false);
    closeChatCloseDialog(false);
    clearActiveReplyMessage();
    discardChatVoiceRecordingForNavigation();

    const attachMenu = document.getElementById("xdChatAttachMenu");
    const emojiPicker = document.getElementById("xdChatEmojiPicker");

    if (attachMenu) {
        attachMenu.classList.remove("active");
    }

    if (emojiPicker) {
        emojiPicker.classList.remove("active");
    }

}


function resetComposerForChatSwitch() {

    const input = document.getElementById("xdChatInput");

    if (input) {
        input.value = "";
    }

    updateChatComposerPrimaryAction();

    prepareChatMobileConversationExit();

}


function discardChatVoiceRecordingForNavigation() {

    voiceRecordingRequestId += 1;
    isVoiceRecordingRequestPending = false;

    const recordButton = document.getElementById("xdChatRecord");

    if (recordButton) {
        recordButton.removeAttribute("aria-busy");
    }

    if (mediaRecorder && mediaRecorder.state === "recording") {
        discardStoppedVoicePreview = true;
        stopChatVoiceRecording();
        return;
    }

    cancelChatVoiceRecording();

}


function focusActiveChatListItem(options) {

    const settings = options || {};
    const focusItem = function () {
        if (chatMobileView !== "list" || activeChatId <= 0) {
            return false;
        }

        const activeItem = document.querySelector(
            '.xd-chat-list-item[data-chat-id="' + activeChatId + '"]'
        );

        if (activeItem) {
            activeItem.focus({ preventScroll: true });
            return true;
        }

        return false;
    };

    if (settings.immediate) {
        return focusItem();
    }

    window.setTimeout(focusItem, 0);
    return true;

}


function getChatMobileHistoryState(view) {

    const currentState = window.history.state;
    const nextState = currentState && typeof currentState === "object"
        ? Object.assign({}, currentState)
        : {};

    nextState[chatMobileHistoryKey] = view;

    return nextState;

}


function getChatMobileHistoryView(state) {

    const targetState = arguments.length > 0 ? state : window.history.state;

    if (!targetState || typeof targetState !== "object") {
        return "";
    }

    return targetState[chatMobileHistoryKey] || "";

}


function replaceChatMobileHistoryView(view) {

    window.history.replaceState(
        getChatMobileHistoryState(view),
        document.title,
        window.location.href
    );

}


function pushChatMobileHistoryView(view) {

    window.history.pushState(
        getChatMobileHistoryState(view),
        document.title,
        window.location.href
    );

}


function removeChatMobileHistoryState() {

    const currentState = window.history.state;

    if (!currentState || typeof currentState !== "object" || !(chatMobileHistoryKey in currentState)) {
        return;
    }

    const nextState = Object.assign({}, currentState);
    delete nextState[chatMobileHistoryKey];
    window.history.replaceState(nextState, document.title, window.location.href);

}


function openChatMobileActions() {

    const actions = document.getElementById("xdChatHeaderActions");
    const moreButton = document.getElementById("xdChatMoreActions");

    if (!chatMobileQuery.matches || !actions || !moreButton) {
        return;
    }

    closeVisitorInfoBox(false);
    actions.classList.add("active");
    moreButton.setAttribute("aria-expanded", "true");

    const firstAction = actions.querySelector("button:not(:disabled)");

    if (firstAction) {
        firstAction.focus();
    }

}


function closeChatMobileActions(restoreFocus) {

    const actions = document.getElementById("xdChatHeaderActions");
    const moreButton = document.getElementById("xdChatMoreActions");

    if (actions) {
        actions.classList.remove("active");
    }

    if (moreButton) {
        moreButton.setAttribute("aria-expanded", "false");
    }

    if (restoreFocus && moreButton && chatMobileQuery.matches) {
        moreButton.focus();
    }

}


function handleChatMobileOverlayKeydown(event) {

    const closeDialog = document.getElementById("xdChatCloseDialog");
    const detailsDialog = document.getElementById("xdChatVisitorInfo");
    const actions = document.getElementById("xdChatHeaderActions");
    const filterPopover = document.getElementById("xdChatFilterPopover");

    if (closeDialog && closeDialog.classList.contains("active")) {
        if (event.key === "Escape") {
            event.preventDefault();
            closeChatCloseDialog(true);
            return;
        }

        trapChatOverlayFocus(closeDialog, event);
        return;
    }

    if (
        chatMobileQuery.matches &&
        detailsDialog &&
        detailsDialog.classList.contains("active")
    ) {
        if (event.key === "Escape") {
            event.preventDefault();
            closeVisitorInfoBox(true);
            return;
        }

        trapChatOverlayFocus(detailsDialog, event);
        return;
    }

    if (chatMobileQuery.matches && actions && actions.classList.contains("active")) {
        if (event.key === "Escape") {
            event.preventDefault();
            closeChatMobileActions(true);
        }
        return;
    }

    if (
        chatMobileQuery.matches &&
        isChatFilterPopoverOpen &&
        filterPopover
    ) {
        if (event.key === "Escape") {
            event.preventDefault();
            closeChatFilterPopover(true);
            return;
        }

        trapChatOverlayFocus(filterPopover, event);
        return;
    }

    if (
        event.key === "Escape" &&
        chatMobileQuery.matches &&
        chatMobileView === "conversation"
    ) {
        event.preventDefault();
        returnToChatMobileList();
    }

}


function trapChatOverlayFocus(container, event) {

    if (event.key !== "Tab") {
        return;
    }

    const focusableItems = Array.from(container.querySelectorAll(
        'button:not(:disabled), a[href], input:not(:disabled), select:not(:disabled), textarea:not(:disabled), [tabindex]:not([tabindex="-1"])'
    )).filter(function (element) {
        return element.getClientRects().length > 0;
    });

    if (focusableItems.length === 0) {
        event.preventDefault();
        return;
    }

    const firstItem = focusableItems[0];
    const lastItem = focusableItems[focusableItems.length - 1];

    if (event.shiftKey && document.activeElement === firstItem) {
        event.preventDefault();
        lastItem.focus();
    } else if (!event.shiftKey && document.activeElement === lastItem) {
        event.preventDefault();
        firstItem.focus();
    }

}


function scheduleChatMobileViewportUpdate() {

    if (chatMobileViewportFrame !== null) {
        return;
    }

    chatMobileViewportFrame = window.requestAnimationFrame(function () {
        chatMobileViewportFrame = null;
        updateChatMobileViewportMetrics();
    });

}


function updateChatMobileViewportMetrics() {

    if (!chatMobileQuery.matches) {
        return;
    }

    const root = document.getElementById("xdLiveChat");

    if (!root) {
        return;
    }

    const viewport = window.visualViewport;
    const viewportHeight = viewport ? viewport.height : window.innerHeight;
    const viewportOffsetTop = viewport ? viewport.offsetTop : 0;

    root.style.setProperty("--xd-chat-viewport-height", Math.round(viewportHeight) + "px");
    root.style.setProperty("--xd-chat-viewport-offset-top", Math.round(viewportOffsetTop) + "px");

    if (chatMobileView === "list") {
        const rootTop = root.getBoundingClientRect().top;
        const usedHeight = Math.max(0, rootTop - viewportOffsetTop);
        const listHeight = Math.max(320, viewportHeight - usedHeight - 16);

        root.style.setProperty("--xd-chat-list-height", Math.round(listHeight) + "px");
    }

}


function registerChatBottomStackObserver() {

    if (!window.ResizeObserver) {
        updateChatBottomStackHeight();
        return;
    }

    const stackItems = [
        document.querySelector(".xd-live-chat-composer"),
        document.getElementById("xdChatReplyPreview"),
        document.getElementById("xdChatVoiceFeedback"),
        document.getElementById("xdChatRecordPanel"),
        document.getElementById("xdChatRecordPreview")
    ].filter(Boolean);

    chatBottomStackObserver = new ResizeObserver(function () {
        updateChatBottomStackHeight();
    });

    stackItems.forEach(function (item) {
        chatBottomStackObserver.observe(item);
    });

    updateChatBottomStackHeight();

}


function updateChatBottomStackHeight() {

    const root = document.getElementById("xdLiveChat");

    if (!root) {
        return;
    }

    const stackItems = [
        document.querySelector(".xd-live-chat-composer"),
        document.getElementById("xdChatReplyPreview"),
        document.getElementById("xdChatVoiceFeedback"),
        document.getElementById("xdChatRecordPanel"),
        document.getElementById("xdChatRecordPreview")
    ].filter(Boolean);

    const stackHeight = stackItems.reduce(function (total, item) {
        return total + item.offsetHeight;
    }, 0);

    root.style.setProperty("--xd-chat-bottom-stack-height", Math.round(stackHeight) + "px");

}


/* ==========================================
   13. AUTO REFRESH
========================================== */

setInterval(function () {

    loadChatList("poll");

}, 3000);


setInterval(function () {

    if (activeChatId > 0) {

        loadChat(activeChatId, "poll");

    }

}, 2000);


setInterval(function () {

    updateDashboardPresence(false, "poll");

}, 1000);
