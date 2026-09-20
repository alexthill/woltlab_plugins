<?php

namespace calendar\system\event\listener;

use calendar\system\user\notification\object\EventDateParticipationUserNotificationObject;
use calendar\system\user\notification\object\EventUserNotificationObject;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\event\listener\IParameterizedEventListener;

/**
 * Checks whether someone unsubscribes from a calendar event and sends notifications to the author
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 * @package com.alexthill.notifications
 */
class ParticipationChangeActionListener implements IParameterizedEventListener {
    /**
     * @see wcf\system\event\IEventListener::execute()
     */
    public function execute($eventObj, $className, $eventName, array &$parameters) {
        $actionName = $eventObj->getActionName();
        $param = $eventObj->getParameters();

        if ($actionName !== 'save' || $eventObj->eventDateParticipation === null) return;

        $eventDate = $eventObj->getObjects()[0]->getDecoratedObject();
        $prevDecision = $eventObj->eventDateParticipation->decision;
        
        //  prev \ curr
        // |        | no sel | yes    | maybe  | no     |
        // | ------ | ------ | ------ | ------ | ------ |
        // | no sel | /      | re 🟢  | mre ✅ | dc     |
        // | yes    | ur  🟠 | /      | ?   🟠 | ur  ✅ |
        // | maybe  | mur 🟠 | re 🟢  | /      | mur ✅ |
        // | no     | dc     | re 🟢  | mre ✅ | /      |
        //
        // ✅ notification implemented
        // 🟢 notification handled by woltlab
        // 🟠 notification could be improved
        // ❗ notification missing
        // dc = don't care, ur = unregister, mur = maybe unregister, re = register, mre = maybe register
        
        if (!empty($param['decision'])) {
            $eventName = match ($param['decision']) {
                'maybe' => 'participationMaybeRegister',
                'no' => match($prevDecision) {
                    'yes' => 'participationUnregister',
                    'maybe' => 'participationMaybeUnregister',
                    default => null,
                },
                default => null,
            };

            if ($eventName !== null) {
                UserNotificationHandler::getInstance()->fireEvent(
                    $eventName,
                    'com.woltlab.calendar.event.date.participation',
                    new EventDateParticipationUserNotificationObject($eventObj->eventDateParticipation),
                    [$eventDate->getUserID()],
                );
            }
        } else if ($prevDecision === 'yes' || $prevDecision === 'maybe') {
            UserNotificationHandler::getInstance()->fireEvent(
                'participationUnregisterNoSelection',
                'com.woltlab.calendar.event',
                new EventUserNotificationObject($eventDate->getEvent()),
                [$eventDate->getUserID()],
            );
        }
    }
}
