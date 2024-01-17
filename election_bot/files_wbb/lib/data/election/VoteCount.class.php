<?php

namespace wbb\data\election;

/**
 * Represents a vote count.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 */
class VoteCount {

    private $items = [];

    public function __construct(VoteList $list) {
        $votes = [];
        foreach ($list as $vote) {
            $votes[$vote->voter] = $vote;
        }
        foreach ($votes as $vote) {
            $this->addVote($vote);
        }
    }

    public function hasNoVotes(): bool {
        return count($this->items) === 0;
    }

    public function addVote(Vote $vote) {
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
        if (!isset($this->items[$newVoted])) {
            $this->items[$newVoted] = ['count' => 0, 'votes' => []];
        }
        $this->items[$newVoted]['count'] += $newCount;
        $this->sortByCount();
        
        $html = '';
        $i = false;
        foreach ($this->items as $voted => $item) {
            if ($voted === '') continue;
            
            if ($i) $html .= '<br/>';
            $html .= $voted . ' (' . $item['count'] . '): ';
            $j = false;
            foreach ($item['votes'] as $vote) {
                if ($j) $html .= ', ';
                $html .= $vote->voter;
                if ($vote->count !== 1) $html .= '*' . $vote->count;
                $j = true;
            }
            if ($voted === $newVoted) {
                if ($j) $html .= ', ';
                $html .= '<span style="text-decoration: underline dotted;">' . $newVoter . '</span>';
                if ($newCount !== 1) $html .= '*' . $newCount;
            }
            $i = true;
        }
        return $html;
    }

    protected function sortByCount() {
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
