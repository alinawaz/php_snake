INSERT INTO users (username, password, role) 
VALUES ('admin', '$2y$10$G9ZJcZ9Fl7dPqf5y9SdZcOCXgE/suVYt6QgjkMGXaB2s3ihr3cAZK', 'admin');


-- i mistakenly ran admin charge cycle with output
-- ✓ Applying bank charges...
-- ➡️ Last bank charge cycle ran on: 2025-08-30 13:54:30 with total amount: 383.00
-- ✓ Bank charges already applied today. Skipping.
-- Need to transfer above collected amount to bank admin account ACCT39853
-- Query for above account transaction
INSERT INTO transactions (account_id, type, amount, message, status) 
VALUES ((SELECT id FROM accounts WHERE account_number='ACCT39853' LIMIT 1), 'credit', 383.00, 'Total bank charges collected for the day, date: 2025-08-30 13:54:30' , 'charged');