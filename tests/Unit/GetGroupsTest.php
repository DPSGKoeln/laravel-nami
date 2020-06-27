<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Zoomyboy\LaravelNami\NamiServiceProvider;
use Zoomyboy\LaravelNami\LoginException;

class GetGroupsTest extends TestCase
{

    public $successJson = '{"servicePrefix":null,"methodCall":null,"response":null,"statusCode":0,"statusMessage":"","apiSessionName":"JSESSIONID","apiSessionToken":"ILBY--L4pZEjSKa39tCemens","minorNumber":2,"majorNumber":1}';
    public $bruteJson = '{"servicePrefix":null,"methodCall":null,"response":null,"statusCode":3000,"statusMessage":"Die höchste Anzahl von Login-Versuchen wurde erreicht. Ihr Konto ist für 15 Minuten gesperrt worden. Nach Ablauf dieser Zeitspanne wird ihr Zugang wieder freigegeben.","apiSessionName":"JSESSIONID","apiSessionToken":"tGlSpMMij9ruHfeiUYjO7SD2","minorNumber":0,"majorNumber":0}';
    public $wrongCredentialsJson = '{"servicePrefix":null,"methodCall":null,"response":null,"statusCode":3000,"statusMessage":"Benutzer nicht gefunden oder Passwort falsch.","apiSessionName":"JSESSIONID","apiSessionToken":"v7lrjgPBbXInJR57qJzVIJ05","minorNumber":0,"majorNumber":0}';
    public $groups = '{"success":true,"data":[{"descriptor":"Group","name":"","representedClass":"de.iconcept.nami.entity.org.Gruppierung","id":100}],"responseType":"OK"}';
    public $expiredJson = '{"servicePrefix":null,"methodCall":null,"response":null,"statusCode":3000,"statusMessage":"expired","apiSessionName":"JSESSIONID","apiSessionToken":"tGlSpMMij9ruHfeiUYjO7SD2","minorNumber":0,"majorNumber":0}';

    protected function getPackageProviders($app)
    {
        return [ NamiServiceProvider::class ];
    }

    public function test_get_all_groups()
    {
        Http::fake([
            'https://nami.dpsg.de/ica/pages/login.jsp' => Http::response('<html></html>', 200),
            'https://nami.dpsg.de/ica/rest/nami/auth/manual/sessionStartup' => Http::response($this->successJson, 200),
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groups, 200),
        ]);

        Config::set('nami.auth.mglnr', '11223');
        Config::set('nami.auth.password', 'secret');
        Config::set('nami.auth.groupid', '55555');

        Nami::login();
        $this->assertEquals([
            (object) ['id' => 100, 'name' => 'Group']
        ], Nami::groups()->toArray());


        Http::assertSent(function($request) {
            return $request->url() == 'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root';
        });
        Http::assertSentCount(3);
    }

    public function test_has_group_access()
    {
        Http::fake([
            'https://nami.dpsg.de/ica/pages/login.jsp' => Http::response('<html></html>', 200),
            'https://nami.dpsg.de/ica/rest/nami/auth/manual/sessionStartup' => Http::response($this->successJson, 200),
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groups, 200),
        ]);

        Config::set('nami.auth.mglnr', '11223');
        Config::set('nami.auth.password', 'secret');
        Config::set('nami.auth.groupid', '55555');

        Nami::login();
        $this->assertTrue(Nami::hasGroup(100));
        $this->assertFalse(Nami::hasGroup(10101));

        Http::assertSentCount(4);
    }
}
