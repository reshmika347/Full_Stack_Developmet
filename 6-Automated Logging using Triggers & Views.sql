-- ============================================
-- TASK 6: AUTOMATED LOGGING USING TRIGGERS & VIEWS
-- Real-Time Audit Logging for Enterprise Databases
-- ============================================


-- 1. MAIN BUSINESS TABLE
CREATE TABLE employees (
    employee_id SERIAL PRIMARY KEY,
    name TEXT,
    department TEXT,
    salary NUMERIC,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- 2. AUDIT LOG TABLE
CREATE TABLE audit_log (
    log_id SERIAL PRIMARY KEY,
    table_name TEXT,
    operation TEXT,              -- INSERT or UPDATE
    record_id INTEGER,
    old_data JSONB,
    new_data JSONB,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- 3. TRIGGER FUNCTION FOR INSERT & UPDATE LOGGING
CREATE OR REPLACE FUNCTION audit_trigger_function()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        INSERT INTO audit_log (
            table_name,
            operation,
            record_id,
            new_data
        )
        VALUES (
            TG_TABLE_NAME,
            TG_OP,
            NEW.employee_id,
            to_jsonb(NEW)
        );

    ELSIF TG_OP = 'UPDATE' THEN
        INSERT INTO audit_log (
            table_name,
            operation,
            record_id,
            old_data,
            new_data
        )
        VALUES (
            TG_TABLE_NAME,
            TG_OP,
            NEW.employee_id,
            to_jsonb(OLD),
            to_jsonb(NEW)
        );
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;


-- 4. TRIGGER ATTACHED TO THE EMPLOYEES TABLE
CREATE TRIGGER employees_audit_trigger
AFTER INSERT OR UPDATE ON employees
FOR EACH ROW
EXECUTE FUNCTION audit_trigger_function();


-- 5. DAILY ACTIVITY REPORT VIEW
CREATE VIEW daily_activity_report AS
SELECT
    DATE(changed_at) AS activity_date,
    table_name,
    operation,
    COUNT(*) AS total_operations
FROM audit_log
GROUP BY
    DATE(changed_at),
    table_name,
    operation
ORDER BY
    activity_date DESC;


-- 6. SAMPLE OPERATIONS (OPTIONAL TEST DATA)
INSERT INTO employees (name, department, salary)
VALUES ('Alice', 'IT', 70000);

UPDATE employees
SET salary = 75000
WHERE employee_id = 1;


-- 7. VIEW DAILY AUDIT ACTIVITY
SELECT * FROM daily_activity_report;
