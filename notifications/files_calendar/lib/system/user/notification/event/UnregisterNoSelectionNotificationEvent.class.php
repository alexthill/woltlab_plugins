<?php

namespace calendar\system\user\notification\event;

use calendar\system\cache\runtime\EventDateRuntimeCache;
use calendar\system\cache\runtime\EventRuntimeCache;
use calendar\system\user\notification\object\EventDateParticipationUserNotificationObject;
use wcf\system\user\notification\event\AbstractUserNotificationEvent;

/**
 * Notification event for when an event participant changes to no selection.
 * 
 * @author  Xaver
 * @license MIT License <https://mit-license.org/>
 * @package com.xaver.notifications
 *
 * @method  EventUserNotificationObject getUserNotificationObject()
 */
class UnregisterNoSelectionNotificationEvent extends AbstractUserNotificationEvent {
    /**
     * @inheritDoc
     */
    protected $stackable = true;

    /**
     * @inheritDoc
     */
    public function checkAccess(): bool {
        return $this->getUserNotificationObject()->canRead();
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
        return $this->getUserNotificationObject()->getLink();
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): string {
        $count = $this->notification->timesTriggered;
        if ($count > 1) {
            return $this->getLanguage()->getDynamicVariable('calendar.event.unregisterNoSelection.notification.message.stacked', [
                'count' => $count,
                'event' => $this->getUserNotificationObject(),
            ]);
        }

        return $this->getLanguage()->getDynamicVariable('calendar.event.unregisterNoSelection.notification.message', [
            'event' => $this->getUserNotificationObject(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): string {
        $count = $this->notification->timesTriggered;
        if ($count > 1) {
            return $this->getLanguage()->getDynamicVariable(
                'calendar.event.unregister.notification.title.stacked',
                ['count' => $count],
            );
        }

        return $this->getLanguage()->get('calendar.event.unregister.notification.title');
    }
}
