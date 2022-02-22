<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Exceptions\RightException;
use Zoomyboy\LaravelNami\Fakes\MembershipFake;
use Zoomyboy\LaravelNami\Member;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Tests\TestCase;

class MembershipTest extends TestCase
{

    public function test_get_all_memberships_of_a_member(): void
    {
        $data = [
            "id" => 68,
            "gruppierung" => "Diözesanleitung Köln 100000",
            "gruppierungId" => 103,
            "taetigkeit" => "ReferentIn",
            "taetigkeitId" => 33,
            "untergliederung" => "Pfadfinder",
            "untergliederungId" => 55,
            "aktivVon" => "2017-02-11 00:00:00",
            "aktivBis" => "2017-03-11 00:00:00"
        ];
        app(MembershipFake::class)->shows(16, $data);

        $this->assertSame($data, $this->login()->membership(16, 68));
    }

    /**
     * @testWith ["Sicherheitsverletzung: Zugriff auf Rechte Recht (n:2001002 o:2) fehlgeschlagen", "Access denied - no right for requested operation"]
     */
    public function test_it_gets_no_memberships_with_no_rights(string $error): void
    {
        $this->expectException(RightException::class);
        app(MembershipFake::class)->failsToShow(16, 68, $error);

        $this->login()->membership(16, 68);
    }

}
