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

        const welcomeMessage = formatMessage(
            widget.welcome_message || "Hi there!\nHow can we help you today?"
        );

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
                       accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx"
                       hidden>

                <button class="xd-chat-attach"
                        type="button"
                        title="Attach file">
                    +
                </button>

                <input type="text"
                       class="xd-chat-input"
                       placeholder="Type your message...">

                <button class="xd-chat-send"
                        type="button"
                        style="background:${widgetColor}">
                    ➤
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
        const preChatForm = chatWindow.querySelector(".xd-chat-preform");
        const nameInput = chatWindow.querySelector(".xd-chat-name");
        const emailInput = chatWindow.querySelector(".xd-chat-email");
        const formError = chatWindow.querySelector(".xd-chat-form-error");
        const chatFooter = chatWindow.querySelector(".xd-chat-footer");
        const fileInput = chatWindow.querySelector(".xd-chat-file-input");
        const attachButton = chatWindow.querySelector(".xd-chat-attach");
        const chatInput = chatWindow.querySelector(".xd-chat-input");
        const sendButton = chatWindow.querySelector(".xd-chat-send");
        const statusText = chatWindow.querySelector(".xd-chat-status");
        const welcomeBubble = chatWindow.querySelector(".xd-welcome-message");
        const lightboxImage = lightbox.querySelector("img");
        const lightboxClose = lightbox.querySelector(".xd-chat-lightbox-close");
        let lastTypingPingTime = 0;
        const typingThrottleDelay = 1500;
        let isMessageNotificationReady = false;
        let unreadAdminMessageCount = 0;

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
                    loadMessagesFromDatabase();

                })

                .catch(function (error) {
                    console.error("XD Chat File API Error :", error);
                });

        }


        function loadMessagesFromDatabase() {

            const formData = new FormData();

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

                    if (data.messages.length > 0) {

                        removeEmptyText();

                        let hasNewAdminMessage = false;
                        let latestAdminMessage = "";

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
                                }
                            }

                        });

                        if (hasNewAdminMessage) {
                            playNotificationSound();

                            if (!chatWindow.classList.contains("active")) {
                                showUnreadNotification(latestAdminMessage);
                            }
                        }

                    }

                    if (data.last_message_id) {
                        lastMessageId = Math.max(lastMessageId, parseInt(data.last_message_id, 10));
                    }

                    isMessageNotificationReady = true;

                    scrollChatToBottom();

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

            const typing = document.createElement("div");

            typing.className = "xd-chat-message xd-chat-typing xd-admin-typing";

            typing.innerHTML = "Admin is typing...";

            chatBody.appendChild(typing);

            scrollChatToBottom();

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

            if (item.message_type === "image") {
                addFileMessage(item, messageId, isVisitorMessage, true);
                return;
            }

            if (item.message_type === "file") {
                addFileMessage(item, messageId, isVisitorMessage, false);
                return;
            }

            if (isVisitorMessage) {
                addUserMessage(item.message, messageId);
                return;
            }

            addAgentMessage(item.message, messageId);

        }


        function addUserMessage(message, messageId) {

            const userMessage = document.createElement("div");

            userMessage.className = "xd-chat-message user";
            userMessage.setAttribute("data-message", message);

            if (messageId) {
                userMessage.classList.add("xd-chat-database-message");
                userMessage.setAttribute("data-message-id", messageId);
            } else {
                userMessage.classList.add("xd-chat-local-message");
            }

            userMessage.innerHTML = `
                ${escapeHTML(message)}
                <span class="xd-chat-time">
                    Just now
                </span>
            `;

            chatBody.appendChild(userMessage);

            scrollChatToBottom();

            return userMessage;

        }


        function addFileMessage(item, messageId, isVisitorMessage, isImage) {

            const fileMessage = document.createElement("div");
            const downloadUrl = getFileDownloadUrl(messageId);

            fileMessage.className = isVisitorMessage
                ? "xd-chat-message user xd-chat-file-message xd-chat-database-message"
                : "xd-chat-message xd-chat-file-message xd-chat-database-message";

            fileMessage.setAttribute("data-message", item.message || item.file_name || "");
            fileMessage.setAttribute("data-message-id", messageId);

            if (isImage) {

                fileMessage.innerHTML = `
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
                    <div class="xd-chat-file-card">
                        <div class="xd-chat-file-icon">
                            FILE
                        </div>
                        <div class="xd-chat-file-meta">
                            <strong>${escapeHTML(item.file_name || "Attachment")}</strong>
                            <small>${escapeHTML(formatFileSize(parseInt(item.file_size || 0, 10)))}</small>
                        </div>
                        <a href="${escapeHTML(downloadUrl)}">
                            Download
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


        function getFileDownloadUrl(messageId) {

            return baseUrl
                + "download-file.php?message_id=" + encodeURIComponent(messageId)
                + "&widget_key=" + encodeURIComponent(widgetKey)
                + "&visitor_id=" + encodeURIComponent(visitorId);

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


        function addAgentMessage(message, messageId) {

            const agentMessage = document.createElement("div");

            agentMessage.className = "xd-chat-message xd-chat-database-message";
            agentMessage.setAttribute("data-message", message);

            if (messageId) {
                agentMessage.setAttribute("data-message-id", messageId);
            }

            agentMessage.innerHTML = `
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

            scrollChatToBottom();

        }


        function removeTyping() {

            const typing = chatBody.querySelector(".xd-chat-typing");

            if (typing) {
                typing.remove();
            }

        }


        function scrollChatToBottom() {

            chatBody.scrollTop = chatBody.scrollHeight;

        }


        sendButton.addEventListener("click", sendMessage);

        attachButton.addEventListener("click", function () {

            enableNotificationHelper();

            fileInput.click();

        });

        fileInput.addEventListener("change", function () {

            enableNotificationHelper();

            if (fileInput.files.length === 0) {
                return;
            }

            sendFileToDatabase(fileInput.files[0]);

            fileInput.value = "";

        });

        lightbox.addEventListener("click", function (event) {

            if (event.target === lightbox) {
                closeImageLightbox();
            }

        });

        lightboxClose.addEventListener("click", closeImageLightbox);

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
