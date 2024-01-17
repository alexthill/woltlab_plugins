<?php

namespace wbb\system\bbcode;

use wcf\system\bbcode\AbstractBBCode;
use wcf\system\bbcode\BBCodeParser;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Parses the [v] bbcode tag.
 *
 * @author  Alex Thill
 * @license MIT License <https://mit-license.org/>
 */
class VoteBBCode extends AbstractBBCode {
    /**
     * @inheriDoc
     */
    public function getParsedTag(array $openingTag, $content, array $closingTag, BBCodeParser $parser) : string {
        $content = StringUtil::trim($content);
        if (strlen($content) > 1 && $content[0] === '!') {
            $content = substr($content, 1);
        }
        $content = WCF::getLanguage()->getDynamicVariable('wbb.electionbot.vote', [
            'valid' => count($openingTag['attributes']) && $openingTag['attributes'][0] == '1',
            'vote' => $content
        ]);
        switch ($parser->getOutputType() == 'text/html') {
            case 'text/html':
                return '<span class="electionBotVote"><u>' . $content . '</u></span>';
            case 'text/simplified-html':
                return '<u>' . $content . '</u>';
            default:
                return $content;
        }
    }
}
