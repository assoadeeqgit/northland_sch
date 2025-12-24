-- ============================================================================
-- DELETE DEMO DATA FROM FINANCIAL DASHBOARD
-- Purpose: Remove all dummy/demo data from the database
-- Database: northland_schools_kano
-- Created: 2025-12-22
-- ============================================================================

USE northland_schools_kano;

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS=0;

-- ============================================================================
-- DELETE FINANCIAL DATA (Payments and Expenses)
-- ============================================================================

-- Delete all payment records
DELETE FROM payments;

-- Delete all expense records
DELETE FROM expenses;

-- Reset auto-increment values
ALTER TABLE payments AUTO_INCREMENT = 1;
ALTER TABLE expenses AUTO_INCREMENT = 1;

-- ============================================================================
-- OPTIONAL: DELETE OTHER DEMO DATA (Uncomment if needed)
-- ============================================================================

-- Delete assignment submissions
-- DELETE FROM assignment_submissions;

-- Delete attendance records
-- DELETE FROM attendance;

-- Delete results
-- DELETE FROM results;

-- Delete assignments
-- DELETE FROM assignments;

-- Delete exams
-- DELETE FROM exams;

-- Delete notices
-- DELETE FROM notices;

-- Delete events
-- DELETE FROM events;

-- Delete library borrowing records
-- DELETE FROM library_borrowing;

-- Delete activity logs
-- DELETE FROM activity_log;

-- Delete generated reports
-- DELETE FROM generated_reports;

-- ============================================================================
-- RE-ENABLE FOREIGN KEY CHECKS
-- ============================================================================

SET FOREIGN_KEY_CHECKS=1;

-- ============================================================================
-- SUMMARY
-- ============================================================================

SELECT 'Demo data deletion completed successfully!' AS Status;

SELECT 
    'Payments' AS TableName, 
    COUNT(*) AS RemainingRecords 
FROM payments
UNION ALL
SELECT 'Expenses', COUNT(*) FROM expenses;
