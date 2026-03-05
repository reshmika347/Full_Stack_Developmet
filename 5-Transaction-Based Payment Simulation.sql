CREATE TABLE accounts (
    account_id INT NOT NULL AUTO_INCREMENT,
    account_type ENUM('USER', 'MERCHANT') NOT NULL,
    balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (account_id)
);
INSERT INTO accounts VALUES
(1, 'USER', 5000.00),
(101, 'MERCHANT', 1000.00);

Transaction: Deduct User Balance & Add to Merchant
🔹 SUCCESS → COMMIT
START TRANSACTION;

-- Lock user account row
SELECT balance 
FROM accounts 
WHERE account_id = 1 
FOR UPDATE;
UPDATE accounts
SET balance = balance - 1500
WHERE account_id = 1
  AND balance >= 1500;

-- If no row updated → insufficient balance
-- Check affected rows
SELECT ROW_COUNT();
-- Add amount to merchant
UPDATE accounts
SET balance = balance + 1500
WHERE account_id = 101;
COMMIT;
Failure Case (Insufficient Balance):
START TRANSACTION;
UPDATE accounts
SET balance = balance - 7000
WHERE account_id = 1
  AND balance >= 7000;
ROLLBACK;	
Verify Balances:
SELECT * FROM accounts;
