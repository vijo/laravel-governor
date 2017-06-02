<?php namespace GeneaLabs\LaravelGovernor\Http\Controllers;

use GeneaLabs\LaravelGovernor\Action;
use GeneaLabs\LaravelGovernor\Entity;
use GeneaLabs\LaravelGovernor\Permission;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function resetSuperAdminPermissions()
    {
        (new Permission)->where('role_key', 'SuperAdmin')->delete();
        $entities = (new Entity)->all();
        $actions = (new Action)->all();

        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                (new Permission)->updateOrCreate([
                    'role_key' => 'SuperAdmin',
                    'entity_key' => $entity->name,
                    'action_key' => $action->name,
                    'ownership_key' => 'any',
                ]);
            }
        }
    }
}
