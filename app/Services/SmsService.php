<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

trait SmsService
{
    /**
     * Send SMS using the SMS provider API
     *
     * @param string $to Phone number in E.164 format (e.g., 254726498973)
     * @param string $message The SMS message to send
     * @param string|null $from The sender name (optional, defaults to config value)
     * @return array Response from SMS API
     * @throws Exception
     */
    public function sendSms(string $to, string $message, ?string $from = null): array
    {
        try {
            // Validate phone number format
            $to = $this->formatPhoneNumber($to);
            
            // Get configuration
            $username = config('sms.username');
            $password = config('sms.password');
            $baseUrl = config('sms.base_url');
            $from = $from ?? config('sms.from', 'InfoSMS');
            
            if (!$username || !$password || !$baseUrl) {
                throw new Exception('SMS configuration is incomplete. Please check your .env file.');
            }
            
            // Create authorization header using Base64 encoding (without line breaks for HTTP headers)
            $authString = base64_encode($username . ':' . $password);
            $authorization = 'Basic ' . $authString;
            
            // Prepare request data
            $requestData = [
                'from' => $from,
                'to' => $to,
                'text' => $message
            ];
            
            // Make HTTP request
            $response = Http::withHeaders([
                'Authorization' => $authorization,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($baseUrl . '/sms/1/text/single', $requestData);
            
            // Log the request for debugging
            Log::info('SMS Request', [
                'url' => $baseUrl . '/sms/1/text/single',
                'to' => $to,
                'from' => $from,
                'message' => $message,
                'status' => $response->status()
            ]);
            
            // Handle response
            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('SMS Sent Successfully', [
                    'to' => $to,
                    'response' => $responseData
                ]);
                
                return [
                    'success' => true,
                    'status' => $response->status(),
                    'data' => $responseData,
                    'message' => 'SMS sent successfully'
                ];
            } else {
                $errorData = $response->json();
                Log::error('SMS Send Failed', [
                    'to' => $to,
                    'status' => $response->status(),
                    'error' => $errorData
                ]);
                
                return [
                    'success' => false,
                    'status' => $response->status(),
                    'error' => $errorData,
                    'message' => 'Failed to send SMS'
                ];
            }
            
        } catch (Exception $e) {
            Log::error('SMS Service Exception', [
                'to' => $to,
                'message' => $message,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'SMS service error occurred'
            ];
        }
    }
    
    /**
     * Format phone number to E.164 format
     *
     * @param string $phoneNumber
     * @return string
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If it starts with 0, replace with country code (assuming Kenya +254)
        if (str_starts_with($phoneNumber, '0')) {
            $phoneNumber = '254' . substr($phoneNumber, 1);
        }
        
        // If it doesn't start with country code, add it (assuming Kenya +254)
        if (!str_starts_with($phoneNumber, '254')) {
            $phoneNumber = '254' . $phoneNumber;
        }
        
        return $phoneNumber;
    }
    
    /**
     * Send bulk SMS to multiple recipients
     *
     * @param array $recipients Array of phone numbers
     * @param string $message The SMS message to send
     * @param string|null $from The sender name (optional)
     * @return array Results for each recipient
     */
    public function sendBulkSms(array $recipients, string $message, ?string $from = null): array
    {
        $results = [];
        
        foreach ($recipients as $recipient) {
            $results[$recipient] = $this->sendSms($recipient, $message, $from);
        }
        
        return $results;
    }
    
    /**
     * Validate SMS configuration
     *
     * @return bool
     */
    public function validateSmsConfig(): bool
    {
        $username = config('sms.username');
        $password = config('sms.password');
        $baseUrl = config('sms.base_url');
        
        return !empty($username) && !empty($password) && !empty($baseUrl);
    }
    
}
