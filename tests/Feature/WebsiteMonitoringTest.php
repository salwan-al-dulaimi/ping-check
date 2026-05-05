<?php

use App\Jobs\MonitorWebsiteJob;
use App\Mail\WebsiteOfflineNotification;
use App\Models\User;
use App\Models\Website;
use App\Services\WebsiteMonitorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('authenticated users can store monitored websites', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Http::fake([
        'https://www.example.com/*' => Http::response('', 200),
    ]);

    $response = $this->postJson(route('websites.store'), [
        'name' => 'Example',
        'url' => 'https://www.example.com',
        'check_interval' => 1,
    ]);

    $response->assertCreated();
    $response->assertJson([
        'name' => 'Example',
        'url' => 'https://www.example.com',
        'status' => 'online',
        'status_code' => 200,
    ]);

    $this->assertDatabaseHas('websites', [
        'user_id' => $user->id,
        'name' => 'Example',
        'url' => 'https://www.example.com',
        'status' => 'online',
        'status_code' => 200,
    ]);
});

test('authenticated users can retrieve their websites', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Website::create([
        'user_id' => $user->id,
        'name' => 'User Site',
        'url' => 'https://user.example.com',
        'check_interval' => 1,
        'status' => Website::STATUS_ONLINE,
    ]);

    Website::create([
        'user_id' => $otherUser->id,
        'name' => 'Other Site',
        'url' => 'https://other.example.com',
        'check_interval' => 1,
        'status' => Website::STATUS_ONLINE,
    ]);

    $this->actingAs($user);

    $response = $this->getJson(route('websites.all'));

    $response->assertOk();
    $response->assertJsonCount(1);
    $response->assertJsonFragment([
        'name' => 'User Site',
        'url' => 'https://user.example.com',
    ]);
});

test('monitor website service queues offline notifications only when status changes', function () {
    Mail::fake();
    Http::fake([
        'https://status.example.com/*' => Http::response('', 500),
    ]);

    $user = User::factory()->create();

    $website = Website::create([
        'user_id' => $user->id,
        'name' => 'Status Site',
        'url' => 'https://status.example.com',
        'check_interval' => 1,
        'status' => Website::STATUS_ONLINE,
    ]);

    app(WebsiteMonitorService::class)->check($website);

    Mail::assertQueued(WebsiteOfflineNotification::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});

test('monitor website dispatch job schedules due checks', function () {
    Queue::fake();

    $user = User::factory()->create();

    Website::create([
        'user_id' => $user->id,
        'name' => 'Due Site',
        'url' => 'https://due.example.com',
        'check_interval' => 1,
        'status' => Website::STATUS_ONLINE,
        'last_checked_at' => now()->subHours(2),
    ]);

    app(WebsiteMonitorService::class)->dispatchDueWebsiteChecks();

    Queue::assertPushed(MonitorWebsiteJob::class);
});
