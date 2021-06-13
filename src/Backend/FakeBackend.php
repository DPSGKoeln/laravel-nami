<?php

namespace Zoomyboy\LaravelNami\Backend;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

class FakeBackend {

    private $members;

    public function __construct() {
        $this->members = collect([]);
    }

    public function addMember(array $member) {
        $this->members->push($member);
    }

    public function cookie($cookie) {
        return $this;
    }

    public function put($url, $data) {
        if (preg_match('|/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/([0-9]+)/([0-9]+)|', $url, $matches)) {
            list($url, $groupId, $memberId) = $matches;
            $existing = $this->members->search(function($m) use ($groupId, $memberId) {
                return $m['gruppierungId'] == $groupId && $m['id'] == $memberId;
            });
            if ($existing !== false) {
                $this->members[$existing] = $data;
            }

            return;
        }

        throw new \Exception('no handler found for URL '.$url);
    }

    public function get($url) {
        if (preg_match('|/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/([0-9]+)/([0-9]+)|', $url, $matches)) {
            list($url, $groupId, $memberId) = $matches;

            $member = $this->members->first(function($m) use ($groupId, $memberId) {
                return $m['gruppierungId'] == $groupId && $m['id'] == $memberId;
            });

            return new Response(new GuzzleResponse(200, [], json_encode([
                'success' => true,
                'data' => $member
            ])));
        }
    }

}
