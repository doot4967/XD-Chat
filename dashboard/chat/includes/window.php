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

        <div class="xd-live-chat-list" id="xdChatList">

            <div class="xd-chat-empty-state">

                No conversations yet.

            </div>

        </div>

    </aside>


    <!-- ==========================================
         03. CHAT CONVERSATION
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

        </div>


        <div class="xd-live-chat-messages" id="xdChatMessages">

            <div class="xd-chat-empty-state large">

                Select a visitor from the left side to start chatting.

            </div>

        </div>


        <div class="xd-live-chat-composer">

            <input type="text"
                   id="xdChatInput"
                   placeholder="Type your reply..."
                   disabled>

            <button type="button"
                    id="xdChatSend"
                    disabled>

                Send

            </button>

        </div>

    </section>

</div>