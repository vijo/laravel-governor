<?php namespace GeneaLabs\LaravelGovernor\Policies;

use GeneaLabs\LaravelGovernor\Traits\GovernorOwnedByField;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BasePolicy
{
    use GovernorOwnedByField;

    protected $entity;
    protected $permissions;

    public function __construct()
    {
        $this->createGovernorOwnedByFieldsByPolicy($this);
        $policyClass = collect(explode('\\', get_class($this)))->last();
        $this->entity = str_replace('policy', '', strtolower($policyClass));
        $this->permissions = $this->getPermissions();
    }

    protected function getPermissions() : Collection
    {
        return app("cache")->remember("governorpermissions", 5, function () {
            $permissionClass = config("genealabs-laravel-governor.models.permission");

            return (new $permissionClass)->get();
        });
    }

    public function create(Model $user) : bool
    {
        return $this->validatePermissions(
            $user,
            'create',
            $this->entity
        );
    }

    public function update(Model $user, Model $model) : bool
    {
        return $this->validatePermissions(
            $user,
            'update',
            $this->entity,
            $model
        );
    }

    public function viewAny(Model $user) : bool
    {
        return $this->validatePermissions(
            $user,
            'viewAny',
            $this->entity
        );
    }

    public function view(Model $user, Model $model) : bool
    {
        return $this->validatePermissions(
            $user,
            'view',
            $this->entity,
            $model
        );
    }

    public function delete(Model $user, Model $model) : bool
    {
        return $this->validatePermissions(
            $user,
            'delete',
            $this->entity,
            $model
        );
    }

    public function restore(Model $user, Model $model) : bool
    {
        return $this->validatePermissions(
            $user,
            'restore',
            $this->entity,
            $model
        );
    }

    public function forceDelete(Model $user, Model $model) : bool
    {
        return $this->validatePermissions(
            $user,
            'forceDelete',
            $this->entity,
            $model
        );
    }

    protected function validatePermissions(
        Model $user,
        string $action,
        string $entity,
        Model $model = null
    ) : bool {
        $user->load("roles", "teams");

        if ($user->hasRole("SuperAdmin")) {
            return true;
        }

        if ($user->roles->isEmpty()
            && $user->teams->isEmpty()
        ) {
            return false;
        }

        $ownership = 'other';

        if ($user->getKey() === $model->governor_owned_by) {
            $ownership = 'own';
        }

        $filteredPermissions = $this->filterPermissions($action, $entity, $ownership);

        foreach ($filteredPermissions as $permission) {
            if ($user->roles->contains($permission->role)
                || $user->teams->contains($permission->team)
            ) {
                return true;
            }
        }

        return false;
    }

    protected function filterPermissions($action, $entity, $ownership)
    {
        $filteredPermissions = $this->permissions->filter(function ($permission) use ($action, $entity, $ownership) {
            return ($permission->action_name === $action
                && $permission->entity_name === $entity
                && in_array($permission->ownership_name, [$ownership, 'any']));
        });

        return $filteredPermissions;
    }
}
