<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Forum\Auth\SsoDriverInterface;
use Flarum\Forum\Auth\SsoResponse;
use Flarum\Http\Middleware\AuthenticateWithHeader;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Tests\integration\BuildsHttpRequests;
use Flarum\Tests\integration\RetrievesAuthorizedUsers;
use Flarum\Tests\integration\TestCase;
use Flarum\User\User;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthSsoTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use BuildsHttpRequests;

    protected $settings;
    protected const LOGGED_IN_RESPONSE = '<script>window.close(); window.opener.app.authenticationComplete({"loggedIn":true});</script>';
    protected const TEST_SUCCESS_RESPONSE = '<script>window.close(); window.opener.app.authenticationComplete({"testSuccess":true});</script>';
    protected const ALREADY_CLAIMED_RESPONSE = 'core.forum.auth.sso.errors.email_already_claimed';
    protected const ALREADY_LINKED_RESPONSE = 'core.forum.auth.sso.errors.provider_already_linked';
    protected const REGISTATION_DISABLED_RESPONSE = 'core.forum.auth.sso.errors.signups_disabled';
    protected const REGISTERING_RESPONSE_FRAGMENT = '"registering":true';

    protected function settings()
    {
        if (is_null($this->settings)) {
            $this->settings = $this->app()->getContainer()->make(SettingsRepositoryInterface::class);
        }

        return $this->settings;
    }

    protected function enableProvider()
    {
        $this->settings()->set('auth_driver_enabled_custom_sso', true);
    }

    protected function trustEmails()
    {
        $this->settings()->set('auth_driver_trust_emails_custom_sso', true);
    }

    protected function disableSignups()
    {
        $this->settings()->set('allow_sign_up', false);
    }

    protected function setUp()
    {
        parent::setUp();
        // Add so that we can use AuthenticatedTestCase (ie API Key Authentication) on forum routes.
        $this->extend((new Extend\Middleware('forum'))->add(AuthenticateWithHeader::class));
    }

    protected function tearDown()
    {
        $this->prepDb();
        $this->settings()->set('auth_driver_enabled_custom_sso', false);
        $this->settings()->set('auth_driver_trust_emails_custom_sso', false);
        User::find(1)->ssoProviders()->delete();
        User::find(2)->ssoProviders()->delete();
        $this->settings()->set('allow_sign_up', true);

        parent::tearDown();
    }

    protected function prepDb()
    {
        $this->prepareDatabase([
            'users' => [
                $this->adminUser(),
                $this->normalUser(),
            ],
        ]);
    }

    /**
     * @test
     */
    public function sso_provider_doesnt_exist_if_not_registered()
    {
        $response = $this->send(
            $this->request('GET', '/auth/custom_sso')
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function sso_provider_doesnt_exist_if_registered_but_not_enabled()
    {
        $this->extend((new Extend\Auth)->ssoDriver('custom_sso', CustomSsoProvider::class));

        $response = $this->send(
            $this->request('GET', '/auth/custom_sso')
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function sso_driver_can_return_http_redirect()
    {
        // This also tests that sso drivers can return both SsoResponse and HttpResponse objects.
        $this->extend((new Extend\Auth)->ssoDriver('custom_sso', CustomSsoProvider::class));

        $this->enableProvider();

        $response = $this->send(
            $this->request('GET', '/auth/custom_sso')->withAttribute('redirect', true)
        );

        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function sso_driver_logs_in_if_already_linked_to_user_who_is_logging_in()
    {
        // Also tests that identifier doesnt necessarily need to be an email.
        $this->extend((new Extend\Auth)->ssoDriver('custom_sso', CustomSsoProvider::class));

        $this->prepDb();

        $this->enableProvider();

        $user = User::find(1);

        $user->ssoProviders()->create(['provider' => 'custom_sso', 'identifier' => 'nonEmailIdentifier']);

        $response = $this->send(
            $this->requestAsUser(
                $this->request('GET', '/auth/custom_sso')->withAttribute('nonEmailIdentifier', true),
                1
            )
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::LOGGED_IN_RESPONSE, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function sso_driver_logs_in_if_already_linked_to_user_with_unauthenticated_user_logging_in()
    {
        $this->extend((new Extend\Auth)->ssoDriver('custom_sso', CustomSsoProvider::class));

        $this->enableProvider();

        $user = User::find(1);

        $user->ssoProviders()->create(['provider' => 'custom_sso', 'identifier' => 'nonEmailIdentifier']);

        $response = $this->send(
            $this->request('GET', '/auth/custom_sso')->withAttribute('nonEmailIdentifier', true)
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(self::LOGGED_IN_RESPONSE, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function return_test_if_admin_sso_into_already_linked_account_of_unrelated_user()
    {
        $this->extend((new Extend\Auth)->ssoDriver('custom_sso', CustomSsoProvider::class));

        $this->prepDb();

        $this->enableProvider();

        $user = User::find(2);

        $user->ssoProviders()->create(['provider' => 'custom_sso', 'identifier' => 'nonEmailIdentifier']);

        $response = $this->send(
            $this->requestAsUser(
                $this->request('GET', '/auth/custom_sso')->withAttribute('nonEmailIdentifier', true),
                1
            )
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(self::TEST_SUCCESS_RESPONSE, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function sso_driver_doesnt_log_into_account_of_unrelated_linked_user_if_not_admin()
    {
        $this->extend((new Extend\Auth)->ssoDriver('custom_sso', CustomSsoProvider::class));

        $this->prepDb();

        $this->enableProvider();

        $user = User::find(1);

        $user->ssoProviders()->create(['provider' => 'custom_sso', 'identifier' => 'nonEmailIdentifier']);

        $response = $this->send(
            $this->requestAsUser(
                $this->request('GET', '/auth/custom_sso')->withAttribute('nonEmailIdentifier', true),
                2
            )
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function links_provider_to_user_if_email_matches_and_user_logged_in()
    {
        $this->extend((new Extend\Auth)->ssoDriver('custom_sso', CustomSsoProvider::class));

        $this->prepDb();

        $this->enableProvider();

        $response = $this->send(
            $this->requestAsUser(
                $this->request('GET', '/auth/custom_sso')
                    ->withAttribute('adminIdentifier', true)
                    ->withAttribute('provideEmail', 'admin'),
                1
            )
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::LOGGED_IN_RESPONSE, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function links_provider_to_user_if_guest_and_trusted_when_email_matches()
    {
        $this->extend((new Extend\Auth)->ssoDriver('custom_sso', CustomSsoProvider::class));

        $this->enableProvider();
        $this->trustEmails();

        $response = $this->send(
            $this->request('GET', '/auth/custom_sso')
                ->withAttribute('adminIdentifier', true)
                ->withAttribute('provideEmail', 'admin')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::LOGGED_IN_RESPONSE, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function shows_error_if_guest_and_not_trusted_when_email_matches()
    {
        $this->extend((new Extend\Auth)->ssoDriver('custom_sso', CustomSsoProvider::class));

        $this->enableProvider();

        $response = $this->send(
            $this->request('GET', '/auth/custom_sso')
                ->withAttribute('adminIdentifier', true)
                ->withAttribute('provideEmail', 'admin')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::ALREADY_CLAIMED_RESPONSE, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function shows_error_if_unrelated_user_when_email_matches()
    {
        $this->extend((new Extend\Auth)->ssoDriver('custom_sso', CustomSsoProvider::class));

        $this->prepDb();

        $this->enableProvider();

        $response = $this->send(
            $this->requestAsUser(
                $this->request('GET', '/auth/custom_sso')
                    ->withAttribute('adminIdentifier', true)
                    ->withAttribute('provideEmail', 'admin'),
                2
            )
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::ALREADY_CLAIMED_RESPONSE, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function test_success_if_admin_when_email_matches()
    {
        $this->extend((new Extend\Auth)->ssoDriver('custom_sso', CustomSsoProvider::class));

        $this->prepDb();

        $this->enableProvider();

        $response = $this->send(
            $this->requestAsUser(
                $this->request('GET', '/auth/custom_sso')
                    ->withAttribute('adminIdentifier', true)
                    ->withAttribute('provideEmail', 'normal'),
                1
            )
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::TEST_SUCCESS_RESPONSE, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function link_new_provider_if_user_logged_in_but_no_email_provided()
    {
        $this->extend((new Extend\Auth)->ssoDriver('custom_sso', CustomSsoProvider::class));

        $this->prepDb();

        $this->enableProvider();

        $response = $this->send(
            $this->requestAsUser(
                $this->request('GET', '/auth/custom_sso')
                    ->withAttribute('adminIdentifier', true),
                1
            )
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::LOGGED_IN_RESPONSE, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function dont_double_link_two_identities_from_same_provider()
    {
        $this->extend((new Extend\Auth)->ssoDriver('custom_sso', CustomSsoProvider::class));

        $this->prepDb();

        $this->enableProvider();

        $user = User::find(1);

        $user->ssoProviders()->create(['provider' => 'custom_sso', 'identifier' => 'nonEmailIdentifier']);

        $response = $this->send(
            $this->requestAsUser(
                $this->request('GET', '/auth/custom_sso')
                    ->withAttribute('adminIdentifier', true),
                1
            )
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::ALREADY_LINKED_RESPONSE, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function wont_register_if_signups_disabled()
    {
        $this->extend((new Extend\Auth)->ssoDriver('custom_sso', CustomSsoProvider::class));

        $this->enableProvider();
        $this->disableSignups();

        $response = $this->send(
            $this->request('GET', '/auth/custom_sso')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains(self::REGISTATION_DISABLED_RESPONSE, $response->getBody()->getContents());
    }

    /**
     * @test
     */
    public function register_if_ssoresponse_returned_but_none_of_the_other_cases_met()
    {
        $this->extend((new Extend\Auth)->ssoDriver('custom_sso', CustomSsoProvider::class));

        $this->enableProvider();

        $response = $this->send(
            $this->request('GET', '/auth/custom_sso')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains(self::REGISTERING_RESPONSE_FRAGMENT, $response->getBody()->getContents());
    }
}

class CustomSsoProvider implements SsoDriverInterface
{
    public function meta(): array
    {
        return [];
    }

    public function sso(Request $request, SsoResponse $response)
    {
        if ($request->getAttribute('redirect')) {
            return new RedirectResponse('https://example.com');
        }

        if ($request->getAttribute('nonEmailIdentifier')) {
            $response->withIdentifier('nonEmailIdentifier');
        } elseif ($request->getAttribute('adminEmailIdentifier')) {
            $response->withIdentifier('admin@machine.local');
        } else {
            $response->withIdentifier('normal@machine.local');
        }

        if ($request->getAttribute('provideEmail') == 'admin') {
            $response->provideEmail('admin@machine.local');
        } elseif ($request->getAttribute('provideEmail') == 'normal') {
            $response->provideEmail('normal@machine.local');
        }

        $response->suggest('registering', true);

        return $response;
    }
}
