<?php

use Illuminate\Database\Seeder;

class LaravelGovernorPermissionsTableSeeder extends Seeder
{
    public function run()
    {
        $actionClass = config("genealabs-laravel-governor.models.action");
        $entityClass = config("genealabs-laravel-governor.models.entity");
        $ownershipClass = config("genealabs-laravel-governor.models.ownership");
        $permissionClass = config("genealabs-laravel-governor.models.permission");
        $roleClass = config("genealabs-laravel-governor.models.role");

        $superadmin = (new $roleClass)->whereName('SuperAdmin')->get()->first();
        $actions = (new $actionClass)->all();
        $ownership = (new $ownershipClass)->whereName('any')->get()->first();
        $entities = (new $entityClass)->all();

        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                (new $permissionClass)->firstOrCreate([
                    "role_name" => $superadmin->name,
                    "action_name" => $action->name,
                    "ownership_name" => $ownership->name,
                    "entity_name" => $entity->name,
                ]);
            }
        }
    }
}
