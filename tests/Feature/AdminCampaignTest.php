<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminCampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_template_renders_and_caches_the_webfx_campaign(): void
    {
        config(['license.issuer' => 'https://webfx.example', 'license.required' => false]);
        $image = 'https://webfx.example/media/example.png';
        Http::preventStrayRequests();
        Http::fake([
            'https://webfx.example/api/v1/template-brand' => Http::response(['logo' => null, 'copyright' => null]),
            'https://webfx.example/api/v1/admin-campaign' => Http::response([
                'image' => $image,
                'links' => [
                    ['text' => 'Ưu đãi <b>tháng này</b>', 'url' => 'https://example.com/sale'],
                    ['text' => 'Dịch vụ WebFX', 'url' => 'https://example.com/design'],
                    ['text' => 'Link không hợp lệ', 'url' => 'javascript:alert(1)'],
                ],
            ]),
        ]);

        $this->get('/')->assertOk()->assertDontSee('Dịch vụ WebFX');
        $campaign = fn ($request) => str_contains($request->url(), '/admin-campaign');
        Http::assertNotSent($campaign);

        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()
            ->assertSeeInOrder(['</nav>', $image, '</main>', 'Ưu đãi &lt;b&gt;tháng này&lt;/b&gt;', 'Dịch vụ WebFX'], false)
            ->assertDontSee('Link không hợp lệ');
        $this->get('/admin/dashboard')->assertOk()->assertSee('Dịch vụ WebFX');
        $this->assertCount(1, Http::recorded($campaign));
        Http::assertSent(fn ($request) => ! $request->hasHeader('Authorization'));

        $this->travel(6)->minutes();
        Http::fake(['https://webfx.example/api/v1/admin-campaign' => Http::failedConnection()]);
        $this->get('/admin/dashboard')->assertOk()->assertDontSee('Dịch vụ WebFX');
    }
}
