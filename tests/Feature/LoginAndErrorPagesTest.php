<?php

namespace Tests\Feature;

use Illuminate\Auth\Middleware\Authenticate;
use Tests\TestCase;

class LoginAndErrorPagesTest extends TestCase
{
    public function test_login_page_shows_erp_sign_in(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Sign in', false);
        $response->assertSee('Sign in to Workspace', false);
        $response->assertSee('Staff Portal', false);
    }

    public function test_unknown_route_renders_branded_404(): void
    {
        $response = $this->get('/this-page-does-not-exist-sns');

        $response->assertNotFound();
        $response->assertSee('Page not found', false);
        $response->assertSee('ERP sign in', false);
    }

    public function test_guests_are_sent_to_login_for_workspace(): void
    {
        $response = $this->get('/workspace');

        $response->assertRedirect('/login');
    }

    public function test_logout_redirects_to_clean_login_url(): void
    {
        $response = $this
            ->withoutMiddleware([
                Authenticate::class,
            ])
            ->post('/logout');

        $response->assertRedirect('/login');
        $response->assertSessionHas('status');
        $this->assertSame(url('/login'), $response->headers->get('Location'));
    }
}
