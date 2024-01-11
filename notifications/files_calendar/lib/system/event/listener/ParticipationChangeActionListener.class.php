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
 */
class ParticipationChangeActionListener implements IParameterizedEventListener {
	/**
	 * @see	wcf\system\event\IEventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
        $actionName = $eventObj->getActionName();
        $param = $eventObj->getParameters();
        
        if ($actionName !== 'save' || $eventObj->eventDateParticipation === null) return;
        
        $eventDate = $eventObj->getObjects()[0]->getDecoratedObject();
        $prevDecision = $eventObj->eventDateParticipation->decision;
        $eventName = null;
        
        if (!empty($param['decision'])) {
            if ($prevDecision === 'yes' && $param['decision'] !== 'yes') {
                $eventName = 'participationUnregister';
            } else if ($prevDecision === 'maybe' && $param['decision'] === 'no') {
                $eventName = 'participationMaybeUnregister';
            }
            
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
