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

    protected $user;

    protected $requestContent = [
        'name' => "Bolshevik Revolution",
        'lan_start' => "2100-10-11 12:00:00",
        'lan_end' => "2100-10-12 12:00:00",
        'seat_reservation_start' => "2100-10-04 12:00:00",
        'tournament_reservation_start' => "2100-10-07 00:00:00",
        "event_key_id" => "",
        "public_key_id" => "",
        "secret_key_id" => "",
        "latitude" => -67.5,
        "longitude" => 64.033333,
        "places" => 10,
        "price" => 0,
        "rules" => '☭',
        "description" => '☭'
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->requestContent['event_key_id'] = env('EVENT_KEY_ID');
        $this->requestContent['secret_key_id'] = env('SECRET_KEY_ID');
        $this->requestContent['public_key_id'] = env('PUBLIC_KEY_ID');

        $this->user = factory('App\Model\User')->create();
    }


    public function testCreateLan(): void
    {
        $this->actingAs($this->user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'name' => $this->requestContent['name'],
                'lan_start' => $this->requestContent['lan_start'],
                'lan_end' => $this->requestContent['lan_end'],
                'seat_reservation_start' => $this->requestContent['seat_reservation_start'],
                'tournament_reservation_start' => $this->requestContent['tournament_reservation_start'],
                "event_key_id" => $this->requestContent['event_key_id'],
                "public_key_id" => $this->requestContent['public_key_id'],
                "secret_key_id" => $this->requestContent['secret_key_id'],
                "latitude" => $this->requestContent['latitude'],
                "longitude" => $this->requestContent['longitude'],
                "price" => 0,
                "places" => $this->requestContent['places'],
                "price" => $this->requestContent['price'],
                "rules" => $this->requestContent['rules'],
                "description" => $this->requestContent['description'],
                "id" => 1
            ])
            ->assertResponseStatus(201);
    }

    public function testCreateLanPriceDefault(): void
    {
        $this->requestContent['price'] = '';
        $this->actingAs($this->user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'name' => $this->requestContent['name'],
                'lan_start' => $this->requestContent['lan_start'],
                'lan_end' => $this->requestContent['lan_end'],
                'seat_reservation_start' => $this->requestContent['seat_reservation_start'],
                'tournament_reservation_start' => $this->requestContent['tournament_reservation_start'],
                "event_key_id" => $this->requestContent['event_key_id'],
                "public_key_id" => $this->requestContent['public_key_id'],
                "secret_key_id" => $this->requestContent['secret_key_id'],
                "places" => $this->requestContent['places'],
                "latitude" => $this->requestContent['latitude'],
                "longitude" => $this->requestContent['longitude'],
                "price" => 0,
                "rules" => $this->requestContent['rules'],
                "description" => $this->requestContent['description'],
                "id" => 1
            ])
            ->assertResponseStatus(201);
    }

    public function testCreateLanNameRequired(): void
    {
        $this->requestContent['name'] = '';
        $this->actingAs($this->user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'name' => [
                        0 => 'The name field is required.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanNameString(): void
    {
        $this->requestContent['name'] = 1;
        $this->actingAs($this->user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'name' => [
                        0 => 'The name must be a string.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanNameMaxLength(): void
    {
        $this->requestContent['name'] = str_repeat('☭', 256);
        $this->actingAs($this->user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'name' => [
                        0 => 'The name may not be greater than 255 characters.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanStartRequired(): void
    {
        $this->requestContent['lan_start'] = '';
        $this->actingAs($this->user)
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
    public function testCreateLanAfterReservation(): void
    {
        // Set the lan_start date to one day before reservation
        $newLanStart = (new DateTime($this->requestContent['seat_reservation_start']));
        $newLanStart->sub(new DateInterval('P1D'));
        $this->requestContent['lan_start'] = $newLanStart->format('Y-m-d\TH:i:s');
        // Set the tournament_reservation_start to one day before the new lan_start
        $newTournamentStart = (new DateTime($this->requestContent['lan_start']));
        $newTournamentStart->sub(new DateInterval('P1D'));
        $this->requestContent['tournament_reservation_start'] = $newTournamentStart->format('Y-m-d\TH:i:s');
        // Execute request
        $this->actingAs($this->user)
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
    public function testCreateLanAfterTournamentStart(): void
    {
        // Set the lan_start date to one day before tournament start
        $newLanStart = (new DateTime($this->requestContent['tournament_reservation_start']));
        $newLanStart->sub(new DateInterval('P1D'));
        $this->requestContent['lan_start'] = $newLanStart->format('Y-m-d\TH:i:s');
        // Set the seat_reservation_start to one day before the new lan_start
        $newTournamentStart = (new DateTime($this->requestContent['lan_start']));
        $newTournamentStart->sub(new DateInterval('P1D'));
        $this->requestContent['seat_reservation_start'] = $newTournamentStart->format('Y-m-d\TH:i:s');
        // Execute request
        $this->actingAs($this->user)
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
    public function testCreateLanEndRequired(): void
    {
        $this->requestContent['lan_end'] = '';
        $this->actingAs($this->user)
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
    public function testCreateLanEndAfterLanStart(): void
    {
        // Set the lan end date to one day before lan start
        $newLanEnd = (new DateTime($this->requestContent['lan_start']));
        $newLanEnd->sub(new DateInterval('P1D'));
        $this->requestContent['lan_end'] = $newLanEnd->format('Y-m-d\TH:i:s');
        // Execute request
        $this->actingAs($this->user)
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
    public function testCreateLanReservationStartRequired(): void
    {
        $this->requestContent['seat_reservation_start'] = '';
        $this->actingAs($this->user)
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
    public function testCreateLanReservationStartAfterOrEqualNow(): void
    {
        // Set the seat reservation date to yesterday
        $newSeatReservationDate = (new DateTime());
        $newSeatReservationDate->sub(new DateInterval('P1D'));
        $this->requestContent['seat_reservation_start'] = $newSeatReservationDate->format('Y-m-d\TH:i:s');
        // Execute request
        $this->actingAs($this->user)
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
    public function testCreateLanTournamentStartRequired(): void
    {
        $this->requestContent['tournament_reservation_start'] = '';
        $this->actingAs($this->user)
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
    public function testCreateLanTournamentStartAfterOrEqualNow(): void
    {
        // Set the reservation date to yesterday
        $newReservationDate = (new DateTime());
        $newReservationDate->sub(new DateInterval('P1D'));
        $this->requestContent['tournament_reservation_start'] = $newReservationDate->format('Y-m-d\TH:i:s');
        // Execute request
        $this->actingAs($this->user)
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

    public function testCreateLanEventKeyIdRequired(): void
    {
        $this->requestContent['event_key_id'] = '';
        $this->actingAs($this->user)
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

    public function testCreateLanEventKeyIdMaxLength(): void
    {
        $this->requestContent['event_key_id'] = str_repeat('☭', 256);
        $this->actingAs($this->user)
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

    public function testCreateLanPublicKeyIdRequired(): void
    {
        $this->requestContent['public_key_id'] = '';
        $this->actingAs($this->user)
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

    public function testCreateLanPublicKeyIdMaxLength(): void
    {
        $this->requestContent['public_key_id'] = str_repeat('☭', 256);
        $this->actingAs($this->user)
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

    public function testCreateLanSecretKeyIdRequired(): void
    {
        $this->requestContent['secret_key_id'] = '';
        $this->actingAs($this->user)
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

    public function testCreateLanSecretKeyIdMaxLength(): void
    {
        $this->requestContent['secret_key_id'] = str_repeat('☭', 256);
        $this->actingAs($this->user)
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

    public function testCreateLanLatitudeRequired(): void
    {
        $this->requestContent['latitude'] = '';
        $this->actingAs($this->user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'latitude' => [
                        0 => 'The latitude field is required.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanLatitudeMin(): void
    {
        $this->requestContent['latitude'] = -86;
        $this->actingAs($this->user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'latitude' => [
                        0 => 'The latitude must be at least -85.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanLatitudeMax(): void
    {
        $this->requestContent['latitude'] = 86;
        $this->actingAs($this->user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'latitude' => [
                        0 => 'The latitude may not be greater than 85.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanLatitudeNumeric(): void
    {
        $this->requestContent['latitude'] = '☭';
        $this->actingAs($this->user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'latitude' => [
                        0 => 'The latitude must be a number.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanLongitudeRequired(): void
    {
        $this->requestContent['longitude'] = '';
        $this->actingAs($this->user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'longitude' => [
                        0 => 'The longitude field is required.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanLongitudeMin(): void
    {
        $this->requestContent['longitude'] = -181;
        $this->actingAs($this->user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'longitude' => [
                        0 => 'The longitude must be at least -180.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanLongitudeMax(): void
    {
        $this->requestContent['longitude'] = 181;
        $this->actingAs($this->user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'longitude' => [
                        0 => 'The longitude may not be greater than 180.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanLongitudeNumeric(): void
    {
        $this->requestContent['longitude'] = '☭';
        $this->actingAs($this->user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'longitude' => [
                        0 => 'The longitude must be a number.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanPriceMinimum()
    {
        $this->requestContent['price'] = '-1';
        $this->actingAs($this->user)
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

    public function testCreateLanPriceInteger(): void
    {
        $this->requestContent['price'] = '☭';
        $this->actingAs($this->user)
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

    public function testCreateLanSecretKeyId(): void
    {
        $this->requestContent['secret_key_id'] = '☭';
        $this->actingAs($this->user)
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

    public function testCreateLanEventKeyId(): void
    {
        $this->requestContent['event_key_id'] = '☭';
        $this->actingAs($this->user)
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

    public function testCreateLanPlacesRequired(): void
    {
        $this->requestContent['places'] = '';
        $this->actingAs($this->user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'places' => [
                        0 => 'The places field is required.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanPlacesMin(): void
    {
        $this->requestContent['places'] = 0;
        $this->actingAs($this->user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'places' => [
                        0 => 'The places must be at least 1.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanPlacesInt(): void
    {
        $this->requestContent['places'] = '☭';
        $this->actingAs($this->user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'places' => [
                        0 => 'The places must be an integer.',
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testCreateLanRulesString(): void
    {
        $this->requestContent['rules'] = 1;
        $this->actingAs($this->user)
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

    public function testCreateLanDescriptionString(): void
    {
        $this->requestContent['description'] = 1;
        $this->actingAs($this->user)
            ->json('POST', '/api/lan', $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'description' => [
                        0 => 'The description must be a string.'
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }
}