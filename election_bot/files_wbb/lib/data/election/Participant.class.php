<?php

namespace wbb\data\election;

use wbb\data\election\utils\ParticipantListLine;
use wcf\data\DatabaseObject;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Represents a participant in the elections of a thread
 *
 * @author  Xaver
 * @license MIT License <https://mit-license.org/>
 * @package com.xaver.election_bot
 *
 * @property-read   int         $participantID  unique id of the participant
 * @property-read   int         $threadID       id of the thread
 * @property-read   string      $name           name of participant
 * @property-read   string      $extra          some extra text
 * @property-read   int         $color          color to mark the participant
 * @property-read   bool        $active         whether the participant is activly participating
 */
class Participant extends DatabaseObject {

    public const ALIAS_TABLE_NAME = 'wbb1_election_participant_alias';

    const COLOR_OPTIONS = [
        1 => 'wcf.ckeditor.marker.success',
        2 => 'wcf.ckeditor.marker.warning',
        3 => 'wcf.ckeditor.marker.error',
        4 => 'wcf.ckeditor.marker.info',
    ];

    /**
     * database table for this object
     * @var string
     */
    protected static $databaseTableName = 'election_participant';

    protected ?array $aliases = null;

    /**
     * Get the css class corresponding to the given color code.
     */
    public static function colorToMarkerClass(int $color): string {
        return match ($color) {
            1 => 'marker-success',
            2 => 'marker-warning',
            3 => 'marker-error',
            4 => 'marker-info',
            default => '',
        };
    }

    public static function fromAlias($threadID, string $alias): ?Participant {
        $sql = "SELECT wbb1_election_participant.* FROM wbb1_election_participant
                JOIN wbb1_election_participant_alias
                ON wbb1_election_participant.participantID = wbb1_election_participant_alias.participantID
                WHERE wbb1_election_participant.threadID = ? AND alias = ?";
        $statement = WCF::getDB()->prepare($sql, 1);
        $statement->execute([$threadID, $alias]);
        return $statement->fetchSingleObject(Participant::class);
    }

    /**
     * Get the css class to color in this participant.
     */
    public function getMarkerClass(): string {
        return Participant::colorToMarkerClass($this->color);
    }

    /**
     * Decorate the name with HTML markup according the participant's properties.
     */
    public function decorateName(): string {
        $class = $this->getMarkerClass();
        $name = StringUtil::encodeHTML($this->name);
        if (!$this->active) {
            $name = "<s>$name</s>";
        }
        return $class === '' ? $name : "<mark class=\"$class\">$name</mark>";
    }

    public function getAliases(): array {
        if ($this->aliases === NULL) {
            $sql = "SELECT alias FROM wbb1_election_participant_alias
                    WHERE threadID = ? AND participantID = ?
                    ORDER BY alias";
            $statement = WCF::getDB()->prepare($sql);
            $statement->execute([$this->threadID, $this->participantID]);
            $this->aliases = $statement->fetchAll(\PDO::FETCH_COLUMN);
        }
        return $this->aliases;
    }

    public function getAliasList(): string {
        return implode(', ', $this->getAliases());
    }

    public function saveAliases(string $aliasList): void {
        $this->aliases = null; // it is better to refetch the aliases from db, because we
                               // don't check for duplicates witch other participants here
        $aliases = [];
        foreach (\preg_split(ParticipantListLine::SPLIT_REGEX, $aliasList) as $alias) {
            $alias = StringUtil::trim($alias);
            if ($alias !== '' && mb_strlen($alias) <= Election::MAX_VOTER_LENGTH) {
                $aliases[] = $alias;
            }
        }
        sort($aliases, SORT_STRING | SORT_FLAG_CASE);
        $aliases = array_unique($aliases);
        $sql = "INSERT IGNORE INTO ".Participant::ALIAS_TABLE_NAME." (participantID, threadID, alias)
                VALUES (?, ?, ?)";
        $statement = WCF::getDB()->prepare($sql);
        foreach ($aliases as $alias) {
            $statement->execute([$this->participantID, $this->threadID, $alias]);
        }
    }

    public function updateAliases(string $aliasList): void {
        $this->deleteAliases();
        $this->saveAliases($aliasList);
    }

    public function deleteAliases(): void {
        $this->aliases = [];
        $sql = "DELETE FROM ".Participant::ALIAS_TABLE_NAME."
                WHERE participantID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$this->participantID]);
    }
}
