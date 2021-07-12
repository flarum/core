<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Http;

use Flarum\Http\RouteCollection;
use Flarum\Testing\unit\TestCase;

class RouteCollectionTest extends TestCase
{
    /** @test */
    public function can_add_routes()
    {
        $routeCollection = (new RouteCollection)
            ->addRoute('GET', '/index', 'index', function () {
                echo 'index';
            })
            ->addRoute('DELETE', '/posts', 'forum.posts.delete', function () {
                echo 'delete posts';
            });

        $this->assertEquals('/index', $routeCollection->getPath('index'));
        $this->assertEquals('/posts', $routeCollection->getPath('forum.posts.delete'));
    }

    /** @test */
    public function can_add_routes_late()
    {
        $routeCollection = (new RouteCollection)->addRoute('GET', '/index', 'index', function () {
            echo 'index';
        });

        $this->assertEquals('/index', $routeCollection->getPath('index'));

        $routeCollection->addRoute('DELETE', '/posts', 'forum.posts.delete', function () {
            echo 'delete posts';
        });

        $this->assertEquals('/posts', $routeCollection->getPath('forum.posts.delete'));
    }

    /** @test */
    public function can_add_empty_parameters()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Route is missing argument for part user.');

        $routeCollection = (new RouteCollection)->addRoute('GET', '/user/{user}', 'user', function () {
            echo 'user';
        });

        $routeCollection->getPath('user', []);
    }
}
