<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!is_logged_in() || !is_user()) {
    header('Location: ../login.php');
    exit();
}

$me = (int)$_SESSION['user_id'];

// ── POST: Send a message ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    verify_csrf_token();

    $to_id  = (int)($_POST['to_id']  ?? 0);
    $pet_id = (int)($_POST['pet_id'] ?? 0);
    $body   = trim($_POST['message'] ?? '');

    if ($to_id > 0 && $pet_id > 0 && $body !== '' && $to_id !== $me) {
        // Verify pet exists
        $chk = $pdo->prepare("SELECT id FROM pets WHERE id = ?");
        $chk->execute([$pet_id]);
        if ($chk->fetch()) {
            $ins = $pdo->prepare(
                "INSERT INTO messages (sender_id, receiver_id, pet_id, message)
                 VALUES (?, ?, ?, ?)"
            );
            $ins->execute([$me, $to_id, $pet_id, $body]);
        }
    }
    header("Location: messages.php?to={$to_id}&pet_id={$pet_id}");
    exit();
}

// ── Query params ─────────────────────────────────────────────────────────────
$active_to     = isset($_GET['to'])     && ctype_digit($_GET['to'])     ? (int)$_GET['to']     : 0;
$active_pet_id = isset($_GET['pet_id']) && ctype_digit($_GET['pet_id']) ? (int)$_GET['pet_id'] : 0;

// If only ?to= is given (from pet_detail "Message Owner"), auto-pick the pet
if ($active_to > 0 && $active_pet_id === 0) {
    // find any existing thread between me & that user
    $thr = $pdo->prepare("
        SELECT pet_id FROM messages
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at DESC LIMIT 1
    ");
    $thr->execute([$me, $active_to, $active_to, $me]);
    $row = $thr->fetch();
    if ($row) {
        $active_pet_id = (int)$row['pet_id'];
    }
}

// ── Fetch all conversation threads for sidebar ───────────────────────────────
// A thread = unique (other_user_id, pet_id) pair
$conv_stmt = $pdo->prepare("
    SELECT
        CASE WHEN m.sender_id = :me THEN m.receiver_id ELSE m.sender_id END AS other_id,
        m.pet_id,
        p.name  AS pet_name,
        u.full_name AS other_name,
        MAX(m.created_at) AS last_at,
        SUM(CASE WHEN m.receiver_id = :me2 AND m.is_read = 0 THEN 1 ELSE 0 END) AS unread
    FROM messages m
    JOIN pets  p ON p.id = m.pet_id
    JOIN users u ON u.id = (CASE WHEN m.sender_id = :me3 THEN m.receiver_id ELSE m.sender_id END)
    WHERE m.sender_id = :me4 OR m.receiver_id = :me5
    GROUP BY other_id, m.pet_id
    ORDER BY last_at DESC
");
$conv_stmt->execute([
    ':me'  => $me, ':me2' => $me, ':me3' => $me,
    ':me4' => $me, ':me5' => $me
]);
$conversations = $conv_stmt->fetchAll();

// ── Active thread ─────────────────────────────────────────────────────────────
$thread_messages = [];
$other_user      = null;
$active_pet      = null;

if ($active_to > 0 && $active_pet_id > 0) {
    // Security: user must be part of this thread OR it's a new conversation
    // If thread has messages, verify participation
    $sec = $pdo->prepare("
        SELECT COUNT(*) FROM messages
        WHERE pet_id = ?
          AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
    ");
    $sec->execute([$active_pet_id, $me, $active_to, $active_to, $me]);
    $has_msgs = (int)$sec->fetchColumn();

    // Allow if thread is new (0 messages yet) or user is participant
    $allowed = ($has_msgs > 0 || $active_to !== $me);

    if ($allowed) {
        // Fetch messages
        $msg_stmt = $pdo->prepare("
            SELECT m.*, u.full_name AS sender_name
            FROM messages m
            JOIN users u ON u.id = m.sender_id
            WHERE m.pet_id = ?
              AND ((m.sender_id = ? AND m.receiver_id = ?)
                OR (m.sender_id = ? AND m.receiver_id = ?))
            ORDER BY m.created_at ASC
        ");
        $msg_stmt->execute([$active_pet_id, $me, $active_to, $active_to, $me]);
        $thread_messages = $msg_stmt->fetchAll();

        // Mark as read
        $read_stmt = $pdo->prepare("
            UPDATE messages SET is_read = 1
            WHERE pet_id = ? AND sender_id = ? AND receiver_id = ? AND is_read = 0
        ");
        $read_stmt->execute([$active_pet_id, $active_to, $me]);

        // Fetch other user info
        $ou = $pdo->prepare("SELECT id, full_name, city FROM users WHERE id = ?");
        $ou->execute([$active_to]);
        $other_user = $ou->fetch();

        // Fetch pet info
        $ap = $pdo->prepare("SELECT id, name, breed FROM pets WHERE id = ?");
        $ap->execute([$active_pet_id]);
        $active_pet = $ap->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages — Pet Adoption</title>
    <meta name="description" content="Chat with pet owners on Pet Adoption.">
    <link rel="stylesheet" href="../css/marketplace.css">
    <style>
        /* ── Messenger Layout ── */
        .msg-shell {
            display: grid;
            grid-template-columns: 340px 1fr;
            height: calc(100vh - var(--nav-height));
            overflow: hidden;
        }

        /* ── Sidebar ── */
        .msg-sidebar {
            background: var(--bg-secondary);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .msg-sidebar-header {
            padding: 20px 20px 16px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }

        .msg-sidebar-header h2 {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.3px;
        }

        .msg-sidebar-header p {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .msg-conv-list {
            flex: 1;
            overflow-y: auto;
            padding: 8px 0;
        }

        .msg-conv-list::-webkit-scrollbar { width: 4px; }
        .msg-conv-list::-webkit-scrollbar-thumb { background: var(--bg-tertiary); border-radius: 4px; }

        .msg-conv-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            cursor: pointer;
            text-decoration: none;
            transition: background var(--transition);
            border-radius: 0;
            position: relative;
        }

        .msg-conv-item:hover { background: var(--bg-hover); }

        .msg-conv-item.active {
            background: rgba(45, 136, 255, 0.12);
        }

        .msg-conv-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--accent);
            border-radius: 0 3px 3px 0;
        }

        .msg-avatar {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            color: #fff;
            flex-shrink: 0;
        }

        .msg-conv-info { flex: 1; min-width: 0; }

        .msg-conv-name {
            font-size: 0.92rem;
            font-weight: 600;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .msg-conv-pet {
            font-size: 0.78rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 2px;
        }

        .msg-conv-time {
            font-size: 0.72rem;
            color: var(--text-muted);
            flex-shrink: 0;
            align-self: flex-start;
            margin-top: 2px;
        }

        .msg-unread-badge {
            width: 20px;
            height: 20px;
            background: var(--accent);
            border-radius: 50%;
            font-size: 0.7rem;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .msg-sidebar-empty {
            padding: 40px 20px;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.88rem;
        }

        .msg-sidebar-empty span { font-size: 2rem; display: block; margin-bottom: 10px; }

        /* ── Chat Area ── */
        .msg-chat {
            display: flex;
            flex-direction: column;
            background: var(--bg-primary);
            overflow: hidden;
        }

        /* Chat header */
        .msg-chat-header {
            padding: 16px 24px;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 14px;
            flex-shrink: 0;
        }

        .msg-chat-header .msg-avatar { width: 40px; height: 40px; font-size: 0.9rem; }

        .msg-chat-title { flex: 1; }
        .msg-chat-title strong {
            display: block;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .msg-chat-title span {
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        /* Messages scroll area */
        .msg-thread {
            flex: 1;
            overflow-y: auto;
            padding: 24px 24px 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .msg-thread::-webkit-scrollbar { width: 6px; }
        .msg-thread::-webkit-scrollbar-thumb { background: var(--bg-tertiary); border-radius: 4px; }

        /* Bubble groups */
        .msg-bubble-wrap {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            animation: msg-pop 0.2s ease;
        }

        @keyframes msg-pop {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .msg-bubble-wrap.mine { flex-direction: row-reverse; }

        .msg-bubble-wrap .msg-avatar { width: 30px; height: 30px; font-size: 0.7rem; flex-shrink: 0; }

        .msg-bubble {
            max-width: 68%;
            padding: 10px 14px;
            border-radius: 18px;
            font-size: 0.92rem;
            line-height: 1.55;
            word-break: break-word;
        }

        .msg-bubble-wrap.theirs .msg-bubble {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border-bottom-left-radius: 4px;
        }

        .msg-bubble-wrap.mine .msg-bubble {
            background: var(--accent);
            color: #fff;
            border-bottom-right-radius: 4px;
        }

        .msg-bubble-time {
            font-size: 0.68rem;
            color: var(--text-muted);
            margin-top: 2px;
            text-align: center;
        }

        .msg-date-divider {
            text-align: center;
            font-size: 0.74rem;
            color: var(--text-muted);
            margin: 12px 0 4px;
            position: relative;
        }

        .msg-date-divider::before,
        .msg-date-divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 30%;
            height: 1px;
            background: var(--border);
        }
        .msg-date-divider::before { left: 0; }
        .msg-date-divider::after  { right: 0; }

        /* Empty / placeholder */
        .msg-empty-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            color: var(--text-muted);
            text-align: center;
            padding: 40px;
        }

        .msg-empty-state span { font-size: 3.5rem; }
        .msg-empty-state h3 { font-size: 1.1rem; font-weight: 700; color: var(--text-secondary); }
        .msg-empty-state p  { font-size: 0.88rem; max-width: 280px; }

        /* Input area */
        .msg-input-bar {
            padding: 14px 20px;
            background: var(--bg-secondary);
            border-top: 1px solid var(--border);
            flex-shrink: 0;
        }

        .msg-input-form {
            display: flex;
            align-items: flex-end;
            gap: 10px;
        }

        .msg-input-wrap {
            flex: 1;
            background: var(--bg-tertiary);
            border-radius: 22px;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 4px 8px 4px 16px;
            transition: border-color var(--transition);
        }

        .msg-input-wrap:focus-within {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        .msg-input-wrap textarea {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: var(--text-primary);
            font-family: var(--font);
            font-size: 0.92rem;
            resize: none;
            max-height: 120px;
            min-height: 36px;
            line-height: 1.5;
            padding: 6px 0;
        }

        .msg-input-wrap textarea::placeholder { color: var(--text-muted); }

        .msg-send-btn {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: none;
            background: var(--accent);
            color: #fff;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all var(--transition);
            flex-shrink: 0;
        }

        .msg-send-btn:hover {
            background: var(--accent-hover);
            transform: scale(1.08);
            box-shadow: var(--shadow-glow);
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .msg-shell {
                grid-template-columns: 1fr;
                position: relative;
            }

            .msg-sidebar {
                position: absolute;
                inset: 0;
                z-index: 10;
                transform: translateX(0);
                transition: transform var(--transition);
            }

            .msg-sidebar.hidden {
                transform: translateX(-100%);
            }

            .msg-chat {
                height: 100%;
            }

            .msg-back-btn {
                display: inline-flex !important;
            }
        }

        .msg-back-btn {
            display: none;
            align-items: center;
            gap: 6px;
            background: var(--bg-tertiary);
            border: none;
            color: var(--text-primary);
            font-family: var(--font);
            font-size: 0.88rem;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: background var(--transition);
        }

        .msg-back-btn:hover { background: var(--bg-hover); }
    </style>
</head>
<body class="marketplace">

<!-- Navigation -->
<nav class="mp-nav">
    <div class="mp-nav-inner">
        <a href="dashboard.php" class="mp-logo">🐾 Pet<span>Adoption</span></a>
        <button class="mp-hamburger" aria-label="Toggle menu">
            <span></span><span></span><span></span>
        </button>
        <ul class="mp-nav-links">
            <li><a href="dashboard.php">Browse Dogs</a></li>
            <li><a href="my_listings.php">My Listings</a></li>
            <li><a href="messages.php" class="active">Messages</a></li>
            <li><a href="post_listing.php" class="mp-nav-cta">+ Post a Dog</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="../logout.php" class="mp-nav-logout">Logout</a></li>
        </ul>
    </div>
</nav>

<div class="msg-shell">

    <!-- ══ SIDEBAR ══ -->
    <aside class="msg-sidebar" id="msgSidebar">
        <div class="msg-sidebar-header">
            <h2>💬 Messages</h2>
            <p><?php echo htmlspecialchars($_SESSION['username']); ?></p>
        </div>

        <div class="msg-conv-list">
            <?php if (empty($conversations)): ?>
                <div class="msg-sidebar-empty">
                    <span>🐾</span>
                    No conversations yet.<br>
                    Start one from a <a href="dashboard.php">pet listing</a>.
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conv):
                    $initials = '';
                    foreach (explode(' ', $conv['other_name'] ?? 'U') as $p) {
                        $initials .= mb_strtoupper(mb_substr($p, 0, 1));
                    }
                    $initials  = mb_substr($initials, 0, 2);
                    $isActive  = ($conv['other_id'] == $active_to && $conv['pet_id'] == $active_pet_id);
                    $timeLabel = date('M j', strtotime($conv['last_at']));
                ?>
                <a href="messages.php?to=<?php echo (int)$conv['other_id']; ?>&amp;pet_id=<?php echo (int)$conv['pet_id']; ?>"
                   class="msg-conv-item<?php echo $isActive ? ' active' : ''; ?>"
                   id="conv-<?php echo (int)$conv['other_id']; ?>-<?php echo (int)$conv['pet_id']; ?>">
                    <div class="msg-avatar"><?php echo htmlspecialchars($initials); ?></div>
                    <div class="msg-conv-info">
                        <div class="msg-conv-name"><?php echo htmlspecialchars($conv['other_name']); ?></div>
                        <div class="msg-conv-pet">🐶 <?php echo htmlspecialchars($conv['pet_name']); ?></div>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
                        <div class="msg-conv-time"><?php echo $timeLabel; ?></div>
                        <?php if ($conv['unread'] > 0): ?>
                            <div class="msg-unread-badge"><?php echo (int)$conv['unread']; ?></div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>

    <!-- ══ CHAT AREA ══ -->
    <section class="msg-chat" id="msgChat">

        <?php if ($other_user && $active_pet): ?>

            <!-- Chat header -->
            <div class="msg-chat-header">
                <button class="msg-back-btn" id="msgBackBtn" aria-label="Back to conversations">← Back</button>
                <?php
                    $hi = '';
                    foreach (explode(' ', $other_user['full_name'] ?? 'U') as $p) $hi .= mb_strtoupper(mb_substr($p,0,1));
                    $hi = mb_substr($hi, 0, 2);
                ?>
                <div class="msg-avatar"><?php echo htmlspecialchars($hi); ?></div>
                <div class="msg-chat-title">
                    <strong><?php echo htmlspecialchars($other_user['full_name']); ?></strong>
                    <span>🐶 <?php echo htmlspecialchars($active_pet['name']); ?> &middot; <?php echo htmlspecialchars($active_pet['breed']); ?></span>
                </div>
                <a href="pet_detail.php?id=<?php echo (int)$active_pet['id']; ?>" class="mp-btn mp-btn-secondary" style="padding:8px 14px;font-size:0.82rem;flex:none;">View Pet</a>
            </div>

            <!-- Thread -->
            <div class="msg-thread" id="msgThread">
                <?php if (empty($thread_messages)): ?>
                    <div class="msg-empty-state">
                        <span>💬</span>
                        <h3>Start the conversation</h3>
                        <p>Say hello to <?php echo htmlspecialchars($other_user['full_name']); ?> about <?php echo htmlspecialchars($active_pet['name']); ?>!</p>
                    </div>
                <?php else:
                    $prev_date = '';
                    foreach ($thread_messages as $msg):
                        $msg_date = date('Y-m-d', strtotime($msg['created_at']));
                        if ($msg_date !== $prev_date):
                            $prev_date = $msg_date;
                            $label = (date('Y-m-d') === $msg_date) ? 'Today' : date('M j, Y', strtotime($msg['created_at']));
                ?>
                    <div class="msg-date-divider"><?php echo htmlspecialchars($label); ?></div>
                <?php      endif;
                        $isMine = ((int)$msg['sender_id'] === $me);
                        $side   = $isMine ? 'mine' : 'theirs';
                        $bInitials = '';
                        foreach (explode(' ', $msg['sender_name'] ?? 'U') as $p) $bInitials .= mb_strtoupper(mb_substr($p,0,1));
                        $bInitials = mb_substr($bInitials, 0, 2);
                ?>
                    <div class="msg-bubble-wrap <?php echo $side; ?>">
                        <?php if (!$isMine): ?>
                            <div class="msg-avatar"><?php echo htmlspecialchars($bInitials); ?></div>
                        <?php endif; ?>
                        <div>
                            <div class="msg-bubble"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                            <div class="msg-bubble-time"><?php echo date('g:i a', strtotime($msg['created_at'])); ?></div>
                        </div>
                    </div>
                <?php   endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Input bar -->
            <div class="msg-input-bar">
                <form class="msg-input-form" method="POST" action="messages.php" id="msgForm">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="send_message" value="1">
                    <input type="hidden" name="to_id"  value="<?php echo (int)$active_to; ?>">
                    <input type="hidden" name="pet_id" value="<?php echo (int)$active_pet_id; ?>">
                    <div class="msg-input-wrap">
                        <textarea name="message"
                                  id="msgTextarea"
                                  placeholder="Type a message…"
                                  rows="1"
                                  required
                                  autocomplete="off"></textarea>
                    </div>
                    <button type="submit" class="msg-send-btn" aria-label="Send message">➤</button>
                </form>
            </div>

        <?php else: ?>

            <!-- No thread selected -->
            <div class="msg-empty-state">
                <span>💬</span>
                <h3>Your Messages</h3>
                <p>Select a conversation from the left, or start one from a <a href="dashboard.php" style="color:var(--accent)">pet listing</a>.</p>
            </div>

        <?php endif; ?>
    </section>

</div><!-- /.msg-shell -->

<script src="../js/marketplace.js"></script>
<script>
(function () {
    // Auto-scroll thread to bottom
    const thread = document.getElementById('msgThread');
    if (thread) thread.scrollTop = thread.scrollHeight;

    // Auto-grow textarea
    const ta = document.getElementById('msgTextarea');
    if (ta) {
        ta.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });

        // Send on Enter (Shift+Enter = newline)
        ta.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (this.value.trim()) {
                    document.getElementById('msgForm').submit();
                }
            }
        });
    }

    // Mobile: back button shows sidebar
    const backBtn  = document.getElementById('msgBackBtn');
    const sidebar  = document.getElementById('msgSidebar');
    if (backBtn && sidebar) {
        // On mobile, hide sidebar when a thread is open
        if (window.innerWidth <= 768) {
            sidebar.classList.add('hidden');
        }
        backBtn.addEventListener('click', function () {
            sidebar.classList.remove('hidden');
        });
    }
})();
</script>
</body>
</html>
