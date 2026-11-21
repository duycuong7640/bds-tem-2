<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebsiteLicenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_license_gates_both_frontend_and_admin(): void
    {
        $pair = sodium_crypto_sign_keypair();
        $key = str_repeat('a', 64);
        config([
            'app.url' => 'https://customer.example.com',
            'license.required' => true,
            'license.issuer' => 'https://webfx.example.com',
            'license.template' => 'den-an',
            'license.key' => $key,
            'license.public_key' => base64_encode(sodium_crypto_sign_publickey($pair)),
        ]);

        $endpoint = 'https://webfx.example.com/api/v1/licenses/check';
        $payload = base64_encode(json_encode([
            'type' => 'webfx-license-v1',
            'domain' => 'customer.example.com',
            'template' => 'den-an',
            'key_hash' => hash('sha256', $key),
            'expires_at' => now()->addMinutes(9)->timestamp,
        ], JSON_THROW_ON_ERROR));
        $valid = [
            'payload' => $payload,
            'signature' => base64_encode(sodium_crypto_sign_detached($payload, sodium_crypto_sign_secretkey($pair))),
        ];
        Http::fake(['https://webfx.example.com/api/v1/template-brand' => Http::response(['logo' => null, 'copyright' => null]), $endpoint => Http::sequence()
            ->push(array_replace($valid, ['signature' => base64_encode(str_repeat('x', 64))]))
            ->push($valid)->pushStatus(503)->pushStatus(403)]);

        $this->get('https://other.example.com/')->assertStatus(503);
        Http::assertNothingSent();
        $this->get('https://customer.example.com/admin/login')->assertStatus(503);
        $this->travel(61)->seconds();
        $this->get('https://customer.example.com/admin/login')->assertOk();
        $this->get('https://customer.example.com/admin/login')->assertOk();
        $this->get('https://customer.example.com/')->assertOk();
        $this->assertCount(2, Http::recorded(fn ($request) => $request->url() === $endpoint));
        $this->travel(6)->minutes();
        $this->get('https://customer.example.com/admin/login')->assertOk();
        $this->travel(61)->seconds();
        $this->get('https://customer.example.com/')->assertStatus(503);
        $this->get('https://customer.example.com/admin/login')->assertStatus(503);
        $this->get('/up')->assertOk();
    }
}
