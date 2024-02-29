<?php

namespace wbb\data\election;

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

    private array $names = [];

    private array $tooLong = [];

    private array $notFound = [];

    private bool $validated = true;

    /**
     * get all participants of given thread
     */
    public static function forThread(int $threadID): static {
        $list = new ParticipantList();
        $list->getConditionBuilder()->add('threadID = ?', [$threadID]);
        $list->readObjects();
        return $list;
    }

    /**
     * load names from a newline-seperated string
     * names are trimmed and lines with starting with # are ignored
     * afterwards `validate` and `save` need to be called to save the list
     */
    public static function fromNsvInput(string $input): static {
        $list = new ParticipantList();
        $lines = explode("\n", $input);
        foreach ($lines as $line) {
            $line = StringUtil::trim($line);
            if ($line !== '' && $line[0] !== '#') {
                if (mb_strlen($line) > Election::MAX_VOTER_LENGTH) {
                    $list->tooLong[] = $line;
                } else {
                    $list->names[] = $line;
                }
            }
        }
        $list->names = array_unique($list->names);
        $list->validated = false;
        return $list;
    }

    public function hasNames() {
        return count($this->names) !== 0;
    }

    /**
     * Generate a formatted HTML list containing all the participants.
     */
    public function generateHtmlList(): string {
        $html = '<h2>' . WCF::getLanguage()->get('wbb.electionbot.form.participants') . '</h2>';
        $html .= '<ol>';
        foreach ($this as $participant) {
            $html .= "<li>{$participant->decorateName()}</li>";
            if ($participant->extra !== '') {
                $html .= " - {$participant->extra}";
            }
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
        if ($strict) {
            $userList = new UserList();
            $userList->getConditionBuilder()->add('username IN (?)', [$this->names]);
            $userList->readObjects();
            $found = array_map(fn($user) => $user->username, $userList->getObjects());
            $this->notFound = array_diff($this->names, $found);
            $this->names = $found;
        }
        $this->validated = true;
        if (count($this->tooLong) || count($this->notFound)) {
            return 'invalid';
        }
        if (count($this->names) > static::MAX_PARTICIPANTS) {
            return 'tooMany';
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
        $res = '';
        if (count($this->notFound)) {
            $lang = WCF::getLanguage()->get('wbb.electionbot.form.participants.inline.notFound');
            $res .= "\n# $lang\n" . implode("\n", $this->notFound);
        }
        if (count($this->tooLong)) {
            $lang = WCF::getLanguage()->getDynamicVariable(
                'wbb.electionbot.form.participants.inline.tooLong',
                ['maxLength' => Election::MAX_VOTER_LENGTH],
            );
            $res .= "\n# $lang\n" . implode("\n", $this->tooLong);
        }
        $lang = WCF::getLanguage()->get('wbb.electionbot.form.participants.inline.valid');
        $res .= "\n# $lang\n" . implode("\n", $this->names);
        return $res;
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
        if (count($this->names)) {
            try {
                WCF::getDB()->beginTransaction();
                $sql = "INSERT INTO wbb1_election_participant (threadID, name, extra, color)
                        VALUES (?, ?, ?, ?)";
                $statement = WCF::getDB()->prepare($sql);
                foreach ($this->names as $name) {
                    $statement->execute([$threadID, $name, '', 0]);
                }
                WCF::getDB()->commitTransaction();
            } catch (\Exception $exception) {
                WCF::getDB()->rollBackTransaction();
                throw $exception;
            }
        }
    }
}


