<?php

namespace App\Http\Controllers;

use App\Models\Ping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class PingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Display a listing of all pings.
     */
    public function getAllPings()
    {
        $pings = Ping::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        return response()->json($pings);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'website_address' => [
                'required',
                'string',
                'max:255',
                'regex:/^[^\s]+\.[^\s]+$/',
            ],
        ], [
            'website_address.regex' => 'The website address must include a dot and contain no spaces, for example google.com.',
        ]);

        $siteUrl = $data['website_address'];

        if (! preg_match('/^https?:\/\//i', $siteUrl)) {
            $siteUrl = 'https://'.$siteUrl;
        }

        $statusCode = null;

        try {
            $response = Http::timeout(5)->get($siteUrl);
            $statusCode = $response->status();
        } catch (\Throwable $exception) {
            $statusCode = null;
        }

        $ping = Ping::create([
            'user_id' => Auth::id(),
            'site_name' => $data['site_name'],
            'website_address' => $data['website_address'],
            'status_code' => $statusCode,
        ]);

        return response()->json($ping, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ping $ping)
    {
        abort_if($ping->user_id !== Auth::id(), 403);

        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'website_address' => [
                'required',
                'string',
                'max:255',
                'regex:/^[^\s]+\.[^\s]+$/',
            ],
        ], [
            'website_address.regex' => 'The website address must include a dot and contain no spaces, for example google.com.',
        ]);

        $siteUrl = $data['website_address'];

        if (! preg_match('/^https?:\/\//i', $siteUrl)) {
            $siteUrl = 'https://'.$siteUrl;
        }

        $statusCode = null;

        try {
            $response = Http::timeout(5)->get($siteUrl);
            $statusCode = $response->status();
        } catch (\Throwable $exception) {
            $statusCode = null;
        }

        $ping->update([
            'site_name' => $data['site_name'],
            'website_address' => $data['website_address'],
            'status_code' => $statusCode,
        ]);

        return response()->json($ping);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ping $ping)
    {
        abort_if($ping->user_id !== Auth::id(), 403);

        $ping->delete();

        return response()->noContent();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }
}
