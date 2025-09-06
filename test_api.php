<?php
/**
 * Simple API Test Script for CBC Admin
 * This script demonstrates how to interact with the API endpoints
 */

class CBCAdminAPITest
{
    private $baseUrl = 'http://localhost:8000/api';
    private $token = null;

    /**
     * Test student registration
     */
    public function testStudentRegistration()
    {
        echo "Testing Student Registration...\n";
        
        $data = [
            'name' => 'Test Student',
            'email' => 'student' . time() . '@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'institution_id' => 1,
            'grade_level' => 'Grade 8'
        ];

        $response = $this->makeRequest('POST', '/register', $data);
        $this->displayResponse($response);
        
        if (isset($response['data']['access_token'])) {
            $this->token = $response['data']['access_token'];
            echo "Token saved for further tests\n";
        }
        
        echo "\n";
    }

    /**
     * Test institution registration
     */
    public function testInstitutionRegistration()
    {
        echo "Testing Institution Registration...\n";
        
        $data = [
            'institution_name' => 'Test School ' . time(),
            'institution_email' => 'school' . time() . '@test.com',
            'institution_phone' => '+254700000000',
            'institution_address' => '123 Test St, Test City',
            'admin_name' => 'Test Admin',
            'admin_email' => 'admin' . time() . '@test.com',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123'
        ];

        $response = $this->makeRequest('POST', '/institution/register', $data);
        $this->displayResponse($response);
        echo "\n";
    }

    /**
     * Test login
     */
    public function testLogin()
    {
        echo "Testing Login...\n";
        
        $data = [
            'email' => 'student@test.com',
            'password' => 'password123'
        ];

        $response = $this->makeRequest('POST', '/login', $data);
        $this->displayResponse($response);
        
        if (isset($response['data']['access_token'])) {
            $this->token = $response['data']['access_token'];
            echo "Login successful, token saved\n";
        }
        
        echo "\n";
    }

    /**
     * Test dashboard (requires authentication)
     */
    public function testDashboard()
    {
        if (!$this->token) {
            echo "No token available, skipping dashboard test\n\n";
            return;
        }

        echo "Testing Dashboard...\n";
        
        $response = $this->makeRequest('GET', '/dashboard', null, true);
        $this->displayResponse($response);
        echo "\n";
    }

    /**
     * Test assessments list (requires authentication)
     */
    public function testAssessments()
    {
        if (!$this->token) {
            echo "No token available, skipping assessments test\n\n";
            return;
        }

        echo "Testing Assessments List...\n";
        
        $response = $this->makeRequest('GET', '/assessments', null, true);
        $this->displayResponse($response);
        echo "\n";
    }

    /**
     * Test token balance (requires authentication)
     */
    public function testTokenBalance()
    {
        if (!$this->token) {
            echo "No token available, skipping token balance test\n\n";
            return;
        }

        echo "Testing Token Balance...\n";
        
        $response = $this->makeRequest('GET', '/token-balance', null, true);
        $this->displayResponse($response);
        echo "\n";
    }

    /**
     * Test logout (requires authentication)
     */
    public function testLogout()
    {
        if (!$this->token) {
            echo "No token available, skipping logout test\n\n";
            return;
        }

        echo "Testing Logout...\n";
        
        $response = $this->makeRequest('POST', '/logout', null, true);
        $this->displayResponse($response);
        
        if ($response['success']) {
            $this->token = null;
            echo "Logged out successfully\n";
        }
        
        echo "\n";
    }

    /**
     * Make HTTP request to API
     */
    private function makeRequest($method, $endpoint, $data = null, $authenticated = false)
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        
        $headers = ['Content-Type: application/json'];
        if ($authenticated && $this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false) {
            return ['success' => false, 'message' => 'cURL error'];
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Invalid JSON response', 'raw' => $response];
        }
        
        return $decoded;
    }

    /**
     * Display API response in a readable format
     */
    private function displayResponse($response)
    {
        if (isset($response['success'])) {
            echo "Success: " . ($response['success'] ? 'Yes' : 'No') . "\n";
        }
        
        if (isset($response['message'])) {
            echo "Message: " . $response['message'] . "\n";
        }
        
        if (isset($response['errors'])) {
            echo "Errors:\n";
            foreach ($response['errors'] as $field => $errors) {
                echo "  $field: " . implode(', ', $errors) . "\n";
            }
        }
        
        if (isset($response['data'])) {
            echo "Data received: " . (is_array($response['data']) ? 'Yes' : 'No') . "\n";
        }
    }

    /**
     * Run all tests
     */
    public function runAllTests()
    {
        echo "=== CBC Admin API Test Suite ===\n\n";
        
        $this->testStudentRegistration();
        $this->testInstitutionRegistration();
        $this->testLogin();
        $this->testDashboard();
        $this->testAssessments();
        $this->testTokenBalance();
        $this->testLogout();
        
        echo "=== Test Suite Complete ===\n";
    }
}

// Run the tests if this file is executed directly
if (php_sapi_name() === 'cli') {
    $tester = new CBCAdminAPITest();
    $tester->runAllTests();
} else {
    echo "This script is designed to run from the command line.\n";
    echo "Usage: php test_api.php\n";
}
?>
