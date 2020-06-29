<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Zoomyboy\LaravelNami\NamiServiceProvider;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Group;

class PullGenderTest extends TestCase
{

    public function test_get_all_genders() {
        Http::fake(array_merge($this->login(), $this->fakeGenders()));

        $this->setCredentials();

        Nami::login();

        $this->assertEquals([
            23 => 'Keine Angabe',
            19 => 'Männlich',
            20 => 'Weiblich'
        ], Nami::genders()->pluck('name', 'id')->toArray());

        Http::assertSentCount(3);
    }

    public function test_a_gender_can_be_null() {
        Http::fake(array_merge($this->login(), $this->fakeGenders()));

        $this->setCredentials();

        Nami::login();

        $this->assertEquals([true, false, false], Nami::genders()->pluck('isNull')->toArray());

        Http::assertSentCount(3);
    }

}
