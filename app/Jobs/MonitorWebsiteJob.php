<?php

namespace App\Jobs;

use App\Models\Website;
use App\Services\WebsiteMonitorService;
use Illuminate\Bus\Queueable;
use Illuminate\Bus\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class MonitorWebsiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Website $website)
    {
    }

    public function handle(WebsiteMonitorService $monitor): void
    {
        $monitor->check($this->website);
    }
}
