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

require_once __DIR__ . '/../../../includes/functions/platform-settings.php';

$xd_chat_upload_config = getPlatformUploadRuntimeConfig($pdo);
$xd_chat_message_max_length = getPlatformMessageMaxLength($pdo);

$xd_chat_format_accept = function (array $extensions): string {

    $values = [];

    foreach ($extensions as $extension) {

        $extension = strtolower(trim((string) $extension));
        $extension = ltrim($extension, ".");

        if ($extension !== "") {
            $values[] = "." . $extension;
        }

    }

    return implode(",", array_values(array_unique($values)));

};

$xd_chat_document_accept = $xd_chat_format_accept($xd_chat_upload_config["documents"]["extensions"] ?? []);
$xd_chat_media_accept = $xd_chat_format_accept(array_merge(
    $xd_chat_upload_config["images"]["extensions"] ?? [],
    $xd_chat_upload_config["videos"]["extensions"] ?? []
));
$xd_chat_audio_accept = $xd_chat_format_accept($xd_chat_upload_config["audio"]["extensions"] ?? []);
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
        <div class="xd-chat-search-box">

            <input type="search"
                   id="xdChatSearch"
                   placeholder="Search visitors, email, website...">

        </div>

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

            <button class="xd-chat-filter"
                    type="button"
                    data-status="unread">
                Unread
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


        <div class="xd-chat-reply-preview"
             id="xdChatReplyPreview">

            <div>
                <strong id="xdChatReplySender"></strong>
                <span id="xdChatReplyText"></span>
            </div>

            <button type="button"
                    id="xdChatReplyCancel"
                    aria-label="Cancel reply">
                &times;
            </button>

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
                            data-accept="<?php echo htmlspecialchars($xd_chat_document_accept); ?>">
                        Document
                    </button>

                    <button type="button"
                            data-accept="<?php echo htmlspecialchars($xd_chat_media_accept); ?>">
                        Photos & Videos
                    </button>

                    <button type="button"
                            data-accept="<?php echo htmlspecialchars($xd_chat_audio_accept); ?>">
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
                   maxlength="<?php echo (int) $xd_chat_message_max_length; ?>"
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
