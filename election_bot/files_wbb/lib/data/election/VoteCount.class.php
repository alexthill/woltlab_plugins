<?php

namespace wbb\data\election;

use wcf\system\WCF;

/**
 * Represents a vote count.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 */
class VoteCount {

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
        return VoteCount::fromUniqueVotes($votes);
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
            $voters = array_map(function ($vote) {
                return htmlspecialchars($vote->voter) . ($vote->count === 1 ? '' : '*' . $vote->count);
            }, $item['votes']);
            if ($newVoter !== '' && $newVoted === $voted) {
                $voters[] = '<span style="text-decoration: underline dotted;">' . htmlspecialchars($newVoter) . '</span>'
                     . ($newCount === 1 ? '' : '*' . $newCount);
            }
            $html .= htmlspecialchars($voted) . ' (' . $item['count'] . '): ' . implode(', ', $voters);
        }
        if (isset($this->items[''])) {
            if ($html !== '') {
                $html .= '<br/><br/>';
            }
            $unvote = WCF::getLanguage()->get('wbb.electionbot.votecount.unvote');
            $voters = array_map(function ($vote) { return htmlspecialchars($vote->voter); }, $this->items['']['votes']);
            if ($newVoter !== '' && $newVoted === '') {
                $voters[] = '<span style="text-decoration: underline dotted;">' . htmlspecialchars($newVoter) . '</span>';
            }
            $html .= WCF::getLanguage()->get('wbb.electionbot.votecount.unvote') . ': ' . implode(', ', $voters);
        }
        
        return $html;
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
