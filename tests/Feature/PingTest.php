<?php

use App\Models\Ping;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('authenticated users can store pings', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Http::fake([
        'https://www.google.com/*' => Http::response('', 200),
    ]);

    $response = $this->postJson(route('pings.store'), [
        'site_name' => 'Google',
        'website_address' => 'www.google.com',
    ]);

    $response->assertCreated();
    $response->assertJson([
        'site_name' => 'Google',
        'website_address' => 'www.google.com',
        'user_id' => $user->id,
        'status_code' => 200,
    ]);

    $this->assertDatabaseHas('pings', [
        'user_id' => $user->id,
        'site_name' => 'Google',
        'website_address' => 'www.google.com',
        'status_code' => 200,
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

test('authenticated users can update a ping', function () {
    $user = User::factory()->create();

    $ping = Ping::create([
        'user_id' => $user->id,
        'site_name' => 'Local',
        'website_address' => 'local.test',
        'status_code' => 200,
    ]);

    $this->actingAs($user);

    Http::fake([
        'https://edited.example.com/*' => Http::response('', 200),
    ]);

    $response = $this->patchJson(route('pings.update', $ping), [
        'site_name' => 'Edited',
        'website_address' => 'edited.example.com',
    ]);

    $response->assertOk();
    $response->assertJson([
        'site_name' => 'Edited',
        'website_address' => 'edited.example.com',
        'status_code' => 200,
    ]);

    $this->assertDatabaseHas('pings', [
        'id' => $ping->id,
        'site_name' => 'Edited',
        'website_address' => 'edited.example.com',
        'status_code' => 200,
    ]);
});

test('authenticated users can delete a ping', function () {
    $user = User::factory()->create();

    $ping = Ping::create([
        'user_id' => $user->id,
        'site_name' => 'Local',
        'website_address' => 'local.test',
        'status_code' => 200,
    ]);

    $this->actingAs($user);

    $response = $this->deleteJson(route('pings.destroy', $ping));

    $response->assertNoContent();

    $this->assertDatabaseMissing('pings', [
        'id' => $ping->id,
    ]);
});
