<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\Driver\NotificationDriverInterface;
use Flarum\Notification\Notification;
use Flarum\Notification\NotificationSyncer;
use Flarum\Tests\integration\TestCase;

class NotificationTest extends TestCase
{
    /**
     * @test
     */
    public function notification_type_doesnt_exist_by_default()
    {
        $this->assertArrayNotHasKey('customNotificationType', Notification::getSubjectModels());
    }

    /**
     * @test
     */
    public function notification_driver_doesnt_exist_by_default()
    {
        $this->assertArrayNotHasKey('customNotificationDriver', NotificationSyncer::getNotificationDrivers());
    }

    /**
     * @test
     */
    public function notification_type_exists_if_added()
    {
        $this->extend((new Extend\Notification)->type(
            CustomNotificationType::class,
            'customNotificationTypeSerializer'
        ));

        $this->app();

        $this->assertArrayHasKey('customNotificationType', Notification::getSubjectModels());
    }

    /**
     * @test
     */
    public function notification_driver_exists_if_added()
    {
        $this->extend((new Extend\Notification())->driver(
            'customNotificationDriver',
            CustomNotificationDriver::class
        ));

        $this->app();

        $this->assertArrayHasKey('customNotificationDriver', NotificationSyncer::getNotificationDrivers());
    }

    /**
     * @test
     */
    public function notification_driver_default_types_exists_if_added()
    {
        $this->extend(
            (new Extend\Notification())
                ->type(CustomNotificationType::class, 'customSerializer')
                ->driver('customDriver', CustomNotificationDriver::class, [CustomNotificationType::class])
        );

        $this->app();


    }
}

class CustomNotificationType implements BlueprintInterface
{
    public function getFromUser()
    {
        // ...
    }

    public function getSubject()
    {
        // ...
    }

    public function getData()
    {
        // ...
    }

    public static function getType()
    {
        return 'customNotificationType';
    }

    public static function getSubjectModel()
    {
        return 'customNotificationTypeSubjectModel';
    }
}

class CustomNotificationDriver implements NotificationDriverInterface
{
    public function send(BlueprintInterface $blueprint, array $users): void
    {
        // ...
    }

    public function registerType(string $blueprintClass, bool $default): void
    {
        // ...
    }
}
