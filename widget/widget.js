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

    const baseUrl = currentScript.src.replace("widget.js", "");
    const visitorId = getVisitorId();

    loadCSS(baseUrl + "widget.css");
    loadWidget();


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

        const bubble = document.createElement("button");
        bubble.className = "xd-chat-bubble";
        bubble.innerHTML = "💬";
        bubble.style.background = widgetColor;

        if (widget.position === "bottom-left") {
            bubble.style.left = "24px";
        } else {
            bubble.style.right = "24px";
        }

        const chatWindow = document.createElement("div");
        chatWindow.className = "xd-chat-window";

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

            <div class="xd-chat-footer">

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
        document.body.appendChild(bubble);

        const closeButton = chatWindow.querySelector(".xd-chat-close");
        const chatBody = chatWindow.querySelector(".xd-chat-body");
        const chatInput = chatWindow.querySelector(".xd-chat-input");
        const sendButton = chatWindow.querySelector(".xd-chat-send");


        bubble.addEventListener("click", function () {

            chatWindow.classList.toggle("active");

            if (chatWindow.classList.contains("active")) {
                loadMessagesFromDatabase();
            }

        });

        closeButton.addEventListener("click", function () {
            chatWindow.classList.remove("active");
        });


        function sendMessage() {

            const message = chatInput.value.trim();

            if (message === "") {
                return;
            }

            removeEmptyText();

            addUserMessage(message);

            chatInput.value = "";

            saveMessageToDatabase(message);

            showTyping();

            setTimeout(function () {

                removeTyping();

                const reply = "Thanks! Our team will reply shortly.";

                addAgentMessage(reply);

                saveAgentReply(reply);

            }, 1200);

        }


        function saveMessageToDatabase(message) {

            const formData = new FormData();

            formData.append("action", "send_message");
            formData.append("widget_key", widgetKey);
            formData.append("visitor_id", visitorId);
            formData.append("message", message);

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
                    }

                    loadMessagesFromDatabase();

                })

                .catch(function (error) {
                    console.error("XD Chat API Error :", error);
                });

        }


        function saveAgentReply(message) {

            const formData = new FormData();

            formData.append("action", "agent_reply");
            formData.append("widget_key", widgetKey);
            formData.append("visitor_id", visitorId);
            formData.append("message", message);

            fetch(baseUrl + "widget-api.php", {
                method: "POST",
                body: formData
            })

                .then(function (response) {
                    return response.json();
                })

                .then(function (data) {

                    if (!data.success) {
                        console.error("XD Chat Agent Save Error :", data.message);
                    }

                    loadMessagesFromDatabase();

                })

                .catch(function (error) {
                    console.error("XD Chat Agent API Error :", error);
                });

        }


        function loadMessagesFromDatabase() {

            const formData = new FormData();

            formData.append("action", "load_messages");
            formData.append("widget_key", widgetKey);
            formData.append("visitor_id", visitorId);

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

                    chatBody.querySelectorAll(".xd-chat-database-message").forEach(function (item) {
                        item.remove();
                    });

                    if (data.messages.length > 0) {

                        removeEmptyText();

                        data.messages.forEach(function (item) {

                            if (item.sender === "visitor") {
                                addUserMessage(item.message);
                            } else {
                                addAgentMessage(item.message);
                            }

                        });

                    }

                    scrollChatToBottom();

                })

                .catch(function (error) {
                    console.error("XD Chat Load Messages API Error :", error);
                });

        }


        function removeEmptyText() {

            const emptyText = chatBody.querySelector(".xd-chat-empty");

            if (emptyText) {
                emptyText.remove();
            }

        }


        function addUserMessage(message) {

            const userMessage = document.createElement("div");

            userMessage.className = "xd-chat-message user xd-chat-database-message";

            userMessage.innerHTML = `
                ${escapeHTML(message)}
                <span class="xd-chat-time">
                    Just now
                </span>
            `;

            chatBody.appendChild(userMessage);

            scrollChatToBottom();

        }


        function addAgentMessage(message) {

            const agentMessage = document.createElement("div");

            agentMessage.className = "xd-chat-message xd-chat-database-message";

            agentMessage.innerHTML = `
                ${escapeHTML(message)}
                <span class="xd-chat-time">
                    Just now
                </span>
            `;

            chatBody.appendChild(agentMessage);

            scrollChatToBottom();

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

        chatInput.addEventListener("keydown", function (event) {

            if (event.key === "Enter") {
                sendMessage();
            }

        });


        setInterval(function () {

            if (chatWindow.classList.contains("active")) {
                loadMessagesFromDatabase();
            }

        }, 2000);

    }


    function escapeHTML(value) {

        const div = document.createElement("div");
        div.textContent = value;
        return div.innerHTML;

    }

})();