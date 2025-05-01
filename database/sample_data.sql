-- File: database/sample_data.sql
USE grant_genie;

-- Insert NSF rules
INSERT INTO budget_rules (grant_type, category, rule_name, rule_description, validation_type, validation_value, error_message, suggestion) 
VALUES 
('NSF', 'personnel', 'Senior Personnel Limit', 'NSF limits the number of months of salary for senior personnel', 'max', '2', 'Senior personnel salary exceeds the 2-month NSF limit', 'Consider reducing the number of months or providing cost-sharing from your institution'),
('NSF', 'personnel', 'Graduate Student Minimum Stipend', 'Graduate students should receive the institutional minimum stipend', 'min', '25000', 'Graduate student stipend is below the minimum amount', 'Check your institution\'s minimum graduate stipend rate'),
('NSF', 'personnel', 'Postdoc Salary', 'Postdoc salaries should align with institutional standards', 'min', '50000', 'Postdoc salary is below recommended minimum', 'Adjust to match institutional rates for postdoctoral researchers'),
('NSF', 'indirect', 'Indirect Cost Rate', 'Indirect costs are calculated based on Modified Total Direct Costs (MTDC)', 'percentage', 'varies', 'Indirect cost calculation appears incorrect', 'Use your institution\'s negotiated indirect cost rate'),
('NSF', 'indirect', 'Equipment Exclusion', 'Equipment over $5000 is excluded from MTDC calculation', 'max', '0', 'Equipment should not have indirect costs applied', 'Remove equipment from indirect cost calculation'),
('NSF', 'travel', 'Travel Justification', 'All travel must be justified specific to the project', 'required', 'justification', 'Travel budget items require specific justification', 'Add detailed explanation of how travel relates to research objectives'),
('NSF', 'travel', 'International Travel Flag', 'International travel requires specific approval and justification', 'required', 'flag', 'International travel must be specifically flagged and justified', 'Mark as international travel and provide stronger justification'),
('NSF', 'equipment', 'Equipment Definition', 'Items over $5000 with useful life >1 year are considered equipment', 'min', '5000', 'Items under $5000 should be categorized as supplies, not equipment', 'Recategorize as supplies if under $5000'),
('NSF', 'participant_support', 'Participant Support Restriction', 'Participant support costs cannot be used for other categories', 'forbidden', 'rebudget', 'Participant support costs must remain in this category', 'These funds cannot be repurposed for other budget items'),
('NSF', 'participant_support', 'Indirect Cost Exclusion', 'Participant support costs are excluded from MTDC', 'max', '0', 'Indirect costs should not be applied to participant support', 'Remove participant support from indirect cost calculation');

-- Insert NIH rules
INSERT INTO budget_rules (grant_type, category, rule_name, rule_description, validation_type, validation_value, error_message, suggestion) 
VALUES 
('NIH', 'personnel', 'Salary Cap', 'NIH has a salary cap for all personnel', 'max', '203700', 'Salary exceeds the NIH salary cap', 'Reduce the salary to the NIH cap amount'),
('NIH', 'personnel', 'Effort Reporting', 'Personnel must have effort allocated appropriately', 'min', '1', 'All personnel must have minimum effort allocated', 'Allocate at least 1% effort for all key personnel'),
('NIH', 'travel', 'Conference Limit', 'Conference attendance costs should be reasonable', 'max', '2500', 'Conference costs exceed typical allowed amount', 'Reduce conference costs or provide stronger justification'),
('NIH', 'equipment', 'Equipment Definition', 'Items over $5000 with useful life >1 year are considered equipment', 'min', '5000', 'Items under $5000 should be categorized as supplies', 'Recategorize as supplies if under $5000'),
('NIH', 'indirect', 'F&A Rate', 'Facilities and Administrative costs use negotiated rates', 'percentage', 'varies', 'F&A rate appears incorrect', 'Use your institution\'s negotiated F&A rate with NIH');

-- Insert AI prompts
INSERT INTO ai_prompts (prompt_name, prompt_text, grant_type) 
VALUES 
('budget_generation', 
'You are a grant budget expert for {grant_type} grants. Based on the following project description, generate a comprehensive {duration_years}-year budget that adheres to all {grant_type} guidelines and restrictions.

The budget should include appropriate categories such as:
- Personnel (faculty, postdocs, graduate students, etc.)
- Equipment (items over $5,000 with useful life > 1 year)
- Travel (domestic and international)
- Materials and Supplies
- Publication Costs
- Consultant Services
- Other Direct Costs
- Indirect Costs (based on institutional rates)

For each budget item, include:
1. An appropriate category
2. A descriptive item name
3. A brief description
4. Which year(s) it applies to (1-{duration_years})
5. An appropriate amount in USD
6. Quantity (default to 1 if not explicitly needed)
7. A justification for the expense

Format your response as JSON with the following structure:
{
  "budget_items": [
    {
      "category": "personnel",
      "item_name": "Principal Investigator",
      "description": "2 months summer salary",
      "year": 1,
      "amount": 15000,
      "quantity": 1,
      "justification": "PI will lead all aspects of the project"
    },
    ...more items...
  ]
}

Important {grant_type} guidelines to follow:
- For NIH: Include appropriate salary caps, ensure correct F&A calculations
- For NSF: Limit senior personnel to 2 months total, exclude equipment from indirect costs

Generate a complete budget that would be realistic and compliant with all {grant_type} requirements.',
'ALL');