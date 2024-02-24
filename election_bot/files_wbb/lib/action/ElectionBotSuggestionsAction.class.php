<?php

namespace wbb\action;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use wbb\data\election\ParticipantList;
use wcf\data\user\UserProfileList;
use wcf\http\Helper;
use wcf\system\WCF;
use wcf\system\exception\PermissionDeniedException;

/**
 * Suggests election participants and normal users.
 *
 * @author  Xaver
 * @license MIT License <https://mit-license.org/>
 * @package com.xaver.election_bot
 */
class ElectionBotSuggestionsAction implements RequestHandlerInterface {

    public function handle(ServerRequestInterface $request): ResponseInterface {
        if (WCF::getUser()->userID === 0) {
            throw new PermissionDeniedException();
        }
        $parameters = Helper::mapQueryParameters(
            $request->getQueryParams(),
            <<<'EOT'
                array {
                    query: non-empty-string,
                    threadID: positive-int,
                }
                EOT,
        );
        $matches = static::getMatches($parameters['query'], $parameters['threadID']);
        return new JsonResponse($matches, 200, ['cache-control' => ['max-age=600']]);
    }

    /**
     * Get matching participants and usernames.
     * @param   string  $query
     * @param   int     $threadID
     * @return  string[]
     */
    public static function getMatches(string $query, int $threadID): array {
        $charMap = [];
        $query2 = mb_strtolower(mb_substr($query, 0, 255));
        $query2 = static::utf8ToExtendedAscii($query2, $charMap);
        $queryLen = mb_strlen($query2);
        $scores = [];
        $matches = [];

        $participants = ParticipantList::forThread($threadID);
        foreach ($participants as $participant) {
            $name = mb_strtolower($participant->name);
            $name = static::utf8ToExtendedAscii($name, $charMap);
            if ($name === $query2) {
                $scores[$participant->name] = 0;
            } else {
                $name = mb_substr($name, 0, $queryLen);
                $distance = levenshtein($name, $query2);
                if ($distance <= $queryLen / 2) {
                    $scores[$participant->participantID] = $distance + 1
                        + ($participant->active ? 0 : 0.5);
                }
            }
        }
        asort($scores);
        foreach ($scores as $id => $score) {
            $participant = $participants->getObjects()[$id];
            $matches[$participant->name] = [
                'label' => $participant->name,
                'icon' => '',
                'active' => $participant->active,
                'objectID' => -1,
            ];
            if (count($matches) > 10) {
                break;
            }
        }

        $query2 = addcslashes($query, '_%');
        $list = new UserProfileList();
        $list->getConditionBuilder()->add("username LIKE ?", [$query2 . '%']);
        $list->sqlLimit = max(3, 10 - count($matches));
        $list->readObjects();
        foreach ($list as $user) {
            if (!array_key_exists($user->username, $matches)) {
                $matches[$user->username] = [
                    'label' => $user->username,
                    'icon' => $user->getAvatar()->getImageTag(16),
                    'active' => true,
                    'objectID' => $user->userID,
                ];
            }
        }

        return array_values($matches);
    }

    /**
     * @see https://www.php.net/manual/en/function.levenshtein.php#113702
     */
    private static function utf8ToExtendedAscii(string $str, array &$map): string {
        // find all multibyte characters (cf. utf-8 encoding specs)
        $matches = array();
        if (!preg_match_all('/[\xC0-\xF7][\x80-\xBF]+/', $str, $matches))
            return $str; // plain ascii string
        // update the encoding map with the characters not already met
        foreach ($matches[0] as $mbc)
            if (!isset($map[$mbc]))
                $map[$mbc] = chr(min(255, 128 + count($map)));
        // finally remap non-ascii characters
        return strtr($str, $map);
    }
}
