<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\users;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

class ShowTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
        ]);
    }

    private function forbidGuestsFromSeeingForum()
    {
        $this->database()->table('group_permission')->where('permission', 'viewForum')->where('group_id', 2)->delete();
    }

    private function forbidMembersFromSearchingUsers()
    {
        $this->database()->table('group_permission')->where('permission', 'searchUsers')->where('group_id', 3)->delete();
    }

    /**
     * @test
     */
    public function admin_can_see_user()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function admin_can_see_user_via_slug()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/normal', [
                'authenticatedAs' => 1,
            ])->withQueryParams([
                'bySlug' => true
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function guest_cant_see_user_by_id_default()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/2')
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function guest_can_see_user_by_slug_by_default()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/normal')->withQueryParams([
                'bySlug' => true
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function guest_can_see_user_by_id_if_allowed()
    {
        $this->prepareDatabase([
            'group_permission' => [
                ['permission' => 'viewUserList', 'group_id' => 2],
            ]
        ]);

        $response = $this->send(
            $this->request('GET', '/api/users/2')
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function guest_cant_see_user_by_slug_if_blocked()
    {
        $this->forbidGuestsFromSeeingForum();

        $response = $this->send(
            $this->request('GET', '/api/users/normal')->withQueryParams([
                'bySlug' => true
            ])
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function user_can_see_themselves()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/2', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function user_can_see_themselves_via_slug()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/normal', [
                'authenticatedAs' => 2,
            ])->withQueryParams([
                'bySlug' => true
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function user_can_see_others_by_default()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/1', [
                'authenticatedAs' => 2,
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function user_can_see_others_by_default_via_slug()
    {
        $response = $this->send(
            $this->request('GET', '/api/users/admin', [
                'authenticatedAs' => 2,
            ])->withQueryParams([
                'bySlug' => true
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function user_can_still_see_others_via_slug_even_if_cant_search()
    {
        $this->forbidMembersFromSearchingUsers();

        $response = $this->send(
            $this->request('GET', '/api/users/admin', [
                'authenticatedAs' => 2,
            ])->withQueryParams([
                'bySlug' => true
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }
}
