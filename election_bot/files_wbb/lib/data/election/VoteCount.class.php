<?php

namespace wbb\data\election;

use wbb\data\election\ParticipantList;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Represents a vote count.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 * @package com.alexthill.election_bot
 */
class VoteCount {

    public array $participants = [];

    private array $items = [];

    public static function fromUniqueVotes(array $votes): static {
        $voteCount = new VoteCount();
        foreach ($votes as $vote) {
            $voteCount->addVote($vote);
        }
        return $voteCount;
    }

    public static function fromVoteList(VoteList $list): static {
        $votes = [];
        foreach ($list as $vote) {
            $votes[$vote->voter] = $vote;
        }
        uasort($votes, function (Vote $a, Vote $b) {
            if ($a->voteID == $b->voteID) {
                return 0;
            }
            return ($a->voteID < $b->voteID) ? -1 : 1;
        });
        return VoteCount::fromUniqueVotes($votes);
    }

    public function participants(?ParticipantList $participants): static {
        $this->participants = [];
        if ($participants !== null) {
            foreach ($participants as $participant) {
                $this->participants[$participant->name] = $participant;
            }
        }
        return $this;
    }

    public function addVote(Vote $vote): void {
        if (!isset($this->items[$vote->voted])) {
            $this->items[$vote->voted] = ['count' => 0, 'votes' => []];
        }
        $this->items[$vote->voted]['count'] += $vote->count;
        $this->items[$vote->voted]['votes'][] = $vote;
    }

    public function generateHtml(): string {
        return $this->generateHtmlWithNewVote('', '', 0);
    }

    public function generateHtmlWithNewVote(string $newVoter, string $newVoted, int $newCount): string {
        if ($newVoter !== '') {
            if (!isset($this->items[$newVoted])) {
                $this->items[$newVoted] = ['count' => 0, 'votes' => []];
            }
            $this->items[$newVoted]['count'] += $newCount;
        }
        $this->sortItems();

        $html = '';
        foreach ($this->items as $voted => $item) {
            if ($voted === '') continue;

            if ($html !== '') $html .= '<br/>';
            $voters = array_map(
                fn($v) => $this->decorateName($v->voter) . ($v->count === 1 ? '' : '*' . $v->count),
                $item['votes'],
            );
            if ($newVoter !== '' && $newVoted === $voted) {
                $voters[] = "<u>{$this->decorateName($newVoter)}</u>"
                    . ($newCount === 1 ? '' : '*' . $newCount);
            }
            $html .= "{$this->decorateName($voted)} ({$item['count']}): " . implode(', ', $voters);
        }
        if (isset($this->items[''])) {
            if ($html !== '') {
                $html .= '<br/><br/>';
            }
            $unvote = WCF::getLanguage()->get('wbb.electionbot.votecount.unvote');
            $voters = array_map(fn($vote) => $this->decorateName($vote->voter), $this->items['']['votes']);
            if ($newVoter !== '' && $newVoted === '') {
                $voters[] = "<u>{$this->decorateName($newVoter)}</u>";
            }
            $html .= WCF::getLanguage()->get('wbb.electionbot.votecount.unvote')
                . ': ' . implode(', ', $voters);
        }

        return $html;
    }

    protected function decorateName(string $name): string {
        if (array_key_exists($name, $this->participants)) {
            return $this->participants[$name]->decorateName();
        }
        return StringUtil::encodeHTML($name);
    }

    protected function sortItems(): void {
        uasort($this->items, function ($a, $b) {
            $a = $a['count'];
            $b = $b['count'];
            if ($a == $b) {
                return 0;
            }
            return ($a > $b) ? -1 : 1;
        });
    }
}

