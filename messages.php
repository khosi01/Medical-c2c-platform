<?php
session_start();
require_once 'config/db.php';
require_once 'includes/user-auth-check.php';

$base   = '/medical-c2c-platform';
$userId = $_SESSION['user_id'];

$activeParter  = isset($_GET['with']) ? (int)$_GET['with'] : null;
$activeProduct = isset($_GET['prod'])  ? (int)$_GET['prod']  : null;

if ($activeParter && $activeProduct && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $exists = $pdo->prepare("
        SELECT id FROM messages 
        WHERE product_context_id = ?
        AND (
            (sender_id = ? AND receiver_id = ?)
            OR 
            (sender_id = ? AND receiver_id = ?)
        )
        LIMIT 1
    ");
    $exists->execute([$activeProduct, $userId, $activeParter, $activeParter, $userId]);

    if (!$exists->fetch()) {
        $prodStmt = $pdo->prepare("SELECT title FROM products WHERE id = ?");
        $prodStmt->execute([$activeProduct]);
        $prodTitle = $prodStmt->fetchColumn();

        $pdo->prepare("
            INSERT INTO messages 
                (sender_id, receiver_id, product_context_id, message_text, is_read, sent_at)
            VALUES (?, ?, ?, ?, 0, NOW())
        ")->execute([
            $userId,
            $activeParter,
            $activeProduct,
            "Hi! I'm interested in your listing: \"$prodTitle\". Is it still available?"
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message_text'])) {
    $text     = trim($_POST['message_text']);
    $receiver = (int)$_POST['receiver_id'];
    $prodCtx  = !empty($_POST['product_context_id']) ? (int)$_POST['product_context_id'] : null;
    $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, product_context_id, message_text, is_read, sent_at)
                   VALUES (?, ?, ?, ?, 0, NOW())")
        ->execute([$userId, $receiver, $prodCtx, $text]);
    header("Location: messages.php?with=$receiver" . ($prodCtx ? "&prod=$prodCtx" : ""));
    exit();
}

if ($activeParter) {
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0")
        ->execute([$activeParter, $userId]);
}

$convStmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.profile_pic, u.profession,
           m.message_text AS last_message, m.sent_at AS last_sent,
           m.product_context_id, p.title AS product_title,
           SUM(CASE WHEN m2.is_read = 0 AND m2.receiver_id = :me THEN 1 ELSE 0 END) AS unread_count
    FROM (
        SELECT CASE WHEN sender_id = :me2 THEN receiver_id ELSE sender_id END AS partner_id, MAX(id) AS last_id
        FROM messages WHERE sender_id = :me3 OR receiver_id = :me4 GROUP BY partner_id
    ) AS latest
    JOIN messages m  ON m.id = latest.last_id
    JOIN users u     ON u.id = latest.partner_id
    LEFT JOIN products p  ON p.id = m.product_context_id
    LEFT JOIN messages m2 ON (m2.sender_id = latest.partner_id AND m2.receiver_id = :me5)
    GROUP BY u.id, u.full_name, u.profile_pic, u.profession, m.message_text, m.sent_at, m.product_context_id, p.title
    ORDER BY m.sent_at DESC
");
$convStmt->execute([':me' => $userId, ':me2' => $userId, ':me3' => $userId, ':me4' => $userId, ':me5' => $userId]);
$conversations = $convStmt->fetchAll(PDO::FETCH_ASSOC);

if (!$activeParter && !empty($conversations)) {
    $activeParter  = $conversations[0]['id'];
    $activeProduct = $conversations[0]['product_context_id'];
}

$partner = $productCtx = null;
if ($activeParter) {
    $ps = $pdo->prepare("SELECT id, full_name, profile_pic, profession FROM users WHERE id = ?");
    $ps->execute([$activeParter]);
    $partner = $ps->fetch(PDO::FETCH_ASSOC);
    if ($activeProduct) {
        $pp = $pdo->prepare("SELECT id, title FROM products WHERE id = ?");
        $pp->execute([$activeProduct]);
        $productCtx = $pp->fetch(PDO::FETCH_ASSOC);
    }
}

$me = $pdo->prepare("SELECT full_name, profile_pic FROM users WHERE id = ?");
$me->execute([$userId]);
$currentUser = $me->fetch(PDO::FETCH_ASSOC);
$messages = [];

if ($activeParter) {
    $ms = $pdo->prepare("
        SELECT m.*, u.full_name, u.profile_pic FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.sent_at ASC
    ");
    $ms->execute([$userId, $activeParter, $activeParter, $userId]);
    $messages = $ms->fetchAll(PDO::FETCH_ASSOC);
}

function timeAgo($dt)
{
    $d = time() - strtotime($dt);
    if ($d < 60)    return 'now';
    if ($d < 3600)  return floor($d / 60) . 'm';
    if ($d < 86400) return floor($d / 3600) . 'h';
    return date('D', strtotime($dt));
}
function avatar($name, $pic, $base, $size = 38)
{
    $i = strtoupper(substr($name, 0, 1));
    if (!empty($pic))
        return "<img src='$base/uploads/profiles/" . htmlspecialchars($pic) . "' style='width:{$size}px;height:{$size}px;border-radius:50%;object-fit:cover;flex-shrink:0;' alt=''>";
    $fs = round($size * 0.38);
    return "<div style='width:{$size}px;height:{$size}px;border-radius:50%;background:#036873;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:{$fs}px;flex-shrink:0;'>$i</div>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | MedMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>../assets/css/style.css">
    <style>
        :root {
            --brand: #036873;
            --brand-mid: #04a0af;
            --lite: #e1eff2;
            --chat-bg: #f5fbfc;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            height: 100dvh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: #fdf5f8;
        }

        /* ─── NAVBAR ─────────────────────────────────────────── */
        .msg-nav {
            background: var(--brand);
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
            z-index: 10;
        }

        .back-btn {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .15);
            border: none;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
            cursor: pointer;
            text-decoration: none;
            transition: background .15s;
            flex-shrink: 0;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, .28);
            color: #fff;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .nav-logo {
            width: 30px;
            height: 30px;
            background: rgba(255, 255, 255, .2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 15px;
        }

        .nav-name {
            font-weight: 800;
            font-size: .98rem;
            color: #fff;
        }

        .secure-pill {
            background: rgba(255, 255, 255, .18);
            border: 1px solid rgba(255, 255, 255, .3);
            color: #fff;
            font-size: .66rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 50px;
            letter-spacing: .4px;
        }

        .nav-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notif-btn {
            width: 32px;
            height: 32px;
            background: rgba(255, 255, 255, .12);
            border: none;
            border-radius: 50%;
            color: #fff;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            text-decoration: none;
        }

        .nav-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, .4);
        }

        .nav-avatar-init {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .25);
            color: #fff;
            font-weight: 700;
            font-size: .85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255, 255, 255, .35);
            flex-shrink: 0;
        }

        /* ─── SHELL ──────────────────────────────────────────── */
        .msg-shell {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        /* ─── SIDEBAR ────────────────────────────────────────── */
        .msg-sidebar {
            width: 260px;
            background: #fff;
            border-right: 1px solid #edf4f5;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            transition: transform .25s ease;
        }

        .sidebar-head {
            padding: 14px 14px 10px;
        }

        .sidebar-head h6 {
            font-weight: 700;
            color: #1a1a1a;
            font-size: .95rem;
            margin-bottom: 10px;
        }

        .search-wrap {
            position: relative;
        }

        .search-wrap i {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: #bbb;
            font-size: 13px;
        }

        .search-wrap input {
            width: 100%;
            background: #f5f5f5;
            border: 1px solid #eee;
            border-radius: 50px;
            padding: 7px 12px 7px 32px;
            font-family: 'Poppins', sans-serif;
            font-size: .78rem;
            color: #555;
            outline: none;
        }

        .search-wrap input:focus {
            border-color: var(--brand-mid);
        }

        .sec-label {
            font-size: .64rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #bbb;
            padding: 12px 14px 5px;
        }

        .conv-list {
            overflow-y: auto;
        }

        .conv-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: background .15s;
        }

        .conv-item:hover {
            background: #f5fbfc;
        }

        .conv-item.active {
            background: #eaf7f9;
            border-left-color: var(--brand);
        }

        .c-avatar {
            position: relative;
            flex-shrink: 0;
        }

        .online-dot {
            position: absolute;
            bottom: 1px;
            right: 1px;
            width: 9px;
            height: 9px;
            background: #2ecc71;
            border-radius: 50%;
            border: 2px solid #fff;
        }

        .c-body {
            flex: 1;
            min-width: 0;
        }

        .c-name {
            font-weight: 700;
            font-size: .82rem;
            color: #1a1a1a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .c-product {
            font-size: .7rem;
            color: #aaa;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .c-preview {
            font-size: .72rem;
            color: #888;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 1px;
        }

        .c-meta {
            text-align: right;
            flex-shrink: 0;
        }

        .c-time {
            font-size: .64rem;
            color: #bbb;
        }

        .unread {
            background: var(--brand);
            color: #fff;
            font-size: .58rem;
            font-weight: 700;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 3px 0 0 auto;
        }

        .chat-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--chat-bg);
            min-width: 0;
        }

        .chat-hdr {
            background: #fff;
            border-bottom: 1px solid #edf4f5;
            padding: 11px 18px;
            display: flex;
            align-items: center;
            gap: 11px;
            flex-shrink: 0;
        }

        .mob-back {
            display: none;
            width: 32px;
            height: 32px;
            border: none;
            background: none;
            color: var(--brand);
            font-size: 1.2rem;
            cursor: pointer;
            flex-shrink: 0;
        }

        .hdr-info {
            flex: 1;
            min-width: 0;
        }

        .hdr-name {
            font-weight: 800;
            font-size: .92rem;
            color: #1a1a1a;
        }

        .prod-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #e4f7f9;
            color: var(--brand);
            font-size: .68rem;
            font-weight: 600;
            padding: 2px 9px;
            border-radius: 50px;
            margin-top: 2px;
        }

        .prod-pill::before {
            content: '';
            width: 7px;
            height: 7px;
            background: var(--brand-mid);
            border-radius: 50%;
        }

        .hdr-actions {
            display: flex;
            gap: 7px;
            flex-shrink: 0;
        }

        .hdr-btn {
            width: 33px;
            height: 33px;
            border-radius: 50%;
            border: 1.5px solid #e0f4f6;
            background: #fff;
            color: var(--brand);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .95rem;
            text-decoration: none;
            cursor: pointer;
            transition: background .15s;
        }

        .hdr-btn:hover {
            background: var(--lite);
            color: var(--brand);
        }

        .enc-badge {
            background: #fff;
            border: 1.5px solid #e0f4f6;
            border-radius: 50px;
            padding: 4px 11px;
            font-size: .7rem;
            font-weight: 700;
            color: #555;
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        .enc-badge i {
            color: var(--brand);
        }

        .chat-msgs {
            flex: 1;
            overflow-y: auto;
            padding: 20px 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .msg-row {
            display: flex;
            align-items: flex-end;
            gap: 8px;
        }

        .msg-row.mine {
            flex-direction: row-reverse;
        }

        .bubble {
            max-width: 65%;
            padding: 9px 13px;
            border-radius: 18px;
            font-size: .84rem;
            line-height: 1.5;
            word-break: break-word;
        }

        .bubble.theirs {
            background: var(--brand);
            color: #fff;
            border-bottom-left-radius: 4px;
        }

        .bubble.mine {
            background: #fff;
            color: #333;
            border: 1px solid #e8f0f2;
            border-bottom-right-radius: 4px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, .05);
        }

        .msg-time {
            font-size: .6rem;
            color: #bbb;
            margin-bottom: 2px;
            flex-shrink: 0;
        }

        .date-div {
            text-align: center;
            font-size: .66rem;
            color: #bbb;
            font-weight: 600;
            letter-spacing: .5px;
            margin: 4px 0;
            position: relative;
        }

        .date-div::before,
        .date-div::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #e8f0f2;
        }

        .date-div::before {
            left: 0;
        }

        .date-div::after {
            right: 0;
        }

        .chat-empty {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #ccc;
            gap: 10px;
        }

        .chat-empty i {
            font-size: 3rem;
        }

        .compose {
            background: #fff;
            border-top: 1px solid #edf4f5;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 9px;
            flex-shrink: 0;
        }

        .compose-btn {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: none;
            background: #f0f8f9;
            color: var(--brand);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .95rem;
            cursor: pointer;
            flex-shrink: 0;
        }

        .compose-btn:hover {
            background: var(--lite);
        }

        .compose-input {
            flex: 1;
            background: #f5fbfc;
            border: 1.5px solid #e0f4f6;
            border-radius: 50px;
            padding: 9px 16px;
            font-family: 'Poppins', sans-serif;
            font-size: .84rem;
            color: #333;
            outline: none;
            min-width: 0;
        }

        .compose-input:focus {
            border-color: var(--brand-mid);
        }

        .send-btn {
            width: 37px;
            height: 37px;
            background: var(--brand);
            color: #fff;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .95rem;
            cursor: pointer;
            flex-shrink: 0;
            transition: background .15s;
        }

        .send-btn:hover {
            background: #024f58;
        }

        .no-conv {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #ccc;
            gap: 10px;
        }

        .no-conv i {
            font-size: 3.5rem;
        }

        @media (max-width: 640px) {

            .msg-sidebar {
                width: 100%;
                position: absolute;
                inset: 0;
                top: 54px;
                z-index: 5;
                transform: translateX(0);
            }

            .chat-panel {
                position: absolute;
                inset: 0;
                top: 54px;
                transform: translateX(100%);
                transition: transform .25s ease;
            }

            .msg-shell.chat-open .msg-sidebar {
                transform: translateX(-100%);
            }

            .msg-shell.chat-open .chat-panel {
                transform: translateX(0);
            }

            .mob-back {
                display: flex;
            }

            .enc-badge {
                display: none;
            }

            .hdr-actions .hdr-btn:not(:last-child) {
                display: none;
            }

            .secure-pill {
                display: none;
            }
        }
    </style>
</head>

<body>

    <!--NAVBAR -->
    <nav class="msg-nav">
        <a href="javascript:history.back()" class="back-btn" title="Go back">
            <i class="bi bi-arrow-left"></i> </a>
        <a href="<?php echo $base; ?>/index.php"
            class="nav-brand"
            style="display:flex; align-items:center; gap:10px; text-decoration:none; flex-shrink:0; margin-right:16px;">

            <img
                src="<?php echo $base; ?>/assets/images/Logo.jpg"
                alt="MedMarket"
                width="36"
                class="rounded">

            <span style="
        font-family:'DM Serif Display', serif;
        color:white;
        font-size:1.15rem;
        font-style:italic;
        white-space:nowrap;
    ">
                Med<em>Market</em>
            </span>

        </a>
        <span class="secure-pill">SECURE</span>
        <div class="nav-right">
            <a href="#" class="notif-btn"><i class="bi bi-bell"></i></a>
            <?php if (!empty($currentUser['profile_pic'])): ?>
                <img src="<?php echo $base; ?>/uploads/profiles/<?php echo htmlspecialchars($currentUser['profile_pic']); ?>" class="nav-avatar" alt="">
            <?php else: ?>
                <div class="nav-avatar-init"><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></div>
            <?php endif; ?>
        </div>
    </nav>


    <div class="msg-shell <?php echo $activeParter ? 'chat-open' : ''; ?>" id="msgShell">
        <div class="msg-sidebar">
            <div class="sidebar-head">
                </br>
                <h6 style="color:#024f58;">Messages</h6>
                <div class="search-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" placeholder="Search conversation..." id="conv-search">
                </div>
            </div>

            <?php
            $sections = ['ACTIVE' => [], 'EARLIER' => []];
            foreach ($conversations as $c)
                $sections[time() - strtotime($c['last_sent']) < 86400 ? 'ACTIVE' : 'EARLIER'][] = $c;
            foreach ($sections as $label => $items):
                if (empty($items)) continue; ?>
                <div class="sec-label"><?php echo $label; ?></div>
                <div class="conv-list">
                    <?php foreach ($items as $c): ?>
                        <a href="messages.php?with=<?php echo $c['id']; ?><?php echo $c['product_context_id'] ? '&prod=' . $c['product_context_id'] : ''; ?>"
                            class="conv-item <?php echo $activeParter == $c['id'] ? 'active' : ''; ?>">
                            <div class="c-avatar">
                                <?php echo avatar($c['full_name'], $c['profile_pic'], $base, 40); ?>
                                <?php if ($label === 'ACTIVE'): ?><div class="online-dot"></div><?php endif; ?>
                            </div>
                            <div class="c-body">
                                <div class="c-name"><?php echo htmlspecialchars($c['full_name']); ?></div>
                                <?php if (!empty($c['product_title'])): ?>
                                    <div class="c-product"><?php echo htmlspecialchars($c['product_title']); ?></div>
                                <?php endif; ?>
                                <div class="c-preview"><?php echo htmlspecialchars($c['last_message']); ?></div>
                            </div>
                            <div class="c-meta">
                                <div class="c-time"><?php echo timeAgo($c['last_sent']); ?></div>
                                <?php if ($c['unread_count'] > 0): ?>
                                    <div class="unread"><?php echo $c['unread_count']; ?></div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- CHAT PANEL -->
        <div class="chat-panel">
            <?php if ($partner): ?>

                <div class="chat-hdr">
                    <button class="mob-back" id="mobBack"><i class="bi bi-arrow-left"></i></button>
                    <?php echo avatar($partner['full_name'], $partner['profile_pic'], $base, 42); ?>
                    <div class="hdr-info">
                        <div class="hdr-name"><?php echo htmlspecialchars($partner['full_name']); ?></div>
                        <?php if ($productCtx): ?>
                            <div class="prod-pill"><?php echo htmlspecialchars($productCtx['title']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="hdr-actions">
                        <a href="#" class="hdr-btn"><i class="bi bi-camera-video"></i></a>
                        <a href="#" class="hdr-btn"><i class="bi bi-telephone"></i></a>
                        <div class="enc-badge"><i class="bi bi-lock-fill"></i> Encrypted</div>
                    </div>
                </div>

                <div class="chat-msgs" id="chatMsgs">
                    <?php if (empty($messages)): ?>
                        <div class="chat-empty"><i class="bi bi-chat-dots"></i>
                            <p>No messages yet — say hello!</p>
                        </div>
                        <?php else:
                        $lastDate = null;
                        foreach ($messages as $msg):
                            $d = date('Y-m-d', strtotime($msg['sent_at']));
                            if ($d !== $lastDate): $lastDate = $d;
                                $lbl = date('Y-m-d') === $d ? 'Today' : date('D, d M', strtotime($msg['sent_at'])); ?>
                                <div class="date-div"><?php echo $lbl; ?></div>
                            <?php endif;
                            $mine = $msg['sender_id'] == $userId; ?>
                            <div class="msg-row <?php echo $mine ? 'mine' : ''; ?>">
                                <?php if (!$mine) echo avatar($msg['full_name'], $msg['profile_pic'], $base, 28); ?>
                                <div class="bubble <?php echo $mine ? 'mine' : 'theirs'; ?>">
                                    <?php echo nl2br(htmlspecialchars($msg['message_text'])); ?>
                                </div>
                                <div class="msg-time"><?php echo date('H:i', strtotime($msg['sent_at'])); ?></div>
                            </div>
                    <?php endforeach;
                    endif; ?>
                </div>

                <div class="compose">
                    <form method="POST" action="messages.php?with=<?php echo $activeParter; ?><?php echo $activeProduct ? '&prod=' . $activeProduct : ''; ?>"
                        style="display:contents;" id="msgForm">
                        <input type="hidden" name="receiver_id" value="<?php echo $activeParter; ?>">
                        <input type="hidden" name="product_context_id" value="<?php echo $activeProduct; ?>">
                        <button type="button" class="compose-btn"><i class="bi bi-paperclip"></i></button>
                        <button type="button" class="compose-btn"><i class="bi bi-camera"></i></button>
                        <input type="text" name="message_text" class="compose-input" placeholder="Type a message..." autocomplete="off" id="msgInput">
                        <button type="submit" class="send-btn"><i class="bi bi-send-fill"></i></button>
                    </form>
                </div>

            <?php else: ?>
                <div class="no-conv"><i class="bi bi-chat-square-dots"></i>
                    <p>Select a conversation to start chatting</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const box = document.getElementById('chatMsgs');
        if (box) box.scrollTop = box.scrollHeight;

        const inp = document.getElementById('msgInput');
        if (inp) inp.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (inp.value.trim()) document.getElementById('msgForm').submit();
            }
        });

        document.getElementById('conv-search').addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.conv-item').forEach(el => {
                el.style.display = el.querySelector('.c-name').textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });

        const mobBack = document.getElementById('mobBack');
        if (mobBack) mobBack.addEventListener('click', () => {
            document.getElementById('msgShell').classList.remove('chat-open');
            history.pushState({}, '', 'messages.php');
        });
    </script>

</body>

</html>