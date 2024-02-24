<?php

namespace wbb\data\election;

use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Represents an election options.
 *
 * @author  Xaver
 * @license MIT License <https://mit-license.org/>
 * @package com.xaver.election_bot
 */
class ElectionOptions {

    public bool $start = false;

    public bool $end = false;

    public bool $changeDeadline = false;

    public ?\DateTime $deadline = null;

    public array $addVotes = [];

    public array $addVoteValues = [];

    public static function fromParameters(array $parameters): ElectionOptions {
        $options = new ElectionOptions();
        if (!empty($parameters['electionStart'])) {
            $options->start = true;
        }
        if (!empty($parameters['electionEnd'])) {
            $options->end = true;
        }
        if (!empty($parameters['electionChangeDeadline'])) {
            $options->changeDeadline = true;
        }
        if (($options->changeDeadline || $options->start) && !empty($parameters['electionDeadline'])) {
            $deadline = \DateTime::createFromFormat(\DateTimeInterface::ATOM, $parameters['electionDeadline']);
            $options->deadline = $deadline;
        }
        if (isset($parameters['electionAddVote'])) {
            if (is_array($parameters['electionAddVote'])) {
                $options->addVotes = array_map('json_decode', $parameters['electionAddVote']);
            } else {
                $options->addVotes[] = json_decode($parameters['electionAddVote']);
            }
        }
        if (isset($parameters['electionAddVoteValue'])) {
            if (is_array($parameters['electionAddVoteValue'])) {
                $options->addVoteValues = array_map('json_decode', $parameters['electionAddVoteValue']);
            } else {
                $options->addVoteValues[] = json_decode($parameters['electionAddVoteValue']);
            }
        }
        return $options;
    }

    public function validate(Election $election, array &$errors): void {
        $id = $election->electionID;
        
        if ($this->deadline === null) {
            if ($this->changeDeadline || $this->start) {
                $msg = WCF::getLanguage()->getDynamicVariable('wcf.global.form.error.empty');
                $errors[] = $this->createError($id, 'electionDeadline', $msg);
            }
        } else if ($this->deadline === false || $this->deadline ->getTimestamp() < TIME_NOW) {
            $msg = WCF::getLanguage()->get('wbb.electionbot.form.deadline.error.invalid');
            $errors[] = $this->createError($id, 'electionDeadline', $msg);
        }
        
        $i = 0;
        foreach ($this->addVotes as $vote) {
            if ($vote === null) {
                $msg = WCF::getLanguage()->get('wbb.electionbot.form.addVote.error.invalid');
                $errors[] = $this->createError($id, 'electionAddVote', $msg, $i);
                continue;
            }
            $vote->voter = StringUtil::trim(strval($vote->voter ?? ''));
            $vote->voted = StringUtil::trim(strval($vote->voted ?? ''));
            $vote->count = intval($vote->count ?? 1);
            if ($vote->voter === '') {
                $msg = WCF::getLanguage()->get('wbb.electionbot.form.addVote.error.emptyVoter');
                $errors[] = $this->createError($id, 'electionAddVote', $msg, $i);
            }
            if (mb_strlen($vote->voter, 'UTF-8') > 255 || mb_strlen($vote->voted, 'UTF-8') > 255) {
                $msg = WCF::getLanguage()->getDynamicVariable('wbb.electionbot.form.addVote.error.tooLong', ['maxLength' => 255]);
                $errors[] = $this->createError($id, 'electionAddVote', $msg, $i);
            }
            if ($vote->count < Vote::MIN_COUNT || $vote->count > Vote::MAX_COUNT) {
                $msg = WCF::getLanguage()->getDynamicVariable(
                    'wbb.electionbot.form.addVote.error.countOutsideRange',
                    ['min' => Vote::MIN_COUNT, 'max' => Vote::MAX_COUNT],
                );
                $errors[] = $this->createError($id, 'electionAddVote', $msg, $i);
            }
            $i += 1;
        }
        
        $i = 0;
        foreach ($this->addVoteValues as $vote) {
            if ($vote === null) {
                $msg = WCF::getLanguage()->get('wbb.electionbot.form.addVote.error.invalid');
                $errors[] = $this->createError($id, 'electionAddVoteValue', $msg, $i);
                continue;
            }
            $vote->voter = StringUtil::trim(strval($vote->voter ?? ''));
            $vote->count = intval($vote->count ?? 1);
            if ($vote->voter === '') {
                $msg = WCF::getLanguage()->get('wbb.electionbot.form.addVote.error.emptyVoter');
                $errors[] = $this->createError($id, 'electionAddVoteValue', $msg, $i);
            }
            if (mb_strlen($vote->voter, 'UTF-8') > 255) {
                $msg = WCF::getLanguage()->getDynamicVariable('wbb.electionbot.form.addVote.error.tooLong', ['maxLength' => 255]);
                $errors[] = $this->createError($id, 'electionAddVoteValue', $msg, $i);
            }
            if ($vote->count < VOTE::MIN_COUNT || $vote->count > VOTE::MAX_COUNT) {
                $msg = WCF::getLanguage()->getDynamicVariable(
                    'wbb.electionbot.form.addVote.error.countOutsideRange',
                    ['min' => VOTE::MIN_COUNT, 'max' => VOTE::MAX_COUNT],
                );
                $errors[] = $this->createError($id, 'electionAddVoteValue', $msg, $i);
            }
            $i += 1;
        }
    }

    protected function createError(int $id, string $field, string $msg, int $n = 0): array {
        return [
            'id' => $id,
            'field' => $field,
            'msg' => $msg,
            'n' => $n,
        ];
    }
}

