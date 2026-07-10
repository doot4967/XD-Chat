/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : widget.js
Module  : Public Widget Script
Status  : Development
Author  : Umesh + ChatGPT
Created : 06 July 2026
==================================================
*/

(function () {

    "use strict";

    const currentScript = document.currentScript;
    const widgetKey = currentScript.getAttribute("data-widget-key");

    if (!widgetKey) {
        console.error("XD Chat : Widget key missing.");
        return;
    }

    const baseUrl = currentScript.src.split("?")[0].replace("widget.js", "");
    const visitorId = getVisitorId();
    let visitorProfile = getVisitorProfile();
    let lastMessageId = 0;
    let notificationAudioContext = null;
    let isNotificationSoundEnabled = false;

    loadCSS(baseUrl + "widget.css");
    loadWidget();


    /* ==========================================
       01. NOTIFICATION HELPER
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


    function getVisitorId() {

        let visitorId = localStorage.getItem("xd_chat_visitor_id");

        if (!visitorId) {
            visitorId = "VIS-" + Date.now() + "-" + Math.random().toString(16).slice(2, 8).toUpperCase();
            localStorage.setItem("xd_chat_visitor_id", visitorId);
        }

        return visitorId;
    }


    function loadCSS(cssFile) {

        const link = document.createElement("link");
        link.rel = "stylesheet";
        link.href = cssFile;
        document.head.appendChild(link);

    }


    function getVisitorProfile() {

        const savedProfile = localStorage.getItem("xd_chat_visitor_profile");

        if (!savedProfile) {
            return null;
        }

        try {
            return JSON.parse(savedProfile);
        } catch (error) {
            localStorage.removeItem("xd_chat_visitor_profile");
            return null;
        }

    }


    function saveVisitorProfile(profile) {

        visitorProfile = profile;
        localStorage.setItem("xd_chat_visitor_profile", JSON.stringify(profile));

    }


    function getVisitorDevice() {

        const userAgent = navigator.userAgent || "";

        if (/tablet|ipad|playbook|silk/i.test(userAgent)) {
            return "Tablet";
        }

        if (/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i.test(userAgent)) {
            return "Mobile";
        }

        return "Desktop";

    }


    function appendVisitorDetails(formData) {

        const profile = visitorProfile || {};

        formData.append("visitor_name", profile.name || "");
        formData.append("visitor_email", profile.email || "");
        formData.append("visitor_page_url", window.location.href || "");
        formData.append("visitor_referrer", document.referrer || "");
        formData.append("visitor_browser", navigator.userAgent || "");
        formData.append("visitor_device", getVisitorDevice());

    }


    function loadWidget() {

        fetch(baseUrl + "widget-loader.php?key=" + encodeURIComponent(widgetKey))

            .then(function (response) {
                return response.json();
            })

            .then(function (data) {

                if (!data.success) {
                    console.error("XD Chat : " + data.message);
                    return;
                }

                createWidget(data.widget);

            })

            .catch(function (error) {
                console.error("XD Chat Load Error :", error);
            });

    }


    function createWidget(widget) {

        const widgetColor = widget.color || "#2563eb";
        const widgetConfig = widget.config || {};
        const uploadConfig = widgetConfig.uploads || {};
        const uploadFallbacks = {
            documents: ["pdf", "doc", "docx", "xls", "xlsx", "txt"],
            images: ["jpg", "jpeg", "png", "webp"],
            audio: ["mp3", "wav", "ogg"],
            videos: ["mp4", "webm", "mov"]
        };
        const messageMaxLength = parseInt(widgetConfig.messageMaxLength, 10) || 1000;
        const documentAccept = buildUploadAccept(["documents"]);
        const mediaAccept = buildUploadAccept(["images", "videos"]);
        const audioAccept = buildUploadAccept(["audio"]);
        const micIconSvg = `
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 14c1.7 0 3-1.3 3-3V6c0-1.7-1.3-3-3-3S9 4.3 9 6v5c0 1.7 1.3 3 3 3z"></path>
                <path d="M17 11c0 2.8-2.2 5-5 5s-5-2.2-5-5H5c0 3.5 2.6 6.4 6 6.9V21h2v-3.1c3.4-.5 6-3.4 6-6.9h-2z"></path>
            </svg>
        `;
        const stopIconSvg = `
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M7 7h10v10H7z"></path>
            </svg>
        `;
        const downloadIconSvg = `
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 3v10.2l3.6-3.6L17 11l-5 5-5-5 1.4-1.4 3.6 3.6V3h2z"></path>
                <path d="M5 19h14v2H5z"></path>
            </svg>
        `;

        const welcomeMessage = formatMessage(
            widget.welcome_message || "Hi there!\nHow can we help you today?"
        );

        function buildUploadAccept(categories) {

            const extensions = [];

            categories.forEach(function (category) {

                const categoryConfig = uploadConfig[category] || {};
                const categoryExtensions = Array.isArray(categoryConfig.extensions)
                    ? categoryConfig.extensions
                    : uploadFallbacks[category] || [];

                categoryExtensions.forEach(function (extension) {

                    extension = String(extension || "").trim().toLowerCase().replace(/^\./, "");

                    if (extension !== "" && extensions.indexOf(extension) === -1) {
                        extensions.push(extension);
                    }

                });

            });

            return extensions.map(function (extension) {
                return "." + extension;
            }).join(",");

        }

        const bubble = document.createElement("button");
        bubble.className = "xd-chat-bubble";
        bubble.innerHTML = "💬";
        bubble.style.background = widgetColor;

        const bubblePreview = document.createElement("button");
        bubblePreview.className = "xd-chat-bubble-preview";
        bubblePreview.type = "button";

        const unreadBadge = document.createElement("span");
        unreadBadge.className = "xd-chat-unread-badge";

        if (widget.position === "bottom-left") {
            bubble.style.left = "24px";
            bubblePreview.style.left = "24px";
            unreadBadge.style.left = "68px";
        } else {
            bubble.style.right = "24px";
            bubblePreview.style.right = "24px";
            unreadBadge.style.right = "18px";
        }

        const chatWindow = document.createElement("div");
        chatWindow.className = "xd-chat-window";

        if (widget.theme === "dark") {
            chatWindow.classList.add("xd-chat-theme-dark");
        }

        if (widget.position === "bottom-left") {
            chatWindow.style.left = "24px";
        } else {
            chatWindow.style.right = "24px";
        }

        chatWindow.innerHTML = `
            <div class="xd-chat-header" style="background:${widgetColor}">

                <div class="xd-chat-brand">

                    <div class="xd-chat-avatar">
                        💬
                    </div>

                    <div>
                        <div class="xd-chat-title">
                            ${escapeHTML(widget.name || "XD Chat")}
                        </div>

                        <div class="xd-chat-status">
                            <span></span>
                            Online now
                        </div>
                    </div>

                </div>

                <button class="xd-chat-close" type="button">
                    ×
                </button>

            </div>

            <div class="xd-chat-body">

                <div class="xd-chat-message xd-welcome-message">
                    ${widget.welcome_message || "Hi there 👋<br>How can we help you today?"}

                    <span class="xd-chat-time">
                        Just now
                    </span>
                </div>

                <div class="xd-chat-empty">
                    Start the conversation below 👇
                </div>

            </div>

            <button class="xd-chat-new-message"
                    type="button">
                ↓ New Message <span>0</span>
            </button>

            <div class="xd-chat-reply-preview">
                <div>
                    <strong></strong>
                    <span></span>
                </div>
                <button type="button"
                        aria-label="Cancel reply">
                    ×
                </button>
            </div>

            <form class="xd-chat-preform">

                <strong>Before we start</strong>

                <input type="text"
                       class="xd-chat-name"
                       placeholder="Your name *">

                <input type="email"
                       class="xd-chat-email"
                       placeholder="Email address (optional)">

                <p class="xd-chat-form-error"></p>

                <button type="submit"
                        style="background:${widgetColor}">
                    Start Chat
                </button>

            </form>

            <div class="xd-chat-footer">

                <input type="file"
                       class="xd-chat-file-input"
                       hidden>

                <div class="xd-chat-attach-wrap">

                    <button class="xd-chat-attach"
                            type="button"
                            title="Attach file">
                        +
                    </button>

                    <div class="xd-chat-attach-menu">

                        <button type="button"
                                data-accept="${escapeHTML(documentAccept)}">
                            Document
                        </button>

                        <button type="button"
                                data-accept="${escapeHTML(mediaAccept)}">
                            Photos & Videos
                        </button>

                        <button type="button"
                                data-accept="${escapeHTML(audioAccept)}">
                            Audio
                        </button>

                    </div>

                </div>

                <div class="xd-chat-emoji-wrap">

                    <button class="xd-chat-emoji-toggle"
                            type="button"
                            title="Emoji">
                        😊
                    </button>

                    <div class="xd-chat-emoji-picker">

                        <input type="text"
                               class="xd-chat-emoji-search"
                               placeholder="Search emoji...">

                        <div class="xd-chat-emoji-tabs"></div>

                        <div class="xd-chat-emoji-grid"></div>

                    </div>

                </div>

                <input type="text"
                       class="xd-chat-input"
                       maxlength="${messageMaxLength}"
                       placeholder="Type your message...">

                <button class="xd-chat-record"
                        type="button"
                        title="Record voice">
                    ${micIconSvg}
                </button>

                <button class="xd-chat-send"
                        type="button"
                        style="background:${widgetColor}">
                    ➤
                </button>

            </div>

            <div class="xd-chat-record-panel">
                <span class="xd-chat-record-dot"></span>
                <strong class="xd-chat-record-time">00:00</strong>
                <span class="xd-chat-record-label">Recording...</span>
            </div>

            <div class="xd-chat-record-preview">
                <audio controls></audio>
                <button type="button"
                        class="xd-chat-record-cancel">
                    Cancel
                </button>
                <button type="button"
                        class="xd-chat-record-send"
                        style="background:${widgetColor}">
                    Send
                </button>
            </div>
        `;

        document.body.appendChild(chatWindow);
        document.body.appendChild(bubblePreview);
        document.body.appendChild(unreadBadge);
        document.body.appendChild(bubble);

        const lightbox = document.createElement("div");
        lightbox.className = "xd-chat-lightbox";
        lightbox.innerHTML = `
            <button type="button" class="xd-chat-lightbox-close">
                Ã—
            </button>
            <img src="" alt="Image preview">
        `;

        document.body.appendChild(lightbox);

        const closeButton = chatWindow.querySelector(".xd-chat-close");
        const chatBody = chatWindow.querySelector(".xd-chat-body");
        const newMessageButton = chatWindow.querySelector(".xd-chat-new-message");
        const newMessageCount = chatWindow.querySelector(".xd-chat-new-message span");
        const replyPreview = chatWindow.querySelector(".xd-chat-reply-preview");
        const replyPreviewSender = chatWindow.querySelector(".xd-chat-reply-preview strong");
        const replyPreviewText = chatWindow.querySelector(".xd-chat-reply-preview span");
        const replyPreviewCancel = chatWindow.querySelector(".xd-chat-reply-preview button");
        const preChatForm = chatWindow.querySelector(".xd-chat-preform");
        const nameInput = chatWindow.querySelector(".xd-chat-name");
        const emailInput = chatWindow.querySelector(".xd-chat-email");
        const formError = chatWindow.querySelector(".xd-chat-form-error");
        const chatFooter = chatWindow.querySelector(".xd-chat-footer");
        const fileInput = chatWindow.querySelector(".xd-chat-file-input");
        const attachButton = chatWindow.querySelector(".xd-chat-attach");
        const attachMenu = chatWindow.querySelector(".xd-chat-attach-menu");
        const attachOptions = chatWindow.querySelectorAll(".xd-chat-attach-menu button");
        const emojiButton = chatWindow.querySelector(".xd-chat-emoji-toggle");
        const emojiPicker = chatWindow.querySelector(".xd-chat-emoji-picker");
        const emojiSearch = chatWindow.querySelector(".xd-chat-emoji-search");
        const emojiTabs = chatWindow.querySelector(".xd-chat-emoji-tabs");
        const emojiGrid = chatWindow.querySelector(".xd-chat-emoji-grid");
        const chatInput = chatWindow.querySelector(".xd-chat-input");
        const recordButton = chatWindow.querySelector(".xd-chat-record");
        const recordPanel = chatWindow.querySelector(".xd-chat-record-panel");
        const recordPreview = chatWindow.querySelector(".xd-chat-record-preview");
        const recordTimer = chatWindow.querySelector(".xd-chat-record-time");
        const recordAudio = chatWindow.querySelector(".xd-chat-record-preview audio");
        const recordCancel = chatWindow.querySelector(".xd-chat-record-cancel");
        const recordSend = chatWindow.querySelector(".xd-chat-record-send");
        const sendButton = chatWindow.querySelector(".xd-chat-send");
        const statusText = chatWindow.querySelector(".xd-chat-status");
        const welcomeBubble = chatWindow.querySelector(".xd-welcome-message");
        const lightboxImage = lightbox.querySelector("img");
        const lightboxClose = lightbox.querySelector(".xd-chat-lightbox-close");
        let lastTypingPingTime = 0;
        const typingThrottleDelay = 1500;
        let isMessageNotificationReady = false;
        let unreadAdminMessageCount = 0;
        let unreadOpenChatMessageCount = 0;
        let allowAutomaticScroll = true;
        let activeReplyMessage = null;
        let activeEmojiCategory = "Recent";
        let mediaRecorder = null;
        let recordingChunks = [];
        let recordedVoiceBlob = null;
        let recordingStartedAt = 0;
        let recordingTimerInterval = null;

        if (welcomeBubble) {

            welcomeBubble.innerHTML = `
                ${welcomeMessage}
                <span class="xd-chat-time">
                    Just now
                </span>
            `;

        }

        setChatReady(!!visitorProfile);

        updateAdminStatus(false);

        if (visitorProfile) {
            updatePresence();
        }


        bubble.addEventListener("click", function () {

            enableNotificationHelper();

            chatWindow.classList.toggle("active");

            if (chatWindow.classList.contains("active")) {
                clearUnreadNotification();
            }

            if (chatWindow.classList.contains("active") && visitorProfile) {
                loadMessagesFromDatabase();
            }

        });

        bubblePreview.addEventListener("click", function () {

            enableNotificationHelper();

            chatWindow.classList.add("active");

            clearUnreadNotification();

            if (visitorProfile) {
                loadMessagesFromDatabase();
            }

        });

        closeButton.addEventListener("click", function () {
            enableNotificationHelper();
            chatWindow.classList.remove("active");
        });


        preChatForm.addEventListener("submit", function (event) {

            event.preventDefault();

            enableNotificationHelper();

            const visitorName = nameInput.value.trim();
            const visitorEmail = emailInput.value.trim();

            if (visitorName === "") {
                formError.textContent = "Name is required.";
                return;
            }

            saveVisitorProfile({
                name: visitorName,
                email: visitorEmail
            });

            formError.textContent = "";
            setChatReady(true);
            updatePresence();
            loadMessagesFromDatabase();
            chatInput.focus();

        });


        function setChatReady(isReady) {

            if (isReady) {
                preChatForm.style.display = "none";
                chatFooter.style.display = "flex";
                return;
            }

            preChatForm.style.display = "grid";
            chatFooter.style.display = "none";

        }


        function sendMessage() {

            enableNotificationHelper();

            if (!visitorProfile) {
                formError.textContent = "Please enter your name to start chat.";
                return;
            }

            const message = chatInput.value.trim();

            if (message === "") {
                return;
            }

            removeEmptyText();

            const pendingMessage = addUserMessage(message);

            chatInput.value = "";

            saveMessageToDatabase(message, pendingMessage);

        }


        function saveMessageToDatabase(message, pendingMessage) {

            const formData = new FormData();

            formData.append("action", "send_message");
            formData.append("widget_key", widgetKey);
            formData.append("visitor_id", visitorId);
            formData.append("message", message);
            appendReplyToFormData(formData);
            appendVisitorDetails(formData);

            fetch(baseUrl + "widget-api.php", {
                method: "POST",
                body: formData
            })

                .then(function (response) {
                    return response.json();
                })

                .then(function (data) {

                    if (!data.success) {
                        console.error("XD Chat Save Error :", data.message);
                        removePendingMessage(pendingMessage);
                        showEmptyTextIfNeeded();
                        return;
                    }

                    clearActiveReplyMessage();
                    loadMessagesFromDatabase();

                })

                .catch(function (error) {
                    console.error("XD Chat API Error :", error);
                });

        }


        function sendFileToDatabase(file) {

            if (!visitorProfile) {
                formError.textContent = "Please enter your name to start chat.";
                return;
            }

            const formData = new FormData();

            formData.append("action", "send_file");
            formData.append("widget_key", widgetKey);
            formData.append("visitor_id", visitorId);
            formData.append("chat_file", file);
            appendReplyToFormData(formData);
            appendVisitorDetails(formData);

            fetch(baseUrl + "widget-api.php", {
                method: "POST",
                body: formData
            })

                .then(function (response) {
                    return response.json();
                })

                .then(function (data) {

                    if (!data.success) {
                        console.error("XD Chat File Error :", data.message);
                        formError.textContent = data.message || "File upload failed.";
                        return;
                    }

                    formError.textContent = "";
                    clearActiveReplyMessage();
                    loadMessagesFromDatabase();

                })

                .catch(function (error) {
                    console.error("XD Chat File API Error :", error);
                });

        }


        /* ==========================================
           02. EMOJI PICKER
        ========================================== */

        const emojiCategories = {
            Recent: [],
            Smileys: ["😀", "😁", "😂", "🤣", "😊", "😍", "😘", "😎", "🙂", "😉", "😢", "😭", "😡", "👍", "🙏", "👏"],
            People: ["👋", "👌", "✌️", "💪", "🤝", "🙋", "🙌", "👨", "👩", "👧", "👦", "🧑", "👮", "👩‍💻", "👨‍💻", "🧑‍💼"],
            Animals: ["🐶", "🐱", "🐭", "🐹", "🐰", "🦊", "🐻", "🐼", "🐨", "🐯", "🦁", "🐮", "🐷", "🐸", "🐵", "🐔"],
            Food: ["🍎", "🍌", "🍇", "🍓", "🍕", "🍔", "🍟", "🌭", "🥪", "🌮", "🍰", "🍫", "☕", "🍵", "🥤", "🍽️"],
            Travel: ["🚗", "🚕", "🚌", "🚆", "✈️", "🚀", "⛵", "🏠", "🏢", "🏥", "🏫", "🏖️", "⛰️", "🌍", "🗺️", "⏰"],
            Objects: ["📱", "💻", "⌨️", "🖱️", "📷", "🎧", "🎁", "💡", "📌", "📎", "✏️", "📁", "🔒", "🔑", "🛒", "💳"],
            Symbols: ["❤️", "💙", "💚", "💛", "⭐", "🔥", "✨", "✅", "❌", "⚠️", "❓", "❗", "💯", "🔔", "📣", "➡️"]
        };

        const emojiSearchKeywords = {
            "😀": "grin happy smile", "😁": "grin happy", "😂": "laugh joy", "🤣": "laugh rolling", "😊": "smile blush", "😍": "love eyes", "😘": "kiss", "😎": "cool", "🙂": "smile", "😉": "wink", "😢": "sad cry", "😭": "cry", "😡": "angry", "👍": "like thumbs up", "🙏": "thanks pray", "👏": "clap",
            "👋": "hello wave", "👌": "ok", "✌️": "peace", "💪": "strong", "🤝": "handshake", "🙋": "raise hand", "🙌": "celebrate", "👨": "man", "👩": "woman", "👧": "girl", "👦": "boy", "🧑": "person", "👮": "police", "👩‍💻": "developer laptop", "👨‍💻": "developer laptop", "🧑‍💼": "business",
            "🍎": "apple fruit", "🍌": "banana fruit", "🍇": "grapes fruit", "🍓": "strawberry fruit", "🍕": "pizza", "🍔": "burger", "🍟": "fries", "🌭": "hot dog", "🥪": "sandwich", "🌮": "taco", "🍰": "cake", "🍫": "chocolate", "☕": "coffee", "🍵": "tea", "🥤": "drink", "🍽️": "food plate",
            "🚗": "car travel", "🚕": "taxi travel", "🚌": "bus travel", "🚆": "train travel", "✈️": "flight plane travel", "🚀": "rocket", "⛵": "boat", "🏠": "home", "🏢": "office", "🏥": "hospital", "🏫": "school", "🏖️": "beach", "⛰️": "mountain", "🌍": "earth world", "🗺️": "map", "⏰": "time clock",
            "📱": "phone mobile", "💻": "laptop computer", "⌨️": "keyboard", "🖱️": "mouse", "📷": "camera", "🎧": "headphone audio", "🎁": "gift", "💡": "idea light", "📌": "pin", "📎": "clip attachment", "✏️": "pencil", "📁": "folder", "🔒": "lock", "🔑": "key", "🛒": "cart shopping", "💳": "card payment",
            "❤️": "heart love", "💙": "blue heart", "💚": "green heart", "💛": "yellow heart", "⭐": "star", "🔥": "fire", "✨": "sparkle", "✅": "check done", "❌": "cross wrong", "⚠️": "warning", "❓": "question", "❗": "alert", "💯": "hundred", "🔔": "bell notification", "📣": "announcement", "➡️": "right arrow"
        };


        function getRecentEmojis() {

            try {
                return JSON.parse(localStorage.getItem("xd_chat_recent_emojis") || "[]");
            } catch (error) {
                return [];
            }

        }


        function saveRecentEmoji(emoji) {

            const recentEmojis = getRecentEmojis().filter(function (item) {
                return item !== emoji;
            });

            recentEmojis.unshift(emoji);

            localStorage.setItem(
                "xd_chat_recent_emojis",
                JSON.stringify(recentEmojis.slice(0, 18))
            );

        }


        function renderEmojiPicker(searchText) {

            const query = String(searchText || "").toLowerCase();
            const categoryNames = Object.keys(emojiCategories);

            emojiCategories.Recent = getRecentEmojis();

            emojiTabs.innerHTML = categoryNames.map(function (categoryName) {
                return `
                    <button type="button"
                            class="${activeEmojiCategory === categoryName ? "active" : ""}"
                            data-category="${escapeHTML(categoryName)}">
                        ${escapeHTML(categoryName)}
                    </button>
                `;
            }).join("");

            let emojis = emojiCategories[activeEmojiCategory] || [];

            if (query !== "") {
                emojis = categoryNames.reduce(function (items, categoryName) {
                    return items.concat(emojiCategories[categoryName]);
                }, []);
            }

            emojis = emojis.filter(function (emoji, index) {
                return emojis.indexOf(emoji) === index;
            });

            if (query !== "") {
                emojis = emojis.filter(function (emoji) {
                    return getEmojiSearchText(emoji).indexOf(query) !== -1;
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


        function getEmojiSearchText(emoji) {

            let categoryText = "";

            Object.keys(emojiCategories).forEach(function (categoryName) {
                if (emojiCategories[categoryName].indexOf(emoji) !== -1) {
                    categoryText += " " + categoryName.toLowerCase();
                }
            });

            return (emoji + " " + categoryText + " " + (emojiSearchKeywords[emoji] || "")).toLowerCase();

        }


        function insertEmojiAtCursor(emoji) {

            const start = chatInput.selectionStart || 0;
            const end = chatInput.selectionEnd || 0;
            const currentValue = chatInput.value;

            chatInput.value = currentValue.slice(0, start) + emoji + currentValue.slice(end);
            chatInput.focus();
            chatInput.selectionStart = start + emoji.length;
            chatInput.selectionEnd = start + emoji.length;

            saveRecentEmoji(emoji);
            renderEmojiPicker(emojiSearch.value);

        }


        /* ==========================================
           03. VOICE RECORDING
        ========================================== */

        function getVoiceMimeType() {

            const supportedTypes = ["audio/webm", "audio/ogg", "audio/wav"];

            return supportedTypes.find(function (type) {
                return window.MediaRecorder && MediaRecorder.isTypeSupported(type);
            }) || "";

        }


        function getVoiceFileExtension(mimeType) {

            if (mimeType.indexOf("ogg") !== -1) {
                return "ogg";
            }

            if (mimeType.indexOf("wav") !== -1) {
                return "wav";
            }

            return "webm";

        }


        function startVoiceRecording() {

            if (!visitorProfile || !navigator.mediaDevices || !window.MediaRecorder) {
                formError.textContent = "Voice recording is not supported in this browser.";
                return;
            }

            navigator.mediaDevices.getUserMedia({ audio: true })
                .then(function (stream) {

                    const mimeType = getVoiceMimeType();
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

                        recordedVoiceBlob = new Blob(recordingChunks, {
                            type: mediaRecorder.mimeType || "audio/webm"
                        });

                        showVoicePreview();
                    });

                    mediaRecorder.start();
                    recordingStartedAt = Date.now();
                    recordPanel.classList.add("active");
                    recordPreview.classList.remove("active");
                    recordButton.classList.add("recording");
                    recordButton.innerHTML = stopIconSvg;
                    startRecordingTimer();

                })
                .catch(function () {
                    formError.textContent = "Microphone permission is required.";
                });

        }


        function stopVoiceRecording() {

            if (mediaRecorder && mediaRecorder.state === "recording") {
                mediaRecorder.stop();
            }

            stopRecordingTimer();
            recordPanel.classList.remove("active");
            recordButton.classList.remove("recording");
            recordButton.innerHTML = micIconSvg;

        }


        function startRecordingTimer() {

            stopRecordingTimer();

            recordingTimerInterval = setInterval(function () {
                const seconds = Math.floor((Date.now() - recordingStartedAt) / 1000);
                recordTimer.textContent = formatRecordingTime(seconds);
            }, 250);

        }


        function stopRecordingTimer() {

            if (recordingTimerInterval) {
                clearInterval(recordingTimerInterval);
                recordingTimerInterval = null;
            }

            recordTimer.textContent = "00:00";

        }


        function formatRecordingTime(seconds) {

            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;

            return String(minutes).padStart(2, "0")
                + ":"
                + String(remainingSeconds).padStart(2, "0");

        }


        function showVoicePreview() {

            if (!recordedVoiceBlob || recordedVoiceBlob.size === 0) {
                return;
            }

            recordAudio.src = URL.createObjectURL(recordedVoiceBlob);
            recordPreview.classList.add("active");

        }


        function cancelVoiceRecording() {

            recordedVoiceBlob = null;
            recordAudio.removeAttribute("src");
            recordPreview.classList.remove("active");

        }


        function sendVoiceRecording() {

            if (!recordedVoiceBlob) {
                return;
            }

            const mimeType = recordedVoiceBlob.type || "audio/webm";
            const extension = getVoiceFileExtension(mimeType);
            const voiceFile = new File(
                [recordedVoiceBlob],
                "voice-message-" + Date.now() + "." + extension,
                { type: mimeType }
            );

            sendFileToDatabase(voiceFile);
            cancelVoiceRecording();

        }


        function loadMessagesFromDatabase() {

            const formData = new FormData();
            const wasAtBottom = isChatBodyAtBottom();
            const previousScrollTop = chatBody.scrollTop;

            formData.append("action", "load_messages");
            formData.append("widget_key", widgetKey);
            formData.append("visitor_id", visitorId);
            formData.append("last_message_id", lastMessageId);
            appendVisitorDetails(formData);

            fetch(baseUrl + "widget-api.php", {
                method: "POST",
                body: formData
            })

                .then(function (response) {
                    return response.json();
                })

                .then(function (data) {

                    if (!data.success) {
                        console.error("XD Chat Load Messages Error :", data.message);
                        return;
                    }

                    syncHiddenMessages(data.hidden_message_ids || []);
                    syncDeletedMessages(data.deleted_message_ids || []);

                    if (data.messages.length > 0) {

                        closeMessageActionMenus();
                        removeEmptyText();

                        let hasNewAdminMessage = false;
                        let latestAdminMessage = "";
                        let newAdminMessageCount = 0;

                        allowAutomaticScroll = wasAtBottom || !isMessageNotificationReady;

                        data.messages.forEach(function (item) {

                            const messageId = parseInt(item.id, 10);

                            if (hasDatabaseMessage(messageId)) {
                                return;
                            }

                            if (item.sender === "visitor") {
                                removePendingUserMessage(item.message);
                                addMessageFromDatabase(item, messageId, true);
                            } else {
                                addMessageFromDatabase(item, messageId, false);

                                if (isMessageNotificationReady) {
                                    hasNewAdminMessage = true;
                                    latestAdminMessage = item.message;
                                    newAdminMessageCount += 1;
                                }
                            }

                        });

                        if (hasNewAdminMessage) {
                            playNotificationSound();

                            if (!chatWindow.classList.contains("active")) {
                                showUnreadNotification(latestAdminMessage);
                            } else if (!wasAtBottom) {
                                showOpenChatNewMessageBadge(newAdminMessageCount);
                            }
                        }

                        if (wasAtBottom || !isMessageNotificationReady) {
                            scrollChatToBottom(true);
                        } else {
                            chatBody.scrollTop = previousScrollTop;
                        }

                        allowAutomaticScroll = true;

                    }

                    if (data.last_message_id) {
                        lastMessageId = Math.max(lastMessageId, parseInt(data.last_message_id, 10));
                    }

                    isMessageNotificationReady = true;

                })

                .catch(function (error) {
                    console.error("XD Chat Load Messages API Error :", error);
                });

        }


        function updatePresence(isTyping) {

            if (!visitorProfile) {
                return;
            }

            const formData = new FormData();

            formData.append("action", "presence");
            formData.append("widget_key", widgetKey);
            formData.append("visitor_id", visitorId);
            formData.append("is_typing", isTyping ? "1" : "0");
            appendVisitorDetails(formData);

            fetch(baseUrl + "widget-api.php", {
                method: "POST",
                body: formData
            })

                .then(function (response) {
                    return response.json();
                })

                .then(function (data) {

                    if (!data.success) {
                        console.error("XD Chat Presence Error :", data.message);
                        return;
                    }

                    updateAdminStatus(data.admin_online);
                    updateAdminTyping(data.admin_typing);

                })

                .catch(function (error) {
                    console.error("XD Chat Presence API Error :", error);
                });

        }


        function sendTypingPresence() {

            const now = Date.now();

            if (now - lastTypingPingTime < typingThrottleDelay) {
                return;
            }

            lastTypingPingTime = now;

            updatePresence(true);

        }


        function updateAdminStatus(isOnline) {

            if (!statusText) {
                return;
            }

            statusText.innerHTML = `
                <span class="${isOnline ? "online" : "offline"}"></span>
                ${isOnline ? "Online now" : "Offline"}
            `;

        }


        function updateAdminTyping(isTyping) {

            if (isTyping) {
                showAdminTyping();
                return;
            }

            removeAdminTyping();

        }


        function showAdminTyping() {

            if (chatBody.querySelector(".xd-admin-typing")) {
                return;
            }

            const wasAtBottom = isChatBodyAtBottom();
            const typing = document.createElement("div");

            typing.className = "xd-chat-message xd-chat-typing xd-admin-typing";

            typing.innerHTML = "Admin is typing...";

            chatBody.appendChild(typing);

            if (wasAtBottom) {
                scrollChatToBottom(true);
            }

        }


        function removeAdminTyping() {

            const typing = chatBody.querySelector(".xd-admin-typing");

            if (typing) {
                typing.remove();
            }

        }


        function removeEmptyText() {

            const emptyText = chatBody.querySelector(".xd-chat-empty");

            if (emptyText) {
                emptyText.remove();
            }

        }


        function showEmptyTextIfNeeded() {

            const hasMessages = chatBody.querySelector(".xd-chat-database-message, .xd-chat-local-message");

            if (!hasMessages && !chatBody.querySelector(".xd-chat-empty")) {

                const emptyText = document.createElement("div");
                emptyText.className = "xd-chat-empty";
                emptyText.innerHTML = "Start the conversation below ðŸ‘‡";
                chatBody.appendChild(emptyText);

            }

        }


        function removePendingMessage(pendingMessage) {

            if (pendingMessage) {
                pendingMessage.remove();
            }

        }


        function removePendingUserMessage(message) {

            const pendingMessages = chatBody.querySelectorAll(".xd-chat-local-message");

            pendingMessages.forEach(function (item) {

                if (item.getAttribute("data-message") === message) {
                    item.remove();
                }

            });

        }


        function hasDatabaseMessage(messageId) {

            return !!chatBody.querySelector('[data-message-id="' + messageId + '"]');

        }


        function addMessageFromDatabase(item, messageId, isVisitorMessage) {

            if (parseInt(item.is_deleted || 0, 10) === 1) {
                addDeletedMessage(item, messageId, isVisitorMessage);
                return;
            }

            if (item.message_type === "image") {
                addFileMessage(item, messageId, isVisitorMessage, true);
                return;
            }

            if (item.message_type === "file") {
                addFileMessage(item, messageId, isVisitorMessage, false);
                return;
            }

            if (item.message_type === "audio") {
                addMediaMessage(item, messageId, isVisitorMessage, "audio");
                return;
            }

            if (item.message_type === "video") {
                addMediaMessage(item, messageId, isVisitorMessage, "video");
                return;
            }

            if (isVisitorMessage) {
                addUserMessage(item.message, messageId, item);
                return;
            }

            addAgentMessage(item.message, messageId, item);

        }


        function addDeletedMessage(item, messageId, isVisitorMessage) {

            const deletedMessage = document.createElement("div");

            deletedMessage.className = isVisitorMessage
                ? "xd-chat-message user deleted xd-chat-database-message"
                : "xd-chat-message deleted xd-chat-database-message";

            deletedMessage.setAttribute("data-message-id", messageId);
            deletedMessage.setAttribute("data-copy-text", "Deleted message");
            deletedMessage.setAttribute("data-sender-name", isVisitorMessage ? "Visitor" : "Admin");
            deletedMessage.setAttribute("data-is-deleted", "1");

            deletedMessage.innerHTML = `
                <span class="xd-message-deleted-text">
                    &#128465; This message was deleted.
                </span>
                <span class="xd-chat-time">
                    Just now
                </span>
            `;

            chatBody.appendChild(deletedMessage);

            scrollChatToBottom();

        }


        function addUserMessage(message, messageId, replyData) {

            const userMessage = document.createElement("div");

            userMessage.className = "xd-chat-message user";
            userMessage.setAttribute("data-message", message);
            userMessage.setAttribute("data-copy-text", message);
            userMessage.setAttribute("data-sender-name", "Visitor");

            if (messageId) {
                userMessage.classList.add("xd-chat-database-message");
                userMessage.setAttribute("data-message-id", messageId);
            } else {
                userMessage.classList.add("xd-chat-local-message");
            }

            userMessage.innerHTML = `
                ${renderMessageActions(!!messageId)}
                ${renderQuotedPreview(replyData)}
                ${escapeHTML(message)}
                <span class="xd-chat-time">
                    Just now
                </span>
            `;

            chatBody.appendChild(userMessage);

            scrollChatToBottom(!messageId);

            return userMessage;

        }


        function addFileMessage(item, messageId, isVisitorMessage, isImage) {

            const fileMessage = document.createElement("div");
            const downloadUrl = getFileDownloadUrl(messageId);

            fileMessage.className = isVisitorMessage
                ? "xd-chat-message user xd-chat-file-message xd-chat-database-message"
                : "xd-chat-message xd-chat-file-message xd-chat-database-message";

            fileMessage.setAttribute("data-message", item.message || item.file_name || "");
            fileMessage.setAttribute("data-copy-text", item.file_name || item.message || "Attachment");
            fileMessage.setAttribute("data-sender-name", isVisitorMessage ? "Visitor" : "Admin");
            fileMessage.setAttribute("data-message-id", messageId);

            if (isImage) {

                fileMessage.innerHTML = `
                    ${renderMessageActions(true)}
                    ${renderQuotedPreview(item)}
                    <button class="xd-chat-image-preview" type="button">
                        <img src="${escapeHTML(downloadUrl)}"
                             alt="${escapeHTML(item.file_name || "Image")}">
                    </button>
                    <span class="xd-chat-time">
                        Just now
                    </span>
                `;

                fileMessage
                    .querySelector(".xd-chat-image-preview")
                    .addEventListener("click", function () {
                        openImageLightbox(downloadUrl, item.file_name || "Image");
                    });

            } else {

                fileMessage.innerHTML = `
                    ${renderMessageActions(true)}
                    ${renderQuotedPreview(item)}
                    <div class="xd-chat-file-card">
                        <div class="xd-chat-file-icon">
                            FILE
                        </div>
                        <div class="xd-chat-file-meta">
                            <strong>${escapeHTML(item.file_name || "Attachment")}</strong>
                            <small>${escapeHTML(formatFileSize(parseInt(item.file_size || 0, 10)))}</small>
                        </div>
                        <a href="${escapeHTML(downloadUrl)}"
                           title="Download"
                           aria-label="Download">
                            ${downloadIconSvg}
                        </a>
                    </div>
                    <span class="xd-chat-time">
                        Just now
                    </span>
                `;

            }

            chatBody.appendChild(fileMessage);

            scrollChatToBottom();

        }


        function getFileDownloadUrl(messageId, forceDownload) {

            let url = baseUrl
                + "download-file.php?message_id=" + encodeURIComponent(messageId)
                + "&widget_key=" + encodeURIComponent(widgetKey)
                + "&visitor_id=" + encodeURIComponent(visitorId);

            if (forceDownload) {
                url += "&download=1";
            }

            return url;

        }


        function addMediaMessage(item, messageId, isVisitorMessage, mediaType) {

            const mediaMessage = document.createElement("div");
            const mediaUrl = getFileDownloadUrl(messageId, false);
            const downloadUrl = getFileDownloadUrl(messageId, true);
            const safeName = escapeHTML(item.file_name || "Attachment");
            const safeSize = escapeHTML(formatFileSize(parseInt(item.file_size || 0, 10)));
            const mediaTag = mediaType === "video"
                ? `<video controls src="${escapeHTML(mediaUrl)}"></video>`
                : `<audio controls src="${escapeHTML(mediaUrl)}"></audio>`;

            mediaMessage.className = isVisitorMessage
                ? "xd-chat-message user xd-chat-file-message xd-chat-media-message xd-chat-database-message"
                : "xd-chat-message xd-chat-file-message xd-chat-media-message xd-chat-database-message";

            mediaMessage.setAttribute("data-message", item.message || item.file_name || "");
            mediaMessage.setAttribute("data-copy-text", item.file_name || item.message || "Attachment");
            mediaMessage.setAttribute("data-sender-name", isVisitorMessage ? "Visitor" : "Admin");
            mediaMessage.setAttribute("data-message-id", messageId);

            mediaMessage.innerHTML = `
                ${renderMessageActions(true)}
                ${renderQuotedPreview(item)}
                <div class="xd-chat-media-card">
                    ${mediaTag}
                    <div class="xd-chat-file-meta">
                        <strong>${safeName}</strong>
                        <small>${safeSize}</small>
                    </div>
                    <a href="${escapeHTML(downloadUrl)}"
                       title="Download"
                       aria-label="Download">
                        ${downloadIconSvg}
                    </a>
                </div>
                <span class="xd-chat-time">
                    Just now
                </span>
            `;

            chatBody.appendChild(mediaMessage);

            scrollChatToBottom();

        }


        function formatFileSize(bytes) {

            if (bytes >= 1024 * 1024) {
                return (bytes / 1024 / 1024).toFixed(1) + " MB";
            }

            if (bytes >= 1024) {
                return (bytes / 1024).toFixed(1) + " KB";
            }

            return bytes + " B";

        }


        function openImageLightbox(imageUrl, imageName) {

            lightboxImage.src = imageUrl;
            lightboxImage.alt = imageName;
            lightbox.classList.add("active");

        }


        function closeImageLightbox() {

            lightbox.classList.remove("active");
            lightboxImage.src = "";

        }


        function addAgentMessage(message, messageId, replyData) {

            const agentMessage = document.createElement("div");

            agentMessage.className = "xd-chat-message xd-chat-database-message";
            agentMessage.setAttribute("data-message", message);
            agentMessage.setAttribute("data-copy-text", message);
            agentMessage.setAttribute("data-sender-name", "Admin");

            if (messageId) {
                agentMessage.setAttribute("data-message-id", messageId);
            }

            agentMessage.innerHTML = `
                ${renderMessageActions(true)}
                ${renderQuotedPreview(replyData)}
                ${escapeHTML(message)}
                <span class="xd-chat-time">
                    Just now
                </span>
            `;

            chatBody.appendChild(agentMessage);

            scrollChatToBottom();

        }


        function showUnreadNotification(message) {

            unreadAdminMessageCount += 1;

            unreadBadge.textContent = formatUnreadCount(unreadAdminMessageCount);
            unreadBadge.classList.add("active");

            bubblePreview.textContent = getMessagePreview(message);
            bubblePreview.classList.add("active");

            pulseBubble();

        }


        function clearUnreadNotification() {

            unreadAdminMessageCount = 0;

            unreadBadge.textContent = "";
            unreadBadge.classList.remove("active");

            bubblePreview.textContent = "";
            bubblePreview.classList.remove("active");

            bubble.classList.remove("xd-chat-bubble-pulse");

        }


        function formatUnreadCount(count) {

            return count > 99 ? "99+" : String(count);

        }


        function getMessagePreview(message) {

            const cleanMessage = String(message || "New message from Admin")
                .replace(/\s+/g, " ")
                .trim();

            if (cleanMessage.length <= 50) {
                return cleanMessage;
            }

            return cleanMessage.slice(0, 47) + "...";

        }


        function pulseBubble() {

            bubble.classList.remove("xd-chat-bubble-pulse");

            void bubble.offsetWidth;

            bubble.classList.add("xd-chat-bubble-pulse");

            setTimeout(function () {
                bubble.classList.remove("xd-chat-bubble-pulse");
            }, 900);

        }


        function showTyping() {

            removeTyping();

            const typing = document.createElement("div");

            typing.className = "xd-chat-message xd-chat-typing";

            typing.innerHTML = `
                Typing<span>.</span><span>.</span><span>.</span>
            `;

            chatBody.appendChild(typing);

            if (wasAtBottom) {
                scrollChatToBottom(true);
            }

        }


        function removeTyping() {

            const typing = chatBody.querySelector(".xd-chat-typing");

            if (typing) {
                typing.remove();
            }

        }


        function isChatBodyAtBottom() {

            const bottomOffset =
                chatBody.scrollHeight - chatBody.scrollTop - chatBody.clientHeight;

            return bottomOffset <= 70;

        }


        function scrollChatToBottom(force) {

            if (!force && !allowAutomaticScroll) {
                return;
            }

            chatBody.scrollTop = chatBody.scrollHeight;

        }


        function showOpenChatNewMessageBadge(count) {

            unreadOpenChatMessageCount += count;

            newMessageCount.textContent = formatUnreadCount(unreadOpenChatMessageCount);
            newMessageButton.classList.add("active");

        }


        function clearOpenChatNewMessageBadge() {

            unreadOpenChatMessageCount = 0;
            newMessageCount.textContent = "0";
            newMessageButton.classList.remove("active");

        }


        function syncDeletedMessages(deletedMessageIds) {

            deletedMessageIds.forEach(function (messageId) {

                const messageElement = chatBody.querySelector(
                    '[data-message-id="' + messageId + '"]'
                );

                if (!messageElement || messageElement.classList.contains("deleted")) {
                    syncDeletedReplyQuotes(messageId);
                    return;
                }

                renderDeletedMessageElement(messageElement);
                syncDeletedReplyQuotes(messageId);

            });

        }


        function syncHiddenMessages(hiddenMessageIds) {

            hiddenMessageIds.forEach(function (messageId) {

                const messageElement = chatBody.querySelector(
                    '[data-message-id="' + messageId + '"]'
                );

                if (messageElement) {
                    hideMessageForCurrentUser(messageElement);
                }

            });

        }


        function syncDeletedReplyQuotes(messageId) {

            if (
                activeReplyMessage &&
                String(activeReplyMessage.id) === String(messageId)
            ) {
                clearActiveReplyMessage();
            }

            chatBody
                .querySelectorAll('.xd-message-quote[data-reply-id="' + messageId + '"] span')
                .forEach(function (quoteText) {
                    quoteText.textContent = "Deleted message";
                });

        }


        function renderDeletedMessageElement(messageElement) {

            const senderName = messageElement.getAttribute("data-sender-name") || "Message";
            const messageId = messageElement.getAttribute("data-message-id") || "";
            const isUserMessage = messageElement.classList.contains("user");

            messageElement.className = isUserMessage
                ? "xd-chat-message user deleted xd-chat-database-message"
                : "xd-chat-message deleted xd-chat-database-message";
            messageElement.setAttribute("data-message-id", messageId);
            messageElement.setAttribute("data-copy-text", "Deleted message");
            messageElement.setAttribute("data-sender-name", senderName);
            messageElement.setAttribute("data-is-deleted", "1");
            messageElement.innerHTML = `
                <span class="xd-message-deleted-text">
                    &#128465; This message was deleted.
                </span>
                <span class="xd-chat-time">
                    Just now
                </span>
            `;

        }


        function renderMessageActions(canDeleteForMe) {

            return `
                <div class="xd-message-action-wrap">
                    <button class="xd-message-menu-trigger"
                            type="button"
                            aria-label="Message actions">
                        &#8942;
                    </button>

                    <div class="xd-message-actions"
                         role="menu"
                         aria-hidden="true">
                    ${canDeleteForMe ? `
                        <button type="button" data-action="delete">
                            Delete
                        </button>
                    ` : ""}
                    <button type="button" data-action="reply">↩ Reply</button>
                    <button type="button" data-action="copy">📋 Copy</button>
                    </div>
                </div>
            `;

        }


        function renderQuotedPreview(replyData) {

            const replyId = replyData && (replyData.reply_id || replyData.id);

            if (!replyId || (replyData && replyData.id && !replyData.sender)) {
                return "";
            }

            const sender = replyData.reply_sender || replyData.sender || "";
            const senderName = sender === "agent" || sender === "Admin"
                ? "Admin"
                : "Visitor";
            const messageType = replyData.reply_message_type || replyData.message_type || "text";
            const isReplyDeleted = parseInt(replyData.reply_is_deleted || 0, 10) === 1;
            const text = isReplyDeleted
                ? "Deleted message"
                : (
                    messageType === "text"
                        ? (replyData.reply_message || replyData.message || replyData.text || "")
                        : (replyData.reply_file_name || replyData.file_name || ucfirst(messageType))
                );

            return `
                <div class="xd-message-quote" data-reply-id="${escapeHTML(replyId)}">
                    <strong>${escapeHTML(senderName)}</strong>
                    <span>${escapeHTML(getShortMessageText(text))}</span>
                </div>
            `;

        }


        function setActiveReplyMessage(messageElement) {

            if (!messageElement.getAttribute("data-message-id")) {
                return;
            }

            activeReplyMessage = {
                id: messageElement.getAttribute("data-message-id"),
                sender: messageElement.getAttribute("data-sender-name") || "Message",
                text: getShortMessageText(messageElement.getAttribute("data-copy-text") || "")
            };

            replyPreviewSender.textContent = activeReplyMessage.sender;
            replyPreviewText.textContent = activeReplyMessage.text;
            replyPreview.classList.add("active");
            chatInput.focus();

        }


        function clearActiveReplyMessage() {

            activeReplyMessage = null;
            replyPreview.classList.remove("active");

        }


        function appendReplyToFormData(formData) {

            if (activeReplyMessage && activeReplyMessage.id) {
                formData.append("reply_to_message_id", activeReplyMessage.id);
            }

        }


        function deleteVisitorMessage(messageElement) {

            if (!messageElement) {
                return;
            }

            const formData = new FormData();

            formData.append("action", "delete_message");
            formData.append("widget_key", widgetKey);
            formData.append("visitor_id", visitorId);
            formData.append("message_id", messageElement.getAttribute("data-message-id") || "0");
            appendVisitorDetails(formData);

            fetch(baseUrl + "widget-api.php", {
                method: "POST",
                body: formData
            })

                .then(function (response) {
                    return response.json();
                })

                .then(function (data) {

                    if (!data.success) {
                        formError.textContent = data.message || "Message could not be deleted.";
                        return;
                    }

                    hideMessageForCurrentUser(messageElement);

                })

                .catch(function (error) {
                    console.error("XD Chat Delete Message API Error :", error);
                });

        }


        function hideMessageForCurrentUser(messageElement) {

            const messageId = messageElement.getAttribute("data-message-id") || "";

            if (
                activeReplyMessage &&
                String(activeReplyMessage.id) === String(messageId)
            ) {
                clearActiveReplyMessage();
            }

            messageElement.remove();

            showEmptyTextIfNeeded();

        }


        function confirmMessageDelete(onConfirm) {

            closeMessageActionMenus();

            let dialog = document.querySelector(".xd-delete-dialog");

            if (!dialog) {

                dialog = document.createElement("div");
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

                chatWindow.appendChild(dialog);

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


        function copyMessageText(messageElement) {

            copyTextToClipboard(messageElement.getAttribute("data-copy-text") || "")
                .then(function () {
                    messageElement.classList.add("copied");

                    setTimeout(function () {
                        messageElement.classList.remove("copied");
                    }, 1500);
                });

        }


        function closeMessageActionMenus() {

            chatBody.querySelectorAll(".actions-open").forEach(function (messageElement) {
                messageElement.classList.remove("actions-open");
                messageElement.classList.remove("actions-up");
                messageElement.classList.remove("actions-left");

                const menu = messageElement.querySelector(".xd-message-actions");

                if (menu) {
                    menu.setAttribute("aria-hidden", "true");
                }
            });

        }


        function openMessageActionMenu(messageElement) {

            closeMessageActionMenus();
            messageElement.classList.add("actions-open");

            const menu = messageElement.querySelector(".xd-message-actions");

            if (menu) {
                menu.setAttribute("aria-hidden", "false");
            }

            positionMessageActionMenu(messageElement);

        }


        function positionMessageActionMenu(messageElement) {

            const messageRect = messageElement.getBoundingClientRect();
            const bodyRect = chatBody.getBoundingClientRect();
            const menu = messageElement.querySelector(".xd-message-actions");
            const menuWidth = menu ? menu.offsetWidth : 150;
            const menuHeight = menu ? menu.offsetHeight : 150;
            const safeBottom = Math.min(
                bodyRect.bottom,
                window.innerHeight - 16
            );

            messageElement.classList.remove("actions-up");
            messageElement.classList.remove("actions-left");

            if (safeBottom - messageRect.bottom < menuHeight + 12) {
                messageElement.classList.add("actions-up");
            }

            if (messageRect.right - menuWidth < bodyRect.left + 8) {
                messageElement.classList.add("actions-left");
            }

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


        function getShortMessageText(text) {

            const cleanText = String(text || "Attachment").replace(/\s+/g, " ").trim();

            return cleanText.length > 80
                ? cleanText.slice(0, 77) + "..."
                : cleanText;

        }


        function ucfirst(value) {

            const text = String(value || "");

            return text.charAt(0).toUpperCase() + text.slice(1);

        }


        function pauseOtherChatMedia(activeMedia) {

            chatWindow.querySelectorAll("audio, video").forEach(function (media) {

                if (media !== activeMedia && !media.paused) {
                    media.pause();
                }

            });

        }


        sendButton.addEventListener("click", sendMessage);

        renderEmojiPicker("");

        attachButton.addEventListener("click", function () {

            enableNotificationHelper();

            if (!visitorProfile) {
                formError.textContent = "Please enter your name to start chat.";
                return;
            }

            attachMenu.classList.toggle("active");
            emojiPicker.classList.remove("active");

        });

        attachOptions.forEach(function (option) {

            option.addEventListener("click", function () {

                enableNotificationHelper();

                fileInput.setAttribute("accept", this.dataset.accept || "");
                attachMenu.classList.remove("active");
                fileInput.click();

            });

        });

        fileInput.addEventListener("change", function () {

            enableNotificationHelper();

            if (fileInput.files.length === 0) {
                return;
            }

            sendFileToDatabase(fileInput.files[0]);

            fileInput.value = "";

        });

        emojiButton.addEventListener("click", function () {

            enableNotificationHelper();

            if (!visitorProfile) {
                formError.textContent = "Please enter your name to start chat.";
                return;
            }

            emojiPicker.classList.toggle("active");
            attachMenu.classList.remove("active");
            renderEmojiPicker(emojiSearch.value);

        });

        emojiSearch.addEventListener("input", function () {
            renderEmojiPicker(emojiSearch.value);
        });

        emojiTabs.addEventListener("click", function (event) {

            const tab = event.target.closest("button");

            if (!tab) {
                return;
            }

            activeEmojiCategory = tab.dataset.category || "Recent";
            renderEmojiPicker(emojiSearch.value);

        });

        emojiGrid.addEventListener("click", function (event) {

            const emojiButton = event.target.closest("button");

            if (!emojiButton) {
                return;
            }

            insertEmojiAtCursor(emojiButton.dataset.emoji || "");

        });

        recordButton.addEventListener("click", function () {

            enableNotificationHelper();

            if (!visitorProfile) {
                formError.textContent = "Please enter your name to start chat.";
                return;
            }

            attachMenu.classList.remove("active");
            emojiPicker.classList.remove("active");

            if (mediaRecorder && mediaRecorder.state === "recording") {
                stopVoiceRecording();
                return;
            }

            startVoiceRecording();

        });

        recordCancel.addEventListener("click", function () {
            cancelVoiceRecording();
        });

        recordSend.addEventListener("click", function () {
            sendVoiceRecording();
        });

        replyPreviewCancel.addEventListener("click", clearActiveReplyMessage);

        chatBody.addEventListener("click", function (event) {

            const actionButton = event.target.closest(".xd-message-actions button");

            if (!actionButton) {
                return;
            }

            const messageElement = actionButton.closest(".xd-chat-message");

            if (!messageElement) {
                return;
            }

            if (actionButton.dataset.action === "reply") {
                closeMessageActionMenus();
                setActiveReplyMessage(messageElement);
                return;
            }

            if (actionButton.dataset.action === "copy") {
                closeMessageActionMenus();
                copyMessageText(messageElement);
                return;
            }

            if (actionButton.dataset.action === "delete") {
                closeMessageActionMenus();
                confirmMessageDelete(function () {
                    deleteVisitorMessage(messageElement);
                });
            }

        });

        chatBody.addEventListener("click", function (event) {

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
                const messageElement = triggerButton.closest(".xd-chat-message");

                if (messageElement) {
                    event.stopPropagation();
                    openMessageActionMenu(messageElement);
                }

                return;
            }

            if (!event.target.closest(".xd-chat-message")) {
                closeMessageActionMenus();
                return;
            }

            if (!event.target.closest(".xd-message-action-wrap")) {
                closeMessageActionMenus();
            }

        });

        chatBody.addEventListener("contextmenu", function (event) {

            const messageElement = event.target.closest(".xd-chat-message");

            if (
                !messageElement ||
                event.target.closest("a, button, input, audio, video")
            ) {
                return;
            }

            event.preventDefault();
            openMessageActionMenu(messageElement);

        });

        document.addEventListener("click", function (event) {

            if (
                !chatWindow.contains(event.target) ||
                (
                    !event.target.closest(".xd-chat-message") &&
                    !event.target.closest(".xd-message-action-wrap")
                )
            ) {
                closeMessageActionMenus();
            }

        });

        document.addEventListener("keydown", function (event) {

            if (event.key === "Escape") {
                closeMessageActionMenus();
            }

        });

        let messageLongPressTimer = null;
        let shouldSkipMessageClick = false;

        chatBody.addEventListener("pointerdown", function (event) {

            const messageElement = event.target.closest(".xd-chat-message");

            if (
                !messageElement ||
                event.target.closest("a, button, input, audio, video, .xd-message-action-wrap")
            ) {
                return;
            }

            clearTimeout(messageLongPressTimer);

            messageLongPressTimer = setTimeout(function () {
                shouldSkipMessageClick = true;
                openMessageActionMenu(messageElement);
            }, 550);

        });

        ["pointerup", "pointercancel", "pointerleave", "pointermove"].forEach(function (eventName) {

            chatBody.addEventListener(eventName, function () {
                clearTimeout(messageLongPressTimer);
            });

        });

        lightbox.addEventListener("click", function (event) {

            if (event.target === lightbox) {
                closeImageLightbox();
            }

        });

        lightboxClose.addEventListener("click", closeImageLightbox);

        newMessageButton.addEventListener("click", function () {

            enableNotificationHelper();
            scrollChatToBottom(true);
            clearOpenChatNewMessageBadge();

        });

        chatBody.addEventListener("scroll", function () {

            closeMessageActionMenus();

            if (isChatBodyAtBottom()) {
                clearOpenChatNewMessageBadge();
            }

        });

        chatWindow.addEventListener("play", function (event) {

            if (event.target.matches("audio, video")) {
                pauseOtherChatMedia(event.target);
            }

        }, true);

        chatInput.addEventListener("keydown", function (event) {

            enableNotificationHelper();

            if (event.key === "Enter") {
                sendMessage();
            }

        });

        chatInput.addEventListener("input", function () {

            enableNotificationHelper();

            if (chatInput.value.trim() !== "") {
                sendTypingPresence();
            }

        });


        setInterval(function () {

            if (visitorProfile) {
                loadMessagesFromDatabase();
            }

        }, 2000);

        setInterval(function () {

            if (visitorProfile) {
                updatePresence();
            }

        }, 2000);

    }


    function escapeHTML(value) {

        const div = document.createElement("div");
        div.textContent = value;
        return div.innerHTML;

    }


    function formatMessage(value) {

        return escapeHTML(value).replace(/\n/g, "<br>");

    }

})();
