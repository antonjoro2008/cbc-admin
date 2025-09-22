<?php

namespace App\Http\Controllers;

use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SmsController extends Controller
{
    use SmsService;

    /**
     * Send a test SMS
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendTestSms(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|string',
            'message' => 'required|string|max:160',
            'from' => 'nullable|string|max:11'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate SMS configuration
        if (!$this->validateSmsConfig()) {
            return response()->json([
                'success' => false,
                'message' => 'SMS configuration is incomplete. Please check your .env file.'
            ], 500);
        }

        $result = $this->sendSms(
            $request->input('to'),
            $request->input('message'),
            $request->input('from')
        );

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    /**
     * Send SMS to a specific user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendSmsToUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'message' => 'required|string|max:160',
            'from' => 'nullable|string|max:11'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = \App\Models\User::find($request->input('user_id'));
        
        if (!$user || !$user->phone) {
            return response()->json([
                'success' => false,
                'message' => 'User not found or phone number not available'
            ], 404);
        }

        $result = $this->sendSms(
            $user->phone,
            $request->input('message'),
            $request->input('from')
        );

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    /**
     * Send bulk SMS
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendBulkSms(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'required|string',
            'message' => 'required|string|max:160',
            'from' => 'nullable|string|max:11'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $results = $this->sendBulkSms(
            $request->input('recipients'),
            $request->input('message'),
            $request->input('from')
        );

        $successCount = collect($results)->where('success', true)->count();
        $totalCount = count($results);

        return response()->json([
            'success' => $successCount > 0,
            'message' => "Sent {$successCount} out of {$totalCount} SMS messages",
            'results' => $results,
            'summary' => [
                'total' => $totalCount,
                'successful' => $successCount,
                'failed' => $totalCount - $successCount
            ]
        ]);
    }

    /**
     * Test SMS configuration
     *
     * @return JsonResponse
     */
    public function testConfig(): JsonResponse
    {
        $isValid = $this->validateSmsConfig();
        
        return response()->json([
            'success' => $isValid,
            'message' => $isValid ? 'SMS configuration is valid' : 'SMS configuration is invalid',
            'config' => [
                'username' => config('sms.username') ? 'Set' : 'Not set',
                'password' => config('sms.password') ? 'Set' : 'Not set',
                'base_url' => config('sms.base_url'),
                'from' => config('sms.from')
            ]
        ]);
    }
}
