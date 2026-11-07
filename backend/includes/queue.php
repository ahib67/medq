<?php
require_once __DIR__ . '/notify.php';
// Helper functions used by doctor and receptionist pages

// Save a staff action in the audit log (FR-13, NFR-10)
function log_action($pdo, $action) {
    $stmt = $pdo->prepare('INSERT INTO audit_log (user_id, action) VALUES (?, ?)');
    $stmt->execute([$_SESSION['user_id'], $action]);
}

// Find the doctor_id of the logged-in doctor
function current_doctor_id($pdo) {
    $stmt = $pdo->prepare('SELECT doctor_id FROM doctors WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $id = $stmt->fetchColumn();
    if (!$id) {
        die('No doctor profile found for this account. Ask the admin to create one.');
    }
    return (int)$id;
}

// Today's queue of one doctor.
// Order: patient in consultation, then waiting patients (emergency, senior, normal, then token number), then the rest.
function get_queue($pdo, $doctor_id) {
    $stmt = $pdo->prepare("SELECT t.token_id, t.token_no, t.priority, t.status, t.booking_type,
                                  p.name AS patient_name, p.phone AS patient_phone
                             FROM tokens t
                             JOIN patients p ON p.patient_id = t.patient_id
                            WHERE t.doctor_id = ? AND t.token_date = CURDATE()
                            ORDER BY CASE t.status WHEN 'in_consultation' THEN 0 WHEN 'waiting' THEN 1 ELSE 2 END,
                                     IF(t.status = 'waiting', FIELD(t.priority,'emergency','senior','normal'), 0),
                                     t.token_no");
    $stmt->execute([$doctor_id]);
    return $stmt->fetchAll();
}

// Call the next waiting patient (FR-7).
// Returns [token_no, ''] on success or [null, 'error message'].
function call_next($pdo, $doctor_id) {
    $pdo->beginTransaction();

    // Lock the doctor row so two quick clicks cannot call two patients
    $stmt = $pdo->prepare('SELECT doctor_id FROM doctors WHERE doctor_id = ? FOR UPDATE');
    $stmt->execute([$doctor_id]);

    // Only one patient may be in consultation at a time
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tokens
                            WHERE doctor_id = ? AND token_date = CURDATE() AND status = 'in_consultation'");
    $stmt->execute([$doctor_id]);
    if ($stmt->fetchColumn() > 0) {
        $pdo->rollBack();
        return [null, 'Complete or skip the current patient first.'];
    }

    // The next patient: emergency first, then senior, then normal, then lowest token number (FR-15)
    $stmt = $pdo->prepare("SELECT token_id, token_no FROM tokens
                            WHERE doctor_id = ? AND token_date = CURDATE() AND status = 'waiting'
                            ORDER BY FIELD(priority,'emergency','senior','normal'), token_no
                            LIMIT 1");
    $stmt->execute([$doctor_id]);
    $next = $stmt->fetch();
    if (!$next) {
        $pdo->rollBack();
        return [null, 'No patients are waiting.'];
    }

    $stmt = $pdo->prepare("UPDATE tokens SET status = 'in_consultation', called_at = NOW() WHERE token_id = ?");
    $stmt->execute([$next['token_id']]);

        $pdo->commit();

    // NEW: alerts (FR-6, FR-18)
    add_notification($pdo, $next['token_id'], 'your_turn');   // tell the called patient
    check_near_turn($pdo, $doctor_id);                        // tell the 3rd patient in line

    return [$next['token_no'], ''];
}

// Mark the consultation as finished. Returns true on success.
function complete_consultation($pdo, $token_id, $doctor_id) {
    $stmt = $pdo->prepare("UPDATE tokens SET status = 'completed', completed_at = NOW()
                            WHERE token_id = ? AND doctor_id = ? AND status = 'in_consultation'");
    $stmt->execute([$token_id, $doctor_id]);

    if ($stmt->rowCount() === 1) {
        update_avg_consult($pdo, $doctor_id);   // FR-8
        return true;
    }
    return false;
}

// Skip a patient or mark a no-show (FR-7, FR-11). Both set the status "no_show".
function mark_no_show($pdo, $token_id, $doctor_id) {
    $stmt = $pdo->prepare("UPDATE tokens SET status = 'no_show'
                            WHERE token_id = ? AND doctor_id = ? AND status IN ('waiting','in_consultation')");
    $stmt->execute([$token_id, $doctor_id]);
    return $stmt->rowCount() === 1;
}

// FR-8: new average = average time of the doctor's last 20 completed consultations (in minutes)
function update_avg_consult($pdo, $doctor_id) {
    $stmt = $pdo->prepare("SELECT AVG(TIMESTAMPDIFF(SECOND, called_at, completed_at)) / 60
                             FROM (SELECT called_at, completed_at FROM tokens
                                    WHERE doctor_id = ? AND status = 'completed'
                                      AND called_at IS NOT NULL AND completed_at IS NOT NULL
                                    ORDER BY completed_at DESC LIMIT 20) AS last_visits");
    $stmt->execute([$doctor_id]);
    $avg = $stmt->fetchColumn();

    if ($avg !== null) {
        $minutes = min(120, max(1, (int)ceil($avg)));   // keep it between 1 and 120
        $stmt = $pdo->prepare('UPDATE doctors SET avg_consult_mins = ? WHERE doctor_id = ?');
        $stmt->execute([$minutes, $doctor_id]);
    }
}
