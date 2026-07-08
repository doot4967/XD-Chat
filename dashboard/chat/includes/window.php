<?php
/*
==================================================
Project : XD Chat
Version : 2.0.0
File    : window.php
Module  : Chat Window Layout
Status  : Development
Author  : Umesh + ChatGPT
Created : 06 July 2026
==================================================
*/
?>

<!-- ==========================================
     01. LIVE CHAT WINDOW
========================================== -->

<div class="xd-live-chat">

    <!-- ==========================================
         02. CHAT SIDEBAR
    ========================================== -->
    <aside class="xd-live-chat-sidebar">

        <div class="xd-live-chat-sidebar-header">

            <h3>Conversations</h3>

           <span class="xd-live-chat-count" id="xdConversationCount">
    0
</span>

        </div>

        <!-- ==========================================
             03. CHAT FILTERS
        ========================================== -->
        <div class="xd-chat-filter-tabs">

            <button class="xd-chat-filter active"
                    type="button"
                    data-status="open">
                Open
            </button>

            <button class="xd-chat-filter"
                    type="button"
                    data-status="closed">
                Closed
            </button>

        </div>

        <div class="xd-live-chat-list" id="xdChatList">

            <div class="xd-chat-empty-state">

                No conversations yet.

            </div>

        </div>

    </aside>


    <!-- ==========================================
         04. CHAT CONVERSATION
    ========================================== -->
    <section class="xd-live-chat-window">

        <div class="xd-live-chat-header">

            <div>

                <h3 id="xdChatVisitorName">
                    Select a conversation
                </h3>

                <p id="xdChatVisitorStatus">
                    Visitor details will appear here.
                </p>

            </div>

            <button class="xd-chat-details-toggle"
                    type="button"
                    id="xdChatDetailsToggle">
                Details
            </button>

            <button class="xd-chat-close-toggle"
                    type="button"
                    id="xdChatCloseButton"
                    disabled>
                Close Chat
            </button>


            <!-- ==========================================
                 05. VISITOR DETAILS
            ========================================== -->
            <div class="xd-live-chat-visitor-info"
                 id="xdChatVisitorInfo">

                <div class="xd-chat-empty-state">
                    Visitor details will appear after selecting a conversation.
                </div>

            </div>

        </div>


        <div class="xd-live-chat-messages" id="xdChatMessages">

            <div class="xd-chat-empty-state large">

                Select a visitor from the left side to start chatting.

            </div>

        </div>


        <div class="xd-live-chat-composer">

            <input type="file"
                   id="xdChatFileInput"
                   hidden>

            <div class="xd-chat-attach-wrap">

                <button type="button"
                        id="xdChatAttach"
                        class="xd-chat-attach-button"
                        aria-label="Attach"
                        disabled>

                    +

                </button>

                <div class="xd-chat-attach-menu"
                     id="xdChatAttachMenu">

                    <button type="button"
                            data-accept=".pdf,.doc,.docx,.xls,.xlsx,.txt">
                        Document
                    </button>

                    <button type="button"
                            data-accept=".jpg,.jpeg,.png,.webp,.mp4,.webm,.mov">
                        Photos & Videos
                    </button>

                    <button type="button"
                            data-accept=".mp3,.wav,.ogg">
                        Audio
                    </button>

                </div>

            </div>

            <div class="xd-chat-emoji-wrap">

                <button type="button"
                        id="xdChatEmoji"
                        class="xd-chat-emoji-button"
                        aria-label="Emoji"
                        disabled>
                    😊
                </button>

                <div class="xd-chat-emoji-picker"
                     id="xdChatEmojiPicker">

                    <input type="text"
                           id="xdChatEmojiSearch"
                           placeholder="Search emoji...">

                    <div class="xd-chat-emoji-tabs"
                         id="xdChatEmojiTabs"></div>

                    <div class="xd-chat-emoji-grid"
                         id="xdChatEmojiGrid"></div>

                </div>

            </div>

            <input type="text"
                   id="xdChatInput"
                   placeholder="Type your reply..."
                   disabled>

            <button type="button"
                    id="xdChatRecord"
                    class="xd-chat-record-button"
                    aria-label="Record voice"
                    disabled>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 14c1.7 0 3-1.3 3-3V6c0-1.7-1.3-3-3-3S9 4.3 9 6v5c0 1.7 1.3 3 3 3z"></path>
                    <path d="M17 11c0 2.8-2.2 5-5 5s-5-2.2-5-5H5c0 3.5 2.6 6.4 6 6.9V21h2v-3.1c3.4-.5 6-3.4 6-6.9h-2z"></path>
                </svg>
            </button>

            <button type="button"
                    id="xdChatSend"
                    aria-label="Send message"
                    disabled>

                ➤

            </button>

        </div>

        <div class="xd-chat-record-panel"
             id="xdChatRecordPanel">
            <span class="xd-chat-record-dot"></span>
            <strong id="xdChatRecordTime">00:00</strong>
            <span>Recording...</span>
        </div>

        <div class="xd-chat-record-preview"
             id="xdChatRecordPreview">
            <audio controls></audio>
            <button type="button"
                    id="xdChatRecordCancel">
                Cancel
            </button>
            <button type="button"
                    id="xdChatRecordSend">
                Send
            </button>
        </div>

    </section>

</div>
