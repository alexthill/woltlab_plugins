<?php

namespace calendar\system\event\listener;

use calendar\system\user\notification\object\EventUserNotificationObject;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\WCF;

/**
 * Checks whether the registration for a calendar event is opened and sends notifications
 * 
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 */
class RegistrationOpensActionListener implements IParameterizedEventListener {

    /**
     * @see wcf\system\event\IEventListener::execute()
     */
    public function execute($eventObj, $className, $eventName, array &$parameters) {
        $actionName = $eventObj->getActionName();
        if ($actionName == 'triggerPublication' || $actionName == 'update') {  
            $eventObject = $eventObj->getObjects()[0]->getDecoratedObject();
            $param = $eventObj->getParameters();
            if (($actionName == 'update' && $param['data']['enableParticipation'] && !$eventObject->enableParticipation) ||
                ($actionName == 'triggerPublication' && $eventObject->enableParticipation)
            ) {
                $recipientIDs = $this->getRecipientIDs();
                UserNotificationHandler::getInstance()->fireEvent(
                    'registrationOpens',
                    'com.woltlab.calendar.event',
                    new EventUserNotificationObject($eventObject),
                    $recipientIDs,
                );
            }
        }
    }
    
    private function getRecipientIDs() {
        $sql = 'SELECT userID
                FROM   wcf' . WCF_N . '_user
                WHERE  lastActivityTime > ?
                AND userID != ' . WCF::getUser()->userID;
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([TIME_NOW - 60*60*24*30]);

        $recipientIDs = [];
        while ($row = $statement->fetchArray()) {
            $recipientIDs[] = $row['userID'];
        }
        
        return $recipientIDs;
    }
}
