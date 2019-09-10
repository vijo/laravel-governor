<?php

use GeneaLabs\LaravelGovernor\Team;
use Illuminate\Database\Seeder;

class LaravelGovernorEntitiesTableSeeder extends Seeder
{
    public function run()
    {
        (new Team)->parsePolicies();
    }
}
