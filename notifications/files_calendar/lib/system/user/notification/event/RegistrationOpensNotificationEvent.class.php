<?php

namespace calendar\system\user\notification\event;

use calendar\system\user\notification\object\EventUserNotificationObject;
use wcf\system\user\notification\event\AbstractUserNotificationEvent;
use wcf\system\user\notification\event\ITestableUserNotificationEvent;

/**
 * Notification event for calendar event registration opens.
 * 
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 * @package com.alexthill.notifications
 *
 * @method  EventUserNotificationObject getUserNotificationObject()
 */
class RegistrationOpensNotificationEvent extends AbstractUserNotificationEvent implements ITestableUserNotificationEvent
{
    use TTestableEventUserNotificationEvent;

    /**
     * @inheritDoc
     */
    public function getTitle(): string {
        return $this->getLanguage()->get('calendar.event.registrationOpens.notification.title');
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): string {
        return $this->getLanguage()->getDynamicVariable('calendar.event.registrationOpens.notification.message', [
            'event' => $this->getUserNotificationObject(),
            'author' => $this->author,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getEmailMessage($notificationType = 'instant') {
        $eventDate = $this->getUserNotificationObject()->getFirstEventDate();

        return [
            'message-id' => 'com.woltlab.calendar.event/' . $this->getUserNotificationObject()->eventID,
            'template' => 'email_notification_event',
            'application' => 'calendar',
            'variables' => [
                'languageVariablePrefix' => 'calendar.event.registrationOpens.notification',
                'eventDate' => $eventDate,
                'author' => $this->author,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getLink(): string {
        return $this->getUserNotificationObject()->getLink();
    }

    /**
     * @inheritDoc
     */
    public function checkAccess() {
        return $this->getUserNotificationObject()->canRead();
    }
}
