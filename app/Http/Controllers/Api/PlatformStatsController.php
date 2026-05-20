<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PlatformStatsController extends Controller
{
    private const CACHE_KEY = 'api.platform_stats';

    private const CACHE_TTL_SECONDS = 300;

    /**
     * Public headline counts for the marketing site (skills-zone home page).
     * Returns raw platform totals; the frontend adds these to configured baselines.
     *
     * active_users: every registered account (all roles), not filtered by activity.
     * Label on the marketing site remains "Active Users".
     */
    public function index(): JsonResponse
    {
        $data = Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): array {
            $totalUsers = User::query()->count();

            return [
                'learners' => User::query()->where('user_type', 'student')->count(),
                'active_users' => $totalUsers,
                'total_users' => $totalUsers,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
