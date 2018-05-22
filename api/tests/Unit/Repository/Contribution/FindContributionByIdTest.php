<?php

namespace Tests\Unit\Repository\Contribution;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class FindContributionByIdTest extends TestCase
{
    use DatabaseMigrations;

    protected $contributionRepository;

    protected $contribution;

    public function setUp()
    {
        parent::setUp();
        $this->contributionRepository = $this->app->make('App\Repositories\Implementation\ContributionRepositoryImpl');

        $this->contribution = factory('App\Model\Contribution')->create([
            'user_full_name' => 'Karl Marx'
        ]);
    }

    public function testFindContributionById()
    {
        $result = $this->contributionRepository->findContributionById($this->contribution->id);

        $this->assertEquals($this->contribution->id, $result->id);
    }
}