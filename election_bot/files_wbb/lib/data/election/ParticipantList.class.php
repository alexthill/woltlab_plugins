<?php

namespace wbb\data\election;

use wbb\data\election\utils\ParticipantListLine;
use wcf\data\user\UserList;
use wcf\data\DatabaseObjectList;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Represents a list of participants.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 * @package com.alexthill.election_bot
 *
 * @method      Participant         current()
 * @method      Participant[]       getObjects()
 * @method      Participant|null    search($objectID)
 * @property    Participant[]       $objects
 */
class ParticipantList extends DatabaseObjectList {

    const MAX_PARTICIPANTS = 100;

    public $className = Participant::class;

    /**
     * @var ParticipantListLine[]
     */
    private array $lines = [];

    private bool $validated = true;

    private int $threadID = 0;

    /**
     * get all participants of given thread
     */
    public static function forThread(int $threadID): static {
        $list = new ParticipantList();
        $list->getConditionBuilder()->add('threadID = ?', [$threadID]);
        $list->readObjects();
        $list->threadID = $threadID;
        return $list;
    }

    /**
     * load names from a newline-separated string
     * names are trimmed and lines starting with '#' are ignored
     * afterwards `validate` and `save` need to be called to save the list
     */
    public static function fromNsvInput(string $input): static {
        $list = new ParticipantList();
        $lines = explode("\n", $input);
        foreach ($lines as $line) {
            $line = StringUtil::trim($line);
            if ($line !== '' && $line[0] !== '#') {
                $list->lines[] = new ParticipantListLine($line);
            }
        }
        $list->validated = false;
        return $list;
    }

    public function generateHtmlListWithAliases(): string {
        if ($this->threadID === 0) {
            throw new \BadMethodCallException('threadID is not set');
        }
        $sql = "SELECT participantID, alias FROM wbb1_election_participant_alias WHERE threadID = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$this->threadID]);
        $aliases = [];
        while ($row = $statement->fetchArray()) {
            if (isset($aliases[$row['participantID']])) {
                $aliases[$row['participantID']][] = $row['alias'];
            } else {
                $aliases[$row['participantID']] = [$row['alias']];
            }
        }
        return $this->generateHtmlList($aliases);
    }

    /**
     * Generate a formatted HTML list containing all the participants.
     */
    public function generateHtmlList(array $aliases = []): string {
        $allCount = count($this);
        $activeCount = $this->countActive();

        $html = '<h2>' . WCF::getLanguage()->get('wbb.electionbot.form.participants');
        if ($activeCount != $allCount) {
            $html .= " ($activeCount/$allCount)";
        }
        $html .= '</h2><ol>';
        foreach ($this as $participant) {
            $html .= "<li>{$participant->decorateName()}";
            if (isset($aliases[$participant->participantID])) {
                $joined = implode('/', $aliases[$participant->participantID]);
                $html .= " ($joined)";
            }
            if ($participant->extra !== '') {
                $html .= " - {$participant->extra}";
            }
            $html .= '</li>';
        }
        $html .= '</ol>';
        return $html;
    }

    /**
     * Validates names loaded with `static::fromNsvInput`.
     *
     * @param   bool    $strict if true it is checked wether names correspond to existing usernames
     * @return  string          the empty string if valid or else an error string
     */
    public function validate(bool $strict): string {
        $this->validated = true;
        if ($strict) {
            $names = [];
            foreach ($this->lines as $line) {
                $aliases = $line->getAliases();
                if (count($aliases) === 0) {
                    $names[] = $line->getName();
                } else {
                    array_push($names, ...$aliases);
                }
            }
            $userList = new UserList();
            $userList->getConditionBuilder()->add('username IN (?)', [array_unique($names)]);
            $userList->readObjects();
            $founds = array_flip(array_map(fn($user) => $user->username, $userList->getObjects()));
            foreach ($this->lines as $line) {
                if (!isset($founds[$line->getName()])) {
                    $line->notFound = true;
                }
                $aliases = $line->getAliases();
                if (count($aliases) === 0) {
                    $line->notFound = !isset($founds[$line->getName()]);
                } else {
                    $line->notFound = array_reduce(
                        $aliases,
                        fn($carry, $alias) => $carry || !isset($founds[$alias]),
                        false
                    );
                }
            }
        }
        $names = [];
        foreach ($this->lines as $line) {
            if (isset($names[$line->getName()])) {
                $line->duplicate = true;
                continue;
            } else {
                $names[$line->getName()] = true;
            }
            foreach ($line->getAliases() as $alias) {
                if (isset($names[$alias])) {
                    $line->duplicate = true;
                } else {
                    $names[$alias] = true;
                }
            }
        }
        if (count($this->lines) > static::MAX_PARTICIPANTS) {
            return 'tooMany';
        }
        foreach ($this->lines as $line) {
            if (!$line->isValid()) {
                return 'invalid';
            }
        }
        return '';
    }

    /**
     * returns the input from `static::fromNsvInput` but with remarks about which names are invalid.
     *
     * @return  string
     * @throws  BadMethodCallException  if `validate` has not been called
     */
    public function getValidatedInput(): string {
        if (!$this->validated) {
            throw new \BadMethodCallException('not validated');
        }
        $invalid = '';
        $valid = '';
        foreach($this->lines as $line) {
            if (!$line->isValid()) {
                $invalid .= '# ' . implode('; ', $line->getInvalidRemarks()) . "\n"
                    . $line->reconstructLine() . "\n\n";
            } else {
                $valid .= $line->reconstructLine() . "\n";
            }
        }
        if ($valid === '') {
            return $invalid;
        }
        return $invalid . "# OK\n" . $valid;
    }

    /**
     * Save the names loaded from `static::fromNsvInput` to the database.
     *
     * @return  void
     * @throws  BadMethodCallException  if `validate` has not been called
     */
    public function save(int $threadID): void {
        if (!$this->validated) {
            throw new \BadMethodCallException('not validated');
        }
        if (count($this->lines) === 0) {
            return;
        }
        try {
            WCF::getDB()->beginTransaction();
            $sql = "INSERT INTO ".Participant::ALIAS_TABLE_NAME." (participantID, threadID, alias)
                    VALUES (?, ?, ?)";
            $statement = WCF::getDB()->prepare($sql);
            foreach ($this->lines as $line) {
                $action = new ParticipantAction([], 'create', ['data' => [
                    'threadID' => $threadID,
                    'name' => $line->getName(),
                    'extra' => '',
                    'color' => 0,
                ]]);
                $action->executeAction();
                $participantID = $action->getReturnValues()['returnValues']->participantID;
                foreach ($line->getAliases() as $alias) {
                    $statement->execute([$participantID, $threadID, $alias]);
                }
            }
            WCF::getDB()->commitTransaction();
        } catch (\Exception $exception) {
            WCF::getDB()->rollBackTransaction();
            throw $exception;
        }
    }

    public function countNames(): int {
        return count($this->lines);
    }

    /**
     * Count the number of active participants in this list.
     *
     * @return  int number of active particpants
     */
    public function countActive(): int {
        $activeCount = 0;
        foreach ($this as $participant) {
            if ($participant->active) {
                $activeCount += 1;
            }
        }
        return $activeCount;
    }
}
