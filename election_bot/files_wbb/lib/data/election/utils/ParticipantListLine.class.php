<?php

namespace wbb\data\election\utils;

use wbb\data\election\Election;
use wcf\system\WCF;
use wcf\util\StringUtil;

class ParticipantListLine {
    public const SPLIT_REGEX = '/[,|\/:]/';

    private string $name;
    private array $aliases = [];

    public bool $tooLong = false;
    public bool $notFound = false;
    public bool $duplicate = false;

    public function __construct(string $line) {
        $line = StringUtil::trim($line);
        $parts = preg_split(ParticipantListLine::SPLIT_REGEX, $line);
        $this->name = StringUtil::trim(array_shift($parts));
        if (mb_strlen($this->name) > Election::MAX_VOTER_LENGTH) {
            $this->tooLong = true;
        }
        foreach ($parts as $alias) {
            $alias = StringUtil::trim($alias);
            if (mb_strlen($alias) > Election::MAX_VOTER_LENGTH) {
                $this->tooLong = true;
            }
            $this->aliases[] = $alias;
        }
        $this->aliases = array_unique($this->aliases);
        sort($this->aliases, SORT_STRING | SORT_FLAG_CASE);
    }

    public function isValid(): bool {
        return !$this->tooLong && !$this->notFound && !$this->duplicate;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getAliases(): array {
        return $this->aliases;
    }

    public function reconstructLine(): string {
        $line = $this->name;
        if (count($this->aliases)) {
            $line .= ": " . implode(" / ", $this->aliases);
        }
        return $line;
    }

    public function getInvalidRemarks(): array {
        $remarks = [];
        if ($this->notFound) {
            $remarks[] = WCF::getLanguage()->get('wbb.electionbot.form.participants.inline.notFound');
        }
        if ($this->tooLong) {
            $remarks[] = WCF::getLanguage()->getDynamicVariable(
                'wbb.electionbot.form.participants.inline.tooLong',
                ['maxLength' => Election::MAX_VOTER_LENGTH],
            );
        }
        if ($this->duplicate) {
            $remarks[] = WCF::getLanguage()->get('wbb.electionbot.form.participants.inline.duplicate');
        }
        return $remarks;
    }
}
