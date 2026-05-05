<?php

namespace App\Services;

use App\Jobs\MonitorWebsiteJob;
use App\Mail\WebsiteOfflineNotification;
use App\Models\Website;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Throwable;

class WebsiteMonitorService
{
    public function check(Website $website): Website
    {
        $start = microtime(true);
        $normalizedUrl = $this->normalizeUrl($website->url);
        $statusCode = null;
        $isOnline = false;
        $errorMessage = null;

        try {
            $response = Http::retry(2, 500)
                ->timeout(10)
                ->get($normalizedUrl);

            $statusCode = $response->status();
            $isOnline = $statusCode === 200;
        } catch (Throwable $exception) {
            $errorMessage = $exception->getMessage();
        }

        $responseTime = (int) round((microtime(true) - $start) * 1000);
        $newStatus = $isOnline ? Website::STATUS_ONLINE : Website::STATUS_OFFLINE;
        $previousStatus = $website->status ?? Website::STATUS_OFFLINE;

        $website->update([
            'url' => $normalizedUrl,
            'status' => $newStatus,
            'status_code' => $statusCode,
            'last_checked_at' => now(),
        ]);

        $website->logs()->create([
            'status_code' => $statusCode,
            'response_time' => $responseTime,
            'checked_at' => now(),
        ]);

        if ($previousStatus === Website::STATUS_ONLINE && $newStatus === Website::STATUS_OFFLINE) {
            Mail::to($website->user)->queue(new WebsiteOfflineNotification($website, $statusCode, $errorMessage));
        }

        return $website->fresh();
    }

    public function dispatchDueWebsiteChecks(): void
    {
        Website::with('user')
            ->get()
            ->filter(function (Website $website) {
                return $this->shouldCheck($website);
            })
            ->each(function (Website $website) {
                MonitorWebsiteJob::dispatch($website);
            });
    }

    protected function shouldCheck(Website $website): bool
    {
        if ($website->last_checked_at === null) {
            return true;
        }

        return now()->diffInSeconds($website->last_checked_at) >= $website->check_interval * 3600;
    }

    protected function normalizeUrl(string $url): string
    {
        return preg_match('/^https?:\/\//i', $url) ? $url : 'https://'.$url;
    }
}
