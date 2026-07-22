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

<div class="xd-live-chat"
     id="xdLiveChat"
     data-mobile-view="list">

    <!-- ==========================================
         02. CHAT SIDEBAR
    ========================================== -->
    <aside class="xd-live-chat-sidebar"
           id="xdChatConversationListPanel"
           aria-label="Conversations">

        <div class="xd-live-chat-sidebar-header">

            <div class="xd-chat-list-heading">

                <h3>Conversations</h3>

                <span class="xd-live-chat-count" id="xdConversationCount">
                    0
                </span>

            </div>

            <button class="xd-chat-filter-toggle"
                    type="button"
                    id="xdChatFilterToggle"
                    aria-label="Filter conversations: Open"
                    aria-controls="xdChatFilterPopover"
                    aria-expanded="false">
                <span id="xdChatFilterToggleLabel">Filter &middot; Open</span>
            </button>

        </div>

        <!-- ==========================================
             03. CHAT FILTERS
        ========================================== -->
        <div class="xd-chat-filter-popover"
             id="xdChatFilterPopover"
             role="dialog"
             aria-modal="false"
             aria-labelledby="xdChatFilterPopoverTitle"
             aria-hidden="false">

            <div class="xd-chat-filter-popover-header">

                <strong id="xdChatFilterPopoverTitle">Filter conversations</strong>

                <button type="button"
                        id="xdChatFilterClose"
                        aria-label="Close conversation filters">
                    &times;
                </button>

            </div>

            <div class="xd-chat-search-box">

                <input type="search"
                       id="xdChatSearch"
                       placeholder="Search visitors, email, website...">

            </div>

            <div class="xd-chat-filter-tabs">

                <button class="xd-chat-filter active"
                        type="button"
                        aria-pressed="true"
                        data-status="open">
                    Open
                </button>

                <button class="xd-chat-filter"
                        type="button"
                        aria-pressed="false"
                        data-status="closed">
                    Closed
                </button>

                <button class="xd-chat-filter"
                        type="button"
                        aria-pressed="false"
                        data-status="unread">
                    Unread
                </button>

            </div>

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
    <section class="xd-live-chat-window"
             id="xdChatConversationPanel"
             aria-label="Active conversation">

        <div class="xd-live-chat-header">

            <button class="xd-chat-mobile-back"
                    type="button"
                    id="xdChatMobileBack"
                    aria-label="Back to conversations">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M15 18l-6-6 6-6"></path>
                </svg>
            </button>

            <div class="xd-live-chat-identity">

                <h3 id="xdChatVisitorName">
                    Select a conversation
                </h3>

                <p id="xdChatVisitorStatus">
                    Visitor details will appear here.
                </p>

            </div>

            <button class="xd-chat-more-actions"
                    type="button"
                    id="xdChatMoreActions"
                    aria-label="More actions"
                    aria-controls="xdChatHeaderActions"
                    aria-expanded="false">
                <span aria-hidden="true">&#8942;</span>
            </button>

            <div class="xd-live-chat-actions"
                 id="xdChatHeaderActions">

                <button class="xd-chat-details-toggle"
                        type="button"
                        id="xdChatDetailsToggle"
                        aria-controls="xdChatVisitorInfo"
                        aria-expanded="false">
                    Details
                </button>

                <button class="xd-chat-close-toggle"
                        type="button"
                        id="xdChatCloseButton"
                        disabled>
                    Close Chat
                </button>

            </div>


            <!-- ==========================================
                 05. VISITOR DETAILS
            ========================================== -->
            <button class="xd-chat-details-backdrop"
                    type="button"
                    id="xdChatDetailsBackdrop"
                    aria-label="Close visitor details"
                    tabindex="-1">
            </button>

            <div class="xd-live-chat-visitor-info"
                 id="xdChatVisitorInfo"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="xdChatVisitorInfoTitle"
                 aria-hidden="true">

                <div class="xd-chat-details-sheet-header">

                    <h4 id="xdChatVisitorInfoTitle">Visitor details</h4>

                    <button type="button"
                            id="xdChatDetailsClose"
                            aria-label="Close visitor details">
                        &times;
                    </button>

                </div>

                <div class="xd-chat-visitor-info-content"
                     id="xdChatVisitorInfoContent">

                    <div class="xd-chat-empty-state">
                        Visitor details will appear after selecting a conversation.
                    </div>

                </div>

            </div>

        </div>


        <div class="xd-live-chat-messages" id="xdChatMessages">

            <div class="xd-chat-empty-state large">

                Select a conversation to start chatting.

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

        <div class="xd-chat-voice-feedback"
             id="xdChatVoiceFeedback"
             role="status"
             aria-live="polite"
             hidden>
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

            <div class="xd-chat-input-shell">

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

            </div>

            <button type="button"
                    id="xdChatRecord"
                    class="xd-chat-record-button"
                    aria-label="Record voice"
                    aria-pressed="false"
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

        <!-- ==========================================
             06. CLOSE CHAT CONFIRMATION
        =========================================== -->
        <div class="xd-chat-close-dialog"
             id="xdChatCloseDialog"
             role="dialog"
             aria-modal="true"
             aria-labelledby="xdChatCloseDialogTitle"
             aria-describedby="xdChatCloseDialogDescription"
             aria-hidden="true">

            <div class="xd-chat-close-dialog-card">

                <h4 id="xdChatCloseDialogTitle">Close this chat?</h4>

                <p id="xdChatCloseDialogDescription">
                    The visitor will no longer be able to continue this conversation.
                </p>

                <div>
                    <button type="button"
                            id="xdChatCloseDialogCancel">
                        Cancel
                    </button>

                    <button type="button"
                            id="xdChatCloseDialogConfirm">
                        Close Chat
                    </button>
                </div>

            </div>

        </div>

    </section>

</div>
