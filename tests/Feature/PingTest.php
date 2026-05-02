<?php

use App\Models\Ping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated users can store pings', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->postJson(route('pings.store'), [
        'site_name' => 'Google',
        'website_address' => 'www.google.com',
    ]);

    $response->assertCreated();
    $response->assertJson([
        'site_name' => 'Google',
        'website_address' => 'www.google.com',
        'user_id' => $user->id,
    ]);

    $this->assertDatabaseHas('pings', [
        'user_id' => $user->id,
        'site_name' => 'Google',
        'website_address' => 'www.google.com',
    ]);
});

test('authenticated users can fetch their pings', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Ping::create([
        'user_id' => $user->id,
        'site_name' => 'Local',
        'website_address' => 'local.test',
    ]);

    Ping::create([
        'user_id' => $otherUser->id,
        'site_name' => 'Other',
        'website_address' => 'other.test',
    ]);

    $this->actingAs($user);

    $response = $this->getJson(route('pings.all'));

    $response->assertOk();
    $response->assertJsonCount(1);
    $response->assertJsonFragment([
        'site_name' => 'Local',
        'website_address' => 'local.test',
    ]);
});
