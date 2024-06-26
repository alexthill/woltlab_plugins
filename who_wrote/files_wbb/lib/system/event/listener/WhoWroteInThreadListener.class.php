<?php

namespace wbb\system\event\listener;

use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\WCF;

/**
 * Event listener to add variables to whoWrote template
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 * @package com.alexthill.notifications
 */
class WhoWroteInThreadListener implements IParameterizedEventListener {
    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters) {
        $sql = "SELECT users.username AS username, userList.userID AS userID, userList.count AS count
            FROM (
                SELECT post.userID, COUNT(*) AS count
                FROM wbb" . WCF_N . "_post post
                WHERE post.threadID = ? AND post.isDeleted = 0 AND post.isDisabled = 0
                GROUP BY post.userID
            ) userList
            LEFT JOIN wcf" . WCF_N . "_user users ON users.userID = userList.userID
            ORDER BY count DESC, username DESC";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$eventObj->threadID]);

        $whoWrote = [];
        $whoWroteByName = [];
        while ($row = $statement->fetchArray()) {
            $whoWrote[] = $row;
            if ($row['userID'] !== 0) {
                $whoWroteByName[$row['username']] = $row['userID'];
            }
        }
        ksort($whoWroteByName, SORT_NATURAL | SORT_FLAG_CASE);

        WCF::getTPL()->assign([
            'whoWrote' => $whoWrote,
            'whoWroteByName' => $whoWroteByName
        ]);
    }
}
