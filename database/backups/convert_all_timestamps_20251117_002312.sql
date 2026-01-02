USE db_leave;
START TRANSACTION;
UPDATE notifications SET
  created_at = CASE WHEN created_at IS NOT NULL THEN DATE_ADD(created_at, INTERVAL 7 HOUR) ELSE NULL END,
  read_at = CASE WHEN read_at IS NOT NULL THEN DATE_ADD(read_at, INTERVAL 7 HOUR) ELSE NULL END,
  updated_at = CASE WHEN updated_at IS NOT NULL THEN DATE_ADD(updated_at, INTERVAL 7 HOUR) ELSE NULL END;
UPDATE permissions SET
  created_at = CASE WHEN created_at IS NOT NULL THEN DATE_ADD(created_at, INTERVAL 7 HOUR) ELSE NULL END,
  updated_at = CASE WHEN updated_at IS NOT NULL THEN DATE_ADD(updated_at, INTERVAL 7 HOUR) ELSE NULL END;
UPDATE users SET
  created_at = CASE WHEN created_at IS NOT NULL THEN DATE_ADD(created_at, INTERVAL 7 HOUR) ELSE NULL END,
  email_verified_at = CASE WHEN email_verified_at IS NOT NULL THEN DATE_ADD(email_verified_at, INTERVAL 7 HOUR) ELSE NULL END,
  updated_at = CASE WHEN updated_at IS NOT NULL THEN DATE_ADD(updated_at, INTERVAL 7 HOUR) ELSE NULL END;
UPDATE roles SET
  created_at = CASE WHEN created_at IS NOT NULL THEN DATE_ADD(created_at, INTERVAL 7 HOUR) ELSE NULL END,
  updated_at = CASE WHEN updated_at IS NOT NULL THEN DATE_ADD(updated_at, INTERVAL 7 HOUR) ELSE NULL END;
UPDATE leave_requests SET
  approved_at = CASE WHEN approved_at IS NOT NULL THEN DATE_ADD(approved_at, INTERVAL 7 HOUR) ELSE NULL END,
  created_at = CASE WHEN created_at IS NOT NULL THEN DATE_ADD(created_at, INTERVAL 7 HOUR) ELSE NULL END,
  hr_notified_at = CASE WHEN hr_notified_at IS NOT NULL THEN DATE_ADD(hr_notified_at, INTERVAL 7 HOUR) ELSE NULL END,
  manager_approved_at = CASE WHEN manager_approved_at IS NOT NULL THEN DATE_ADD(manager_approved_at, INTERVAL 7 HOUR) ELSE NULL END,
  supervisor_approved_at = CASE WHEN supervisor_approved_at IS NOT NULL THEN DATE_ADD(supervisor_approved_at, INTERVAL 7 HOUR) ELSE NULL END,
  updated_at = CASE WHEN updated_at IS NOT NULL THEN DATE_ADD(updated_at, INTERVAL 7 HOUR) ELSE NULL END;
UPDATE departments SET
  created_at = CASE WHEN created_at IS NOT NULL THEN DATE_ADD(created_at, INTERVAL 7 HOUR) ELSE NULL END,
  updated_at = CASE WHEN updated_at IS NOT NULL THEN DATE_ADD(updated_at, INTERVAL 7 HOUR) ELSE NULL END;
UPDATE failed_jobs SET
  failed_at = CASE WHEN failed_at IS NOT NULL THEN DATE_ADD(failed_at, INTERVAL 7 HOUR) ELSE NULL END;
UPDATE password_reset_tokens SET
  created_at = CASE WHEN created_at IS NOT NULL THEN DATE_ADD(created_at, INTERVAL 7 HOUR) ELSE NULL END;
UPDATE approvals SET
  created_at = CASE WHEN created_at IS NOT NULL THEN DATE_ADD(created_at, INTERVAL 7 HOUR) ELSE NULL END,
  updated_at = CASE WHEN updated_at IS NOT NULL THEN DATE_ADD(updated_at, INTERVAL 7 HOUR) ELSE NULL END;
COMMIT;

