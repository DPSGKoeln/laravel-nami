<?php

namespace Zoomyboy\LaravelNami\Fakes;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class LoginFake extends Fake {

    public function succeeds(string $mglnr): void
    {
        Http::fake(function($request) use ($mglnr) {
            if ($request->url() === 'https://nami.dpsg.de/ica/pages/login.jsp') {
                return Http::response('', 200);
            }

            if ($request->url() === 'https://nami.dpsg.de/ica/rest/nami/auth/manual/sessionStartup') {
                return Http::response('{"statusCode": 0}', 302)->then(function($r) use ($mglnr) {
                    app('nami.cookie')->set($mglnr, 'rZMBv1McDAJ-KukQ6BboJBTq.srv-nami06');
                    return $r;
                });
            }
        });
    }

    public function fails(string $mglnr): void
    {
        Http::fake(function($request) {
            if ($request->url() === 'https://nami.dpsg.de/ica/pages/login.jsp') {
                return Http::response('', 200);
            }

            if ($request->url() === 'https://nami.dpsg.de/ica/rest/nami/auth/manual/sessionStartup') {
                return Http::response('{"statusCode": 3000, "statusMessage": "Benutzer nicht gefunden oder Passwort falsch"}', 200);
            }
        });
    }

}
