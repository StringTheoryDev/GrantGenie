-- File: database/insert_data.sql
USE grant_genie;

-- Insert NSF rules
INSERT INTO budget_rules (grant_type, category, rule_name, rule_description, validation_type, validation_value, error_message, suggestion) 
VALUES 
('NSF', 'personnel', 'Senior Personnel Limit', 'NSF limits the number of months of salary for senior personnel', 'max', '2', 'Senior personnel salary exceeds the 2-month NSF limit', 'Consider reducing the number of months or providing cost-sharing from your institution');

INSERT INTO budget_rules (grant_type, category, rule_name, rule_description, validation_type, validation_value, error_message, suggestion) 
VALUES
('NSF', 'personnel', 'Graduate Student Minimum Stipend', 'Graduate students should receive the institutional minimum stipend', 'min', '25000', 'Graduate student stipend is below the minimum amount', 'Check your institution\'s minimum graduate stipend rate');

-- Add more insert statements, one at a time to locate any issues