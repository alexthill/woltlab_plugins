<?php

namespace calendar\system\user\notification\event;

use calendar\system\cache\runtime\EventDateRuntimeCache;
use calendar\system\cache\runtime\EventRuntimeCache;
use calendar\system\user\notification\object\EventDateParticipationUserNotificationObject;
use wcf\data\user\UserProfile;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\user\notification\event\AbstractSharedUserNotificationEvent;
use wcf\system\user\notification\event\ITestableUserNotificationEvent;
use wcf\system\user\notification\event\TTestableUserNotificationEvent;


/**
 * Notification event for when an event participant changes to not participating.
 * 
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 * @package com.alexthill.notifications
 *
 * @method  EventDateParticipationUserNotificationObject  getUserNotificationObject()
 */
class UnregisterNotificationEvent extends AbstractSharedUserNotificationEvent implements
    ITestableUserNotificationEvent
{
    use TTestableEventDateRelatedUserNotificationEvent;
    use TTestableUserNotificationEvent;

    /**
     * @inheritDoc
     */
    protected $stackable = true;

    /**
     * @inheritDoc
     */
    protected function prepare() {
        EventDateRuntimeCache::getInstance()->cacheObjectID($this->getUserNotificationObject()->eventDateID);
        UserProfileRuntimeCache::getInstance()->cacheObjectID($this->getUserNotificationObject()->userID);
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): string {
        $count = \count($this->getAuthors());
        if ($count > 1) {
            return $this->getLanguage()->getDynamicVariable(
                'calendar.event.unregister.notification.title.stacked',
                [
                    'count' => $count,
                    'timesTriggered' => $this->notification->timesTriggered,
                ]
            );
        }

        return $this->getLanguage()->get('calendar.event.unregister.notification.title');
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): string {
        $eventDate = EventDateRuntimeCache::getInstance()->getObject($this->getUserNotificationObject()->eventDateID);

        $authors = \array_values($this->getAuthors());
        $count = \count($authors);

        if ($count > 1) {
            return $this->getLanguage()->getDynamicVariable(
                'calendar.event.unregister.notification.message.stacked',
                [
                    'author' => $this->author,
                    'authors' => $authors,
                    'count' => $count,
                    'others' => $count - 1,
                    'participation' => $this->userNotificationObject,
                    'eventDate' => $eventDate,
                ]
            );
        }

        return $this->getLanguage()->getDynamicVariable('calendar.event.unregister.notification.message', [
            'participation' => $this->userNotificationObject,
            'author' => $this->author,
            'eventDate' => $eventDate,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function supportsEmailNotification(): bool {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getLink(): string {
        return $this->getUserNotificationObject()->getURL();
    }

    /**
     * @inheritDoc
     */
    public function getEventHash(): string {
        return sha1($this->eventID . '-' . $this->getUserNotificationObject()->eventDateID);
    }

    /**
     * @inheritDoc
     */
    public function checkAccess(): bool {
        $eventDate = EventDateRuntimeCache::getInstance()->getObject($this->getUserNotificationObject()->eventDateID);

        return EventRuntimeCache::getInstance()->getObject($eventDate->eventID)->canRead();
    }

    /**
     * @inheritDoc
     */
    public static function getTestObjects(UserProfile $recipient, UserProfile $author) {
        return [
            new UnregisterNotificationEvent(self::createTestEventDateParticipation(
                $recipient,
                $author
            )),
        ];
    }
}
