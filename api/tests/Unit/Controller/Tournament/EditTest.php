<?php

namespace Tests\Unit\Controller\Tournament;

use Carbon\Carbon;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class EditTest extends TestCase
{
    use DatabaseMigrations;

    protected $user;
    protected $lan;
    protected $tournament;

    protected $requestContent = [
        'tournament_id' => null,
        'name' => 'October',
        'state' => 'visible',
        'tournament_start' => null,
        'tournament_end' => null,
        'players_to_reach' => 5,
        'teams_to_reach' => 6,
        'rules' => 'The Bolsheviks seize control of Petrograd.',
        'price' => 0,
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->user = factory('App\Model\User')->create();
        $this->lan = factory('App\Model\Lan')->create();
        $this->requestContent['lan_id'] = $this->lan->id;
        $startTime = new Carbon($this->lan->lan_start);
        $this->requestContent['tournament_start'] = $startTime->addHour(1)->format('Y-m-d H:i:s');
        $endTime = new Carbon($this->lan->lan_end);
        $this->requestContent['tournament_end'] = $endTime->subHour(1)->format('Y-m-d H:i:s');
        $this->tournament = factory('App\Model\Tournament')->create([
            'lan_id' => $this->lan->id,
            'tournament_start' => $startTime->addHour(1),
            'tournament_end' => $endTime->subHour(1)
        ]);
    }

    public function testEdit(): void
    {
        $this->actingAs($this->user)
            ->json('PUT', '/api/tournament/' . $this->tournament->id, $this->requestContent)
            ->seeJsonEquals([
                'id' => 1,
                'lan_id' => $this->requestContent['lan_id'],
                'name' => $this->requestContent['name'],
                'state' => $this->requestContent['state'],
                'tournament_start' => $this->requestContent['tournament_start'],
                'tournament_end' => $this->requestContent['tournament_end'],
                'players_to_reach' => $this->requestContent['players_to_reach'],
                'teams_to_reach' => $this->requestContent['teams_to_reach'],
                'rules' => $this->requestContent['rules'],
                'price' => $this->requestContent['price'],
            ])
            ->assertResponseStatus(200);
    }

    public function testEditNameRequired(): void
    {
        $this->requestContent['name'] = null;
        $this->actingAs($this->user)
            ->json('PUT', '/api/tournament/' . $this->tournament->id, $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'name' => [
                        0 => 'The name field is required.'
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testEditNameString(): void
    {
        $this->requestContent['name'] = 1;
        $this->actingAs($this->user)
            ->json('PUT', '/api/tournament/' . $this->tournament->id, $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'name' => [
                        0 => 'The name must be a string.'
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testEditNameMaxLength(): void
    {
        $this->requestContent['name'] = str_repeat('☭', 256);
        $this->actingAs($this->user)
            ->json('PUT', '/api/tournament/' . $this->tournament->id, $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'name' => [
                        0 => 'The name may not be greater than 255 characters.'
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testEditPriceInteger(): void
    {
        $this->requestContent['price'] = '☭';
        $this->actingAs($this->user)
            ->json('PUT', '/api/tournament/' . $this->tournament->id, $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'price' => [
                        0 => 'The price must be an integer.'
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testEditPriceMin(): void
    {
        $this->requestContent['price'] = -1;
        $this->actingAs($this->user)
            ->json('PUT', '/api/tournament/' . $this->tournament->id, $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'price' => [
                        0 => 'The price must be at least 0.'
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testEditPriceDefault(): void
    {
        $this->requestContent['price'] = '';
        $this->actingAs($this->user)
            ->json('PUT', '/api/tournament/' . $this->tournament->id, $this->requestContent)
            ->seeJsonEquals([
                'id' => 1,
                'lan_id' => $this->requestContent['lan_id'],
                'name' => $this->requestContent['name'],
                'tournament_start' => $this->requestContent['tournament_start'],
                'tournament_end' => $this->requestContent['tournament_end'],
                'players_to_reach' => $this->requestContent['players_to_reach'],
                'teams_to_reach' => $this->requestContent['teams_to_reach'],
                'rules' => $this->requestContent['rules'],
                'price' => 0,
            ])
            ->assertResponseStatus(201);
    }

    public function testEditTournamentStartRequired(): void
    {
        $this->requestContent['tournament_start'] = null;
        $this->actingAs($this->user)
            ->json('PUT', '/api/tournament/' . $this->tournament->id, $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'tournament_start' => [
                        0 => 'The tournament start field is required.'
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testEditTournamentStartAfterOrEqualLanStartTime(): void
    {

        $startTime = new Carbon($this->lan->lan_start);
        $this->requestContent['tournament_start'] = $startTime->subHour(1)->format('Y-m-d H:i:s');
        $this->actingAs($this->user)
            ->json('PUT', '/api/tournament/' . $this->tournament->id, $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'tournament_start' => [
                        0 => 'The tournament start time must be after or equal the lan start time.'
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testEditTournamentEndRequired(): void
    {
        $this->requestContent['tournament_end'] = null;
        $this->actingAs($this->user)
            ->json('PUT', '/api/tournament/' . $this->tournament->id, $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'tournament_end' => [
                        0 => 'The tournament end field is required.'
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testEditTournamentEndBeforeOrEqualLanEndTime(): void
    {
        $endTime = new Carbon($this->lan->lan_end);
        $this->requestContent['tournament_end'] = $endTime->addHour(1)->format('Y-m-d H:i:s');
        $this->actingAs($this->user)
            ->json('PUT', '/api/tournament/' . $this->tournament->id, $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'tournament_end' => [
                        0 => 'The tournament end time must be before or equal the lan end time.'
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testEditPlayersToReachRequired(): void
    {
        $this->requestContent['players_to_reach'] = null;
        $this->actingAs($this->user)
            ->json('PUT', '/api/tournament/' . $this->tournament->id, $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'players_to_reach' => [
                        0 => 'The players to reach field is required.'
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testEditPlayersToReachMin(): void
    {
        $this->requestContent['players_to_reach'] = 0;
        $this->actingAs($this->user)
            ->json('PUT', '/api/tournament/' . $this->tournament->id, $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'players_to_reach' => [
                        0 => 'The players to reach must be at least 1.'
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testEditPlayersToReachInteger(): void
    {
        $this->requestContent['players_to_reach'] = '☭';
        $this->actingAs($this->user)
            ->json('PUT', '/api/tournament/' . $this->tournament->id, $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'players_to_reach' => [
                        0 => 'The players to reach must be an integer.'
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testEditTeamsToReachRequired(): void
    {
        $this->requestContent['teams_to_reach'] = null;
        $this->actingAs($this->user)
            ->json('PUT', '/api/tournament/' . $this->tournament->id, $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'teams_to_reach' => [
                        0 => 'The teams to reach field is required.'
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testEditTeamsToReachMin(): void
    {
        $this->requestContent['teams_to_reach'] = 0;
        $this->actingAs($this->user)
            ->json('PUT', '/api/tournament/' . $this->tournament->id, $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'teams_to_reach' => [
                        0 => 'The teams to reach must be at least 1.'
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testEditTeamsToReachInteger(): void
    {
        $this->requestContent['teams_to_reach'] = '☭';
        $this->actingAs($this->user)
            ->json('PUT', '/api/tournament/' . $this->tournament->id, $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'teams_to_reach' => [
                        0 => 'The teams to reach must be an integer.'
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testEditRulesRequired(): void
    {
        $this->requestContent['rules'] = null;
        $this->actingAs($this->user)
            ->json('PUT', '/api/tournament/' . $this->tournament->id, $this->requestContent)
            ->seeJsonEquals([
                'success' => false,
                'status' => 400,
                'message' => [
                    'rules' => [
                        0 => 'The rules field is required.'
                    ],
                ]
            ])
            ->assertResponseStatus(400);
    }

    public function testEditRulesString(): void
    {
        $this->requestContent['rules'] = 1;
        $this->actingAs($this->user)
            ->json('PUT', '/api/tournament/' . $this->tournament->id, $this->requestContent)
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
