<?php
date_default_timezone_set('Asia/Karachi');

// The message text for each alert type
function notification_text($type, $token_no, $doctor, $date, $room) {
    if ($type === 'token_booked') return "Token #$token_no booked with $doctor for $date.";
    if ($type === 'near_turn')    return "You are 3 tokens away (token #$token_no, $doctor). Please come to the clinic now.";
    if ($type === 'your_turn')    return "It is your turn! Token #$token_no, $doctor, room $room.";
    if ($type === 'cancelled')    return "Token #$token_no with $doctor on $date was cancelled.";
    return "Update about your token with $doctor.";
}

// Save an alert for the patient of a token, then try to send it.
// Walk-in patients have no account, so nobody to notify: we simply return.
function add_notification($pdo, $token_id, $type) {
    $stmt = $pdo->prepare('SELECT p.user_id FROM tokens t JOIN patients p ON p.patient_id = t.patient_id WHERE t.token_id = ?');
    $stmt->execute([$token_id]);
    $user_id = $stmt->fetchColumn();
    if (!$user_id) return;

    $stmt = $pdo->prepare("INSERT INTO notifications (token_id, recipient_user_id, type, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$token_id, $user_id, $type]);

    send_notification($pdo, $pdo->lastInsertId());
}

// "Send" one saved alert and mark it as sent or failed
function send_notification($pdo, $notif_id) {
    $stmt = $pdo->prepare('SELECT n.type, u.email, u.phone, t.token_no, t.token_date, du.name AS doctor_name, d.room_no
                             FROM notifications n
                             JOIN users u ON u.user_id = n.recipient_user_id
                             LEFT JOIN tokens t ON t.token_id = n.token_id
                             LEFT JOIN doctors d ON d.doctor_id = t.doctor_id
                             LEFT JOIN users du ON du.user_id = d.user_id
                            WHERE n.notif_id = ?');
    $stmt->execute([$notif_id]);
    $n = $stmt->fetch();
    if (!$n) return;

    $text = notification_text($n['type'], $n['token_no'], $n['doctor_name'], $n['token_date'], $n['room_no']);

    if (defined('MAIL_ENABLED') && MAIL_ENABLED) {
        // Real email (needs a mail server on the machine)
        $ok = @mail($n['email'], 'MedQ notification', $text, 'From: no-reply@medq.local');
    } else {
        // Simulation: write the "SMS + email" into a log file
        $line = '[' . date('Y-m-d H:i:s') . '] to ' . $n['email'] . ' / ' . $n['phone'] . ': ' . $text . "\n";
        $ok = @file_put_contents(__DIR__ . '/../storage/notifications.log', $line, FILE_APPEND) !== false;
    }

    $status = $ok ? 'sent' : 'failed';
    $stmt = $pdo->prepare("UPDATE notifications SET status = ?, sent_at = IF(? = 'sent', NOW(), NULL) WHERE notif_id = ?");
    $stmt->execute([$status, $status, $notif_id]);
}

// FR-18: when the current token changes, tell the 3rd waiting patient "you are 3 tokens away".
// OFFSET 2 skips the first two waiting patients, so we get the 3rd one.
function check_near_turn($pdo, $doctor_id) {
    $stmt = $pdo->prepare("SELECT token_id FROM tokens
                            WHERE doctor_id = ? AND token_date = CURDATE() AND status = 'waiting'
                            ORDER BY FIELD(priority,'emergency','senior','normal'), token_no
                            LIMIT 1 OFFSET 2");
    $stmt->execute([$doctor_id]);
    $token_id = $stmt->fetchColumn();
    if (!$token_id) return;

    // Do not send the same alert twice
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE token_id = ? AND type = 'near_turn'");
    $stmt->execute([$token_id]);
    if ($stmt->fetchColumn() == 0) {
        add_notification($pdo, $token_id, 'near_turn');
    }
}
