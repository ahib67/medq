<?php
// Helper functions used by the patient pages

// Find the patient_id of the logged-in user
function current_patient_id($pdo) {
    $stmt = $pdo->prepare('SELECT patient_id FROM patients WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $id = $stmt->fetchColumn();
    if (!$id) {
        die('No patient profile found for this account.');
    }
    return (int)$id;
}

// Create a token for a patient.
// Returns [token_id, ''] on success or [null, 'error message'] on failure.
function issue_token($pdo, $doctor_id, $patient_id, $date) {
    $today = date('Y-m-d');

    // 1. Check the date
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        return [null, 'Choose a valid date.'];
    }
    if ($date < $today) {
        return [null, 'You cannot book a past date.'];
    }
    if ($date > date('Y-m-d', strtotime('+30 days'))) {
        return [null, 'You can book only up to 30 days ahead.'];
    }

    // 2. Does the doctor work on that weekday? (Mon, Tue, ...)
    $day = date('D', strtotime($date));
    $stmt = $pdo->prepare('SELECT MAX(end_time) AS last_end, SUM(max_tokens) AS max_tokens
                             FROM doctor_schedules WHERE doctor_id = ? AND day_of_week = ?');
    $stmt->execute([$doctor_id, $day]);
    $sched = $stmt->fetch();
    if (!$sched['max_tokens']) {
        return [null, 'The doctor does not work on this day.'];
    }
    if ($date === $today && date('H:i:s') >= $sched['last_end']) {
        return [null, "Today's clinic hours are over."];
    }

    // 3. Does the patient already have an active token for this doctor on this date?
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tokens
                            WHERE doctor_id = ? AND patient_id = ? AND token_date = ?
                              AND status IN ('waiting','in_consultation')");
    $stmt->execute([$doctor_id, $patient_id, $date]);
    if ($stmt->fetchColumn() > 0) {
        return [null, 'You already have a token with this doctor on this date.'];
    }

    // 4. Give the next token number inside a transaction
    $pdo->beginTransaction();

    // FOR UPDATE locks the doctor row. If two patients book at the same moment,
    // the second one waits until the first finishes, so numbers never repeat.
    $stmt = $pdo->prepare('SELECT doctor_id FROM doctors WHERE doctor_id = ? FOR UPDATE');
    $stmt->execute([$doctor_id]);
    if (!$stmt->fetch()) {
        $pdo->rollBack();
        return [null, 'Doctor not found.'];
    }

    // Daily limit (FR-14): cancelled tokens do not count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tokens WHERE doctor_id = ? AND token_date = ? AND status <> 'cancelled'");
    $stmt->execute([$doctor_id, $date]);
    if ($stmt->fetchColumn() >= $sched['max_tokens']) {
        $pdo->rollBack();
        return [null, 'All tokens for this day are booked.'];
    }

    // Next number = highest number so far + 1
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(token_no), 0) + 1 FROM tokens WHERE doctor_id = ? AND token_date = ?');
    $stmt->execute([$doctor_id, $date]);
    $token_no = $stmt->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO tokens (doctor_id, patient_id, token_date, token_no, booking_type, priority, status)
                           VALUES (?, ?, ?, ?, 'online', 'normal', 'waiting')");
    $stmt->execute([$doctor_id, $patient_id, $date, $token_no]);
    $token_id = $pdo->lastInsertId();

    $pdo->commit();
    return [$token_id, ''];
}
