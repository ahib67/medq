-- Approach B: derived current token (from your ERD)
SELECT token_no AS current_token_no
  FROM tokens
 WHERE status = 'in_consultation'
   AND doctor_id = 1
   AND token_date = CURDATE()
 LIMIT 1;

-- How many patients are ahead of token #3?
SELECT COUNT(*) AS people_ahead
  FROM tokens
 WHERE doctor_id = 1 AND token_date = CURDATE()
   AND status = 'waiting' AND token_no < 3;

-- Next waiting token (emergency > senior > normal, then by token_no)
SELECT token_id, token_no, priority
  FROM tokens
 WHERE doctor_id = 1 AND token_date = CURDATE() AND status = 'waiting'
 ORDER BY FIELD(priority,'emergency','senior','normal'), token_no
 LIMIT 1;

-- Doctor directory with department
SELECT d.doctor_id, u.name, dep.dept_name, d.room_no
  FROM doctors d
  JOIN users u        ON u.user_id = d.user_id
  JOIN departments dep ON dep.dept_id = d.dept_id;
