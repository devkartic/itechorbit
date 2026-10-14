<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\VerificationController;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class AdminAuthenticationViewsTest extends TestCase
{
    public function test_guest_authentication_routes_use_admin_templates()
    {
        foreach ([
            '/login' => 'login',
            '/register' => 'register',
            '/password/reset' => 'forgot',
        ] as $path => $view) {
            $this->get($path)->assertOk()->assertInertia(fn ($page) => $page->component('Auth')->where('mode', $view));
        }
    }

    public function test_password_reset_passes_token_and_email_to_admin_template()
    {
        $this->get('/password/reset/test-token?email=test%40example.com')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth')->where('token', 'test-token')->where('email', 'test@example.com'));
    }

    public function test_password_confirmation_uses_admin_template()
    {
        $this->actingAs(new User(['name' => 'Test User', 'email' => 'test@example.com']))
            ->get('/password/confirm')
            ->assertOk()->assertInertia(fn ($page) => $page->component('Auth')->where('mode', 'confirm'));
    }

    public function test_unverified_user_gets_admin_verification_view()
    {
        $request = Request::create('/email/verify');
        $request->setUserResolver(function () {
            return new User(['email' => 'test@example.com']);
        });

        $view = (new VerificationController)->show($request);

        $this->assertSame('Auth', $view->toResponse($request)->original->getData()['page']['component']);
    }

    public function test_verified_user_is_still_redirected_home()
    {
        $user = new User(['email' => 'test@example.com']);
        $user->email_verified_at = now();
        $request = Request::create('/email/verify');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = (new VerificationController)->show($request);

        $this->assertSame(url('/home'), $response->getTargetUrl());
    }

    public function test_login_validation_returns_errors_in_admin_template()
    {
        $this->from('/login')->post('/login', [])
            ->assertRedirect('/login')->assertSessionHasErrors(['email', 'password']);

        $this->get('/login')->assertOk()->assertInertia(fn ($page) => $page->component('Auth')->where('errors.email', 'The email field is required.')->where('errors.password', 'The password field is required.'));
    }
}
