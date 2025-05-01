<?php
// includes/GeminiAPI.php
class GeminiAPI {
    private $apiKey;
    private $endpoint = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash-lite:generateContent';
    private $conn;
    private $promptsTable = "ai_prompts";
    
    // Constructor
    public function __construct($db, $apiKey) {
        $this->conn = $db;
        $this->apiKey = $apiKey;
    }
    
    // Get the appropriate prompt for a grant type
    private function getPrompt($grantType, $durationYears = null) { // Added durationYears parameter for fallback
        $query = "SELECT prompt_text FROM " . $this->promptsTable . " 
                  WHERE grant_type = ? AND prompt_name = 'budget_generation' 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $grantType);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            // Return prompt from database if found
            return $row['prompt_text'];
        } else {
            // Default prompt if none found in the database
            // Use HEREDOC for easier multi-line string management
            $fallbackPrompt = <<<PROMPT
You are a grant budget expert specializing in NSF grants for the University of Idaho. 
Create a comprehensive {duration_years}-year budget for this project.
Follow these specific guidelines:
1. Use University of Idaho fringe benefit rates (Faculty: 28.5%, Staff: 40.2%, Students: 5.5%)
2. Include indirect costs at Idaho's negotiated rate (45% of Modified Total Direct Costs)
3. Exclude equipment over $5,000 from MTDC calculations
4. Maximum 2 months of faculty summer salary per year
5. Graduate student stipends should align with University of Idaho rates
6. Include tuition remission for graduate students at Idaho rates
7. Only include expenses that are allowable under NSF guidelines
Format the budget with these EXACT categories:
- Personnel (faculty, postdocs, graduate students, undergraduate students)
- Fringe Benefits (using correct Idaho rates per category)
- Equipment (items over $5,000 with useful life > 1 year)
- Travel (domestic and international)
- Materials and Supplies
- Publication Costs
- Consultant Services
- Computer Services
- Subawards (first $25,000 only in MTDC)
- Other Direct Costs
- Indirect Costs (45% of MTDC)

Format your response as JSON with the following structure:
{
  "budget_items": [
    {
      "category": "Personnel", 
      "item_name": "Principal Investigator",
      "description": "e.g., 1 month summer salary",
      "year": 1,
      "amount": 12000, 
      "quantity": 1,
      "justification": "Required for project oversight" 
    },
    {
      "category": "Fringe Benefits",
      "item_name": "Faculty Fringe", 
      "description": "Calculated on PI Salary",
      "year": 1,
      "amount": 3420, // Example: 12000 * 28.5%
      "quantity": 1,
      "justification": "UI Faculty Fringe Rate (28.5%)"
    }
    // ... more items
  ]
}
Ensure all budget items adhere to NSF guidelines and University of Idaho policies. Provide justifications for all items. Calculate Fringe Benefits based on the personnel costs and specified rates. Calculate Indirect Costs based on the MTDC.
PROMPT;
            // Replace placeholders in the fallback prompt
            // Use ?? operator to provide a default if $durationYears is null
            $fallbackPrompt = str_replace('{duration_years}', $durationYears ?? 'unknown', $fallbackPrompt); 
            // Also replace {grant_type} placeholder which was missing in the original fallback logic
            $fallbackPrompt = str_replace('{grant_type}', $grantType, $fallbackPrompt); 
            return $fallbackPrompt;
        }
    }
    
    // Generate budget using Gemini API
    public function generateBudget($projectDescription, $grantType, $durationYears) {
        // Get the appropriate prompt, passing durationYears for the fallback
        $prompt = $this->getPrompt($grantType, $durationYears); 
        
        // Replace placeholders in the prompt (potentially again for DB prompts, harmless)
        $prompt = str_replace('{grant_type}', $grantType, $prompt);
        $prompt = str_replace('{duration_years}', $durationYears, $prompt);
        
        // Prepare the request data
        $data = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt . "\n\nProject Description: " . $projectDescription
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192
            ]
        ];
        
        // Initialize cURL session
        $ch = curl_init();
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $this->endpoint . '?key=' . $this->apiKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        // Execute cURL session and get the response
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status code

        // Check for errors
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL Error: " . $error);
        }
        
        curl_close($ch);
        
        // Log the raw response for debugging
        $this->logResponse($response, $httpCode); // Use helper function to log

        // Decode the response
        $responseData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
             // Log the invalid JSON response before throwing exception
             file_put_contents('../temp/error_log.txt', "Invalid JSON received: " . $response . "\n", FILE_APPEND);
            throw new Exception("Failed to parse API response as JSON: " . json_last_error_msg());
        }
        
        // Create a fallback budget (using the dedicated helper function)
        $fallbackBudget = [
            'budget_items' => $this->createFallbackBudget($grantType)
        ];
        
        // Check for API errors in the response structure
        if (isset($responseData['error'])) {
             file_put_contents('../temp/error_log.txt', "API Error Response: " . json_encode($responseData['error']) . "\n", FILE_APPEND);
             return $fallbackBudget; // Return fallback on API error
        }

        // Extract the text content from the response (with improved error handling)
        $text = null;
        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $responseData['candidates'][0]['content']['parts'][0]['text'];
        } elseif (isset($responseData['candidates'][0]['finishReason']) && $responseData['candidates'][0]['finishReason'] !== 'STOP') {
             // Log safety or other non-STOP finish reasons
             file_put_contents('../temp/error_log.txt', "API Finish Reason Error: " . $responseData['candidates'][0]['finishReason'] . "\n" . json_encode($responseData). "\n", FILE_APPEND);
             return $fallbackBudget; // Return fallback if generation stopped for safety/error
        } else {
             // Log unexpected structure
             file_put_contents('../temp/error_log.txt', "Invalid response structure: " . json_encode($responseData) . "\n", FILE_APPEND);
             return $fallbackBudget; // Return fallback on unexpected structure
        }
        
        // Log the extracted text
        file_put_contents('../temp/extracted_text.txt', $text . "\n", FILE_APPEND);
        
        // Attempt to parse JSON from the extracted text
        $budgetData = null;

        // Look for JSON inside markdown code blocks first
        if (preg_match('/```(?:json)?\s*({[\s\S]*?})\s*```/m', $text, $matches)) {
            $jsonContent = $matches[1];
            $budgetData = json_decode($jsonContent, true);
            // Check if JSON is valid and contains the expected structure
            if (json_last_error() !== JSON_ERROR_NONE || !isset($budgetData['budget_items'])) {
                 file_put_contents('../temp/error_log.txt', "Failed to parse JSON from markdown block or invalid structure. Error: " . json_last_error_msg() . "\nContent: " . $jsonContent . "\n", FILE_APPEND);
                 $budgetData = null; // Reset if parsing failed or structure is wrong
            }
        } 
        
        // If not found in markdown, try parsing the whole text as JSON (or find first '{' to last '}')
        if ($budgetData === null && preg_match('/{[\s\S]*}/', $text, $matches)) {
             $jsonContent = $matches[0]; // Try the first match of a potential JSON object
             $budgetData = json_decode($jsonContent, true);
              // Check if JSON is valid and contains the expected structure
             if (json_last_error() !== JSON_ERROR_NONE || !isset($budgetData['budget_items'])) {
                 file_put_contents('../temp/error_log.txt', "Failed to parse JSON from raw text or invalid structure. Error: " . json_last_error_msg() . "\nContent: " . $jsonContent . "\n", FILE_APPEND);
                 $budgetData = null; // Reset if parsing failed or structure is wrong
             }
        }

        // If valid budget data was parsed, process and return it
        if ($budgetData !== null && isset($budgetData['budget_items']) && is_array($budgetData['budget_items'])) {
             // Process and standardize the budget items (optional: add more validation)
             foreach ($budgetData['budget_items'] as &$item) {
                 // Ensure required fields exist with defaults
                 $item['category'] = $item['category'] ?? 'Other';
                 $item['item_name'] = $item['item_name'] ?? 'Unnamed Item';
                 $item['description'] = $item['description'] ?? '';
                 $item['year'] = isset($item['year']) && is_numeric($item['year']) ? (int)$item['year'] : 1;
                 $item['amount'] = isset($item['amount']) && is_numeric($item['amount']) ? (float)$item['amount'] : 0;
                 $item['quantity'] = isset($item['quantity']) && is_numeric($item['quantity']) ? (int)$item['quantity'] : 1;
                 $item['justification'] = $item['justification'] ?? '';
             }
             unset($item); // Unset reference after loop
             return $budgetData;
        }

        // If all parsing attempts fail or result in invalid structure, log and return fallback
        file_put_contents('../temp/error_log.txt', "Failed to extract valid JSON budget data from API response. Returning fallback.\nResponse Text: " . $text . "\n", FILE_APPEND);
        return $fallbackBudget; // Return the fallback budget as last resort
    }

    // Helper function to log API responses
    private function logResponse($response, $httpCode) {
        // Create temp directory if it doesn't exist
        if (!file_exists('../temp')) {
             mkdir('../temp', 0777, true);
        }
        $logPath = '../temp/api_log.txt';
        $logMessage = "=== " . date('Y-m-d H:i:s') . " === HTTP Code: $httpCode ===\n";
        // Log first 1000 chars, or full response if shorter
        $logMessage .= mb_substr($response, 0, 1000); 
        if (mb_strlen($response) > 1000) {
             $logMessage .= "\n...\n";
        }
        $logMessage .= "\n\n";
        file_put_contents($logPath, $logMessage, FILE_APPEND);
    }

    // Helper function to create fallback budget items
    private function createFallbackBudget($grantType) {
        // Basic fallback structure
        return [
            [
                'category' => 'Personnel', // Match category casing from prompt example
                'item_name' => 'Principal Investigator',
                'description' => 'Placeholder - Check AI Response',
                'year' => 1,
                'amount' => 15000,
                'quantity' => 1,
                'justification' => 'Placeholder - PI leadership (Fallback)'
            ],
             [
                'category' => 'Fringe Benefits', // Match category casing from prompt example
                'item_name' => 'Faculty Fringe',
                'description' => 'Placeholder - Check AI Response',
                'year' => 1,
                'amount' => 4275, // Example placeholder (15000 * 28.5%)
                'quantity' => 1,
                'justification' => 'Placeholder - Calculated on PI Salary (Fallback)'
            ],
            [
                'category' => 'Other Direct Costs', // Match category casing
                'item_name' => 'General Supplies',
                'description' => 'Placeholder - Check AI Response',
                'year' => 1,
                'amount' => 5000,
                'quantity' => 1,
                'justification' => 'Placeholder - General research supplies (Fallback)'
            ]
        ];
    }

    // (Kept for reference, but primary logic now attempts JSON parsing first)
    // Helper function to parse plain text into budget items - VERY basic
    private function parsePlainTextToBudgetItems($text) {
        // This is a very rudimentary attempt if JSON parsing completely fails
        // It's unlikely to produce a useful budget and serves mainly as a last resort.
         file_put_contents('../temp/error_log.txt', "Attempting basic text parsing as last resort.\nText: " . mb_substr($text, 0, 500) . "...\n", FILE_APPEND);
        return [
            [
                'category' => 'Other',
                'item_name' => 'Fallback Item',
                'description' => 'Failed to parse structured data from AI response.',
                'year' => 1,
                'amount' => 0,
                'quantity' => 1,
                'justification' => 'See raw AI response in logs.'
            ]
        ];
    }
}
?>