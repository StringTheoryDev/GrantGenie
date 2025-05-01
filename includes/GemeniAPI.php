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
    private function getPrompt($grantType) {
        $query = "SELECT prompt_text FROM " . $this->promptsTable . " 
                  WHERE grant_type = ? AND prompt_name = 'budget_generation' 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $grantType);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            return $row['prompt_text'];
        } else {
            // Default prompt if none found
            return "You are a grant budget expert. Given the following grant project description, 
                    generate a comprehensive budget for a {$grantType} grant proposal. 
                    The budget should include personnel, equipment, travel, supplies, and other relevant categories. 
                    Format your response as JSON with the following structure:
                    {
                        \"budget_items\": [
                            {
                                \"category\": \"personnel\",
                                \"item_name\": \"Principal Investigator\",
                                \"description\": \"2 months summer salary\",
                                \"year\": 1,
                                \"amount\": 15000,
                                \"quantity\": 1,
                                \"justification\": \"PI will lead all aspects of the project\"
                            },
                            ...
                        ]
                    }
                    Ensure all budget items adhere to {$grantType} guidelines and are properly justified.";
        }
    }
    
    // Generate budget using Gemini API
    public function generateBudget($projectDescription, $grantType, $durationYears) {
        // Get the appropriate prompt
        $prompt = $this->getPrompt($grantType);
        
        // Replace placeholders in the prompt
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
        
        // Check for cURL errors
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL Error: " . $error);
        }
        
        // Get the HTTP status code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Debug response
        $this->logResponse($response, $httpCode);
        
        // Check for HTTP errors
        if ($httpCode !== 200) {
            throw new Exception("API returned HTTP code $httpCode. Response: " . substr($response, 0, 300) . "...");
        }
        
        // Decode the response
        $responseData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response: " . json_last_error_msg() . ". Raw response: " . substr($response, 0, 300) . "...");
        }
        
        // Extract the generated text
        if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception("Unexpected API response structure. Response: " . json_encode($responseData));
        }
        
        $generatedText = $responseData['candidates'][0]['content']['parts'][0]['text'];
        
        // Extract JSON from the response (it might be wrapped in markdown code blocks)
        preg_match('/```json\s*([\s\S]*?)\s*```|({[\s\S]*})/', $generatedText, $matches);
        
        if (empty($matches)) {
            // No JSON found, create a simple fallback structure with the AI response text
            $budgetData = [
                'budget_items' => $this->parsePlainTextToBudgetItems($generatedText)
            ];
        } else {
            $jsonString = isset($matches[1]) && !empty($matches[1]) ? $matches[1] : (isset($matches[2]) ? $matches[2] : $generatedText);
            
            // Parse the JSON
            $budgetData = json_decode($jsonString, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                // If JSON parsing fails, try to extract just the JSON part
                preg_match('/{[\s\S]*}/', $generatedText, $jsonMatches);
                if (isset($jsonMatches[0])) {
                    $budgetData = json_decode($jsonMatches[0], true);
                }
            }
        }
        
        // Make sure we have valid budget data
        if (json_last_error() !== JSON_ERROR_NONE || !isset($budgetData['budget_items'])) {
            // Create fallback budget items
            $budgetData = [
                'budget_items' => $this->createFallbackBudget($grantType)
            ];
        }
        
        return $budgetData;
    }

    // Helper function to log API responses
    private function logResponse($response, $httpCode) {
        $logPath = '../temp/api_log.txt';
        $logMessage = "=== " . date('Y-m-d H:i:s') . " === HTTP Code: $httpCode ===\n";
        $logMessage .= substr($response, 0, 1000) . "\n...\n\n";
        file_put_contents($logPath, $logMessage, FILE_APPEND);
    }

    // Helper function to create fallback budget items
    private function createFallbackBudget($grantType) {
        return [
            [
                'category' => 'personnel',
                'item_name' => 'Principal Investigator',
                'description' => '2 months summer salary',
                'year' => 1,
                'amount' => 15000,
                'quantity' => 1,
                'justification' => 'PI will lead all aspects of the project'
            ],
            [
                'category' => 'personnel',
                'item_name' => 'Graduate Student',
                'description' => 'Full-time PhD student',
                'year' => 1,
                'amount' => 30000,
                'quantity' => 2,
                'justification' => 'Will conduct experimental work and data analysis'
            ],
            [
                'category' => 'equipment',
                'item_name' => 'Laboratory Equipment',
                'description' => 'Specialized research equipment',
                'year' => 1,
                'amount' => 20000,
                'quantity' => 1,
                'justification' => 'Required for experimental research'
            ]
        ];
    }

    // Helper function to parse plain text into budget items
    private function parsePlainTextToBudgetItems($text) {
        // Simple fallback implementation - creates basic budget items from text
        return [
            [
                'category' => 'personnel',
                'item_name' => 'Principal Investigator',
                'description' => 'Based on AI response',
                'year' => 1,
                'amount' => 15000,
                'quantity' => 1,
                'justification' => 'Project leadership'
            ],
            [
                'category' => 'other',
                'item_name' => 'General Expenses',
                'description' => 'AI generated content',
                'year' => 1,
                'amount' => 10000,
                'quantity' => 1,
                'justification' => 'AI response: ' . substr($text, 0, 100) . '...'
            ]
        ];
    }
}
?>