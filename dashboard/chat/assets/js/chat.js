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

    registerSingleMediaPlayback();

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

            const shouldPreserveMedia = isChatMediaActive(messageBox);

            if (previousMessageSignature === currentMessageSignature) {
                updateChatControls(activeChatStatus);
                isChatMessageNotificationReady = true;
                isFirstChatLoad = false;
                updateDashboardPresence();
                return;
            }

            if (shouldPreserveMedia && previousMessageSignature !== "") {
                appendNewChatMessages(messageBox, html);
            } else {
                messageBox.innerHTML = html;
            }

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

    const emojiButton = document.getElementById("xdChatEmoji");

    const recordButton = document.getElementById("xdChatRecord");

    const closeButton = document.getElementById("xdChatCloseButton");

    const isClosed = chatStatus === "closed";

    const hasActiveChat = activeChatId > 0;

    input.disabled = !hasActiveChat || isClosed;

    sendButton.disabled = !hasActiveChat || isClosed;

    attachButton.disabled = !hasActiveChat || isClosed;

    emojiButton.disabled = !hasActiveChat || isClosed;

    recordButton.disabled = !hasActiveChat || isClosed;

    closeButton.disabled = !hasActiveChat || isClosed;

    input.placeholder = isClosed
        ? "This chat is closed."
        : "Type your reply...";

    if (!hasActiveChat || isClosed) {
        closeChatComposerPanels();
    }

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

    if (activeChatId <= 0 || activeChatStatus === "closed") {
        const attachMenu = document.getElementById("xdChatAttachMenu");

        if (attachMenu) {
            attachMenu.classList.remove("active");
        }

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

    if (
        activeChatId <= 0 ||
        activeChatStatus === "closed" ||
        !navigator.mediaDevices ||
        !window.MediaRecorder
    ) {
        return;
    }

    navigator.mediaDevices.getUserMedia({ audio: true })
        .then(function (stream) {

            const mimeType = getChatVoiceMimeType();
            const recorderOptions = mimeType ? { mimeType: mimeType } : {};
            const recordButton = document.getElementById("xdChatRecord");

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
            recordButton.innerHTML = chatStopIconSvg;
            startChatRecordingTimer();

        })
        .catch(function (error) {
            console.error(error);
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
    recordButton.innerHTML = chatMicIconSvg;

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
