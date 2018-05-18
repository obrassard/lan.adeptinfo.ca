<?php

namespace Tests\Unit\Controller\Lan;

use DateInterval;
use DateTime;
use Exception;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateLanTest extends TestCase
{
    use DatabaseMigrations;

    protected $requestContent = [
        'lan_start' => "2100-10-11T12:00:00",
        'lan_end' => "2100-10-12T12:00:00",
        'seat_reservation_start' => "2100-10-04T12:00:00",
        'tournament_reservation_start' => "2100-10-07T00:00:00",
        "event_key_id" => "",
        "public_key_id" => "",
        "secret_key_id" => "",
        "price" => 0,
        "rules" => '☭'
    ];

    public function setUp()
    {
        parent::setUp();

        $this->requestContent['event_key_id'] = env('EVENT_KEY_ID');
        $this->requestContent['secret_key_id'] = env('SECRET_KEY_ID');
        $this->requestContent['public_key_id'] = env('PUBLIC_KEY_ID');
    }


    public function testCreateLan()
    {
        $user = factory('App\Model\User')->make();
        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'lan_start' => $this->requestContent['lan_start'],
                'lan_end' => $this->requestContent['lan_end'],
                'seat_reservation_start' => $this->requestContent['seat_reservation_start'],
                'tournament_reservation_start' => $this->requestContent['tournament_reservation_start'],
                "event_key_id" => $this->requestContent['event_key_id'],
                "public_key_id" => $this->requestContent['public_key_id'],
                "secret_key_id" => $this->requestContent['secret_key_id'],
                "price" => $this->requestContent['price'],
                "rules" => $this->requestContent['rules'],
                "id" => 1
            ])
            ->assertResponseStatus(201);
    }

    public function testCreateLanPriceDefault()
    {
        $user = factory('App\Model\User')->make();
        $this->requestContent['price'] = '';
        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'lan_start' => $this->requestContent['lan_start'],
                'lan_end' => $this->requestContent['lan_end'],
                'seat_reservation_start' => $this->requestContent['seat_reservation_start'],
                'tournament_reservation_start' => $this->requestContent['tournament_reservation_start'],
                "event_key_id" => $this->requestContent['event_key_id'],
                "public_key_id" => $this->requestContent['public_key_id'],
                "secret_key_id" => $this->requestContent['secret_key_id'],
                "price" => 0,
                "rules" => $this->requestContent['rules'],
                "id" => 1
            ])
            ->assertResponseStatus(201);
    }

    /**
     * @throws \Exception
     */
    public function testCreateLanStartRequired()
    {
        $user = factory('App\Model\User')->make();
        $this->requestContent['lan_start'] = '';
        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'lan_start' => [
                        0 => 'The lan start field is required.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    /**
     * @throws Exception
     */
    public function testCreateLanAfterReservation()
    {
        $user = factory('App\Model\User')->make();
        // Set the lan_start date to one day before reservation
        $newLanStart = (new DateTime($this->requestContent['seat_reservation_start']));
        $newLanStart->sub(new DateInterval('P1D'));
        $this->requestContent['lan_start'] = $newLanStart->format('Y-m-d\TH:i:s');
        // Set the tournament_reservation_start to one day before the new lan_start
        $newTournamentStart = (new DateTime($this->requestContent['lan_start']));
        $newTournamentStart->sub(new DateInterval('P1D'));
        $this->requestContent['tournament_reservation_start'] = $newTournamentStart->format('Y-m-d\TH:i:s');
        // Execute request
        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'lan_start' => [
                        0 => 'The lan start must be a date after seat reservation start.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    /**
     * @throws Exception
     */
    public function testCreateLanAfterTournamentStart()
    {
        $user = factory('App\Model\User')->make();
        // Set the lan_start date to one day before tournament start
        $newLanStart = (new DateTime($this->requestContent['tournament_reservation_start']));
        $newLanStart->sub(new DateInterval('P1D'));
        $this->requestContent['lan_start'] = $newLanStart->format('Y-m-d\TH:i:s');
        // Set the seat_reservation_start to one day before the new lan_start
        $newTournamentStart = (new DateTime($this->requestContent['lan_start']));
        $newTournamentStart->sub(new DateInterval('P1D'));
        $this->requestContent['seat_reservation_start'] = $newTournamentStart->format('Y-m-d\TH:i:s');
        // Execute request
        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'lan_start' => [
                        0 => 'The lan start must be a date after tournament reservation start.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    /**
     * @throws \Exception
     */
    public function testCreateLanEndRequired()
    {
        $user = factory('App\Model\User')->make();
        $this->requestContent['lan_end'] = '';
        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'lan_end' => [
                        0 => 'The lan end field is required.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    /**
     * @throws Exception
     */
    public function testCreateLanEndAfterLanStart()
    {
        $user = factory('App\Model\User')->make();
        // Set the lan end date to one day before lan start
        $newLanEnd = (new DateTime($this->requestContent['lan_start']));
        $newLanEnd->sub(new DateInterval('P1D'));
        $this->requestContent['lan_end'] = $newLanEnd->format('Y-m-d\TH:i:s');
        // Execute request
        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'lan_end' => [
                        0 => 'The lan end must be a date after lan start.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    /**
     * @throws \Exception
     */
    public function testCreateLanReservationStartRequired()
    {
        $user = factory('App\Model\User')->make();
        $this->requestContent['seat_reservation_start'] = '';
        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'seat_reservation_start' => [
                        0 => 'The seat reservation start field is required.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    /**
     * @throws Exception
     */
    public function testCreateLanReservationStartAfterOrEqualNow()
    {
        $user = factory('App\Model\User')->make();
        // Set the seat reservation date to yesterday
        $newSeatReservationDate = (new DateTime());
        $newSeatReservationDate->sub(new DateInterval('P1D'));
        $this->requestContent['seat_reservation_start'] = $newSeatReservationDate->format('Y-m-d\TH:i:s');
        // Execute request
        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'seat_reservation_start' => [
                        0 => 'The seat reservation start must be a date after or equal to now.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    /**
     * @throws \Exception
     */
    public function testCreateLanTournamentStartRequired()
    {
        $user = factory('App\Model\User')->make();
        $this->requestContent['tournament_reservation_start'] = '';
        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'tournament_reservation_start' => [
                        0 => 'The tournament reservation start field is required.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    /**
     * @throws Exception
     */
    public function testCreateLanTournamentStartAfterOrEqualNow()
    {
        $user = factory('App\Model\User')->make();
        // Set the reservation date to yesterday
        $newReservationDate = (new DateTime());
        $newReservationDate->sub(new DateInterval('P1D'));
        $this->requestContent['tournament_reservation_start'] = $newReservationDate->format('Y-m-d\TH:i:s');
        // Execute request
        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'tournament_reservation_start' => [
                        0 => 'The tournament reservation start must be a date after or equal to now.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanEventKeyIdRequired()
    {
        $user = factory('App\Model\User')->make();
        $this->requestContent['event_key_id'] = '';
        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'event_key_id' => [
                        0 => 'The event key id field is required.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanEventKeyIdMaxLength()
    {
        $user = factory('App\Model\User')->make();
        $this->requestContent['event_key_id'] = str_repeat('☭', 256);
        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'event_key_id' => [
                        0 => 'The event key id may not be greater than 255 characters.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanPublicKeyIdRequired()
    {
        // Required
        $user = factory('App\Model\User')->make();
        $this->requestContent['public_key_id'] = '';
        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'public_key_id' => [
                        0 => 'The public key id field is required.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanPublicKeyIdMaxLength()
    {
        $user = factory('App\Model\User')->make();
        $this->requestContent['public_key_id'] = str_repeat('☭', 256);
        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'public_key_id' => [
                        0 => 'The public key id may not be greater than 255 characters.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanSecretKeyIdRequired()
    {
        $user = factory('App\Model\User')->make();
        $this->requestContent['secret_key_id'] = '';
        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'secret_key_id' => [
                        0 => 'The secret key id field is required.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanSecretKeyIdMaxLength()
    {
        $user = factory('App\Model\User')->make();
        $this->requestContent['secret_key_id'] = str_repeat('☭', 256);
        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'secret_key_id' => [
                        0 => 'The secret key id may not be greater than 255 characters.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanPriceMinimum()
    {
        $user = factory('App\Model\User')->make();
        $this->requestContent['price'] = '-1';
        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'price' => [
                        0 => 'The price must be at least 0.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanPriceInteger()
    {
        $user = factory('App\Model\User')->make();
        $this->requestContent['price'] = '☭';
        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'price' => [
                        0 => 'The price must be an integer.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanSecretKeyId()
    {
        $user = factory('App\Model\User')->make();
        $this->requestContent['secret_key_id'] = '☭';
        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'secret_key_id' => [
                        0 => 'Secret key id: ' . $this->requestContent['secret_key_id'] . ' is not valid.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanEventKeyId()
    {
        $user = factory('App\Model\User')->make();
        $this->requestContent['event_key_id'] = '☭';
        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'event_key_id' => [
                        0 => 'Event key id: ' . $this->requestContent['event_key_id'] . ' is not valid.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanRulesString()
    {
        $user = factory('App\Model\User')->create();
        $this->requestContent['rules'] = 1;

        $this->actingAs($user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'rules' => [
                        0 => 'The rules must be a string.'
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }
}
