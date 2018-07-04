<?php

namespace Tests\Unit\Controller\Lan;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetCurrentLanTest extends TestCase
{
    use DatabaseMigrations;

    public function testSetCurrentLanHasCurrentLanSimple()
    {
        $lan = factory('App\Model\Lan')->create([
            'is_current' => true
        ]);
        $this->json('GET', 'api/lans/current')
            ->seeJsonEquals([
                'id' => $lan->id,
                'name' => $lan->name,
                'lan_start' => $lan->lan_start,
                'lan_end' => $lan->lan_end,
                'seat_reservation_start' => $lan->seat_reservation_start,
                'tournament_reservation_start' => $lan->tournament_reservation_start,
                'longitude' => number_format($lan->longitude, 7),
                'latitude' => number_format($lan->latitude, 7),
                'places' => [
                    'reserved' => 0,
                    'total' => $lan->places
                ],
                'price' => $lan->price,
                'rules' => $lan->rules,
                'description' => $lan->description,
                'images' => []
            ])
            ->seeStatusCode(200);
    }

    public function testSetCurrentLanHasCurrentLanParameters()
    {
        $lan = factory('App\Model\Lan')->create([
            'is_current' => true
        ]);
        $queryParams = ['fields' => "lan_start,lan_end,seat_reservation_start"];
        $this->json('GET', 'api/lans/current', $queryParams)
            ->seeJsonEquals([
                'id' => $lan->id,
                'lan_start' => $lan->lan_start,
                'lan_end' => $lan->lan_end,
                'seat_reservation_start' => $lan->seat_reservation_start,
            ])
            ->seeStatusCode(200);
    }

    public function testSetCurrentLanNoCurrentLan()
    {
        factory('App\Model\Lan')->create();
        $this->json('GET', 'api/lans/current')
            ->seeJsonEquals([])
            ->seeStatusCode(200);
    }
}