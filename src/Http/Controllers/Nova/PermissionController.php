<?php namespace GeneaLabs\LaravelGovernor\Http\Controllers\Nova;

use GeneaLabs\LaravelGovernor\Http\Controllers\Controller;

class PermissionController extends Controller
{
    public function index() : array
    {
        $actionClass = config("genealabs-laravel-governor.models.action");
        $entityClass = config("genealabs-laravel-governor.models.entity");
        $roleClass = config("genealabs-laravel-governor.models.role");
        $teamClass = config("genealabs-laravel-governor.models.team");

        $permissibleClass = request("filter") === "team_id"
            ? $teamClass
            : $roleClass;
        $permissible = (new $permissibleClass)
            ->with("permissions.action", "permissions.entity", "permissions.ownership")
            ->where(function ($query) {
                if (request("filter") === "team_id") {
                    $query->where("id", request("value"));
                }

                if (request("filter") === "role_name") {
                    $query->where("name", request("value"));
                }
            })
            ->first();

        if (request("owner") === "yes") {
            return $permissible
                ->ownedBy
                ->effectivePermissions
                ->toArray();
        }

        $gate = app('Illuminate\Contracts\Auth\Access\Gate');
        $reflectedGate = new \ReflectionObject($gate);
        $policies = $reflectedGate->getProperty("policies");
        $policies->setAccessible(true);
        $policies = $policies->getValue($gate);

        collect(array_keys($policies))
            ->each(function ($entity) use ($entityClass) {
                $entity = strtolower(collect(explode('\\', $entity))->last());

                return (new $entityClass)
                    ->firstOrCreate([
                        'name' => $entity,
                    ]);
            });
        $entities = (new $entityClass)
            ->whereNotIn('name', ['governor_permission', 'governor_entity', "governor_action", "governor_ownership"])
            ->orderBy("group_name")
            ->orderBy("name")
            ->get();
        $actions = (new $actionClass)
            ->all();
        $permissionMatrix = [];

        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                $selectedOwnership = 'no';

                foreach ($permissible->permissions as $permissioncheck) {
                    if (($permissioncheck->entity->name === $entity->name)
                        && ($permissioncheck->action->name === $action->name)) {
                        $selectedOwnership = $permissioncheck->ownership->name;
                    }
                }

                $groupName = ucwords($entity->group_name)
                    ?: "Ungrouped";
                $permissionMatrix[$groupName][$entity->name][$action->name] = $selectedOwnership;
            }
        }

        return $permissionMatrix;
    }
}
