<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebsiteRequest;
use App\Models\Website;
use App\Services\WebsiteMonitorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebsiteController extends Controller
{
    public function all()
    {
        $websites = Website::forUser(Auth::id())
            ->orderByDesc('updated_at')
            ->get();

        return response()->json($websites);
    }

    public function store(WebsiteRequest $request, WebsiteMonitorService $monitorService)
    {
        $website = Website::create([
            'user_id' => Auth::id(),
            'name' => $request->input('name'),
            'url' => $request->input('url'),
            'check_interval' => $request->input('check_interval'),
            'status' => Website::STATUS_OFFLINE,
        ]);

        return response()->json($monitorService->check($website), 201);
    }

    public function update(WebsiteRequest $request, Website $website, WebsiteMonitorService $monitorService)
    {
        abort_if($website->user_id !== Auth::id(), 403);

        $website->update([
            'name' => $request->input('name'),
            'url' => $request->input('url'),
            'check_interval' => $request->input('check_interval'),
        ]);

        return response()->json($monitorService->check($website));
    }

    public function destroy(Website $website)
    {
        abort_if($website->user_id !== Auth::id(), 403);

        $website->delete();

        return response()->noContent();
    }
}
