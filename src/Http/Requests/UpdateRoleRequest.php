<?php namespace GeneaLabs\LaravelGovernor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest as Request;

class UpdateRoleRequest extends Request
{
    public function authorize() : bool
    {
        $roleClass = config("laravel-governor.models.role");

        return auth()->check()
            && ($this->role
                ? auth()->user()->can("update", $this->role)
                : auth()->user()->can("create", $roleClass));
    }

    public function rules() : array
    {
        return [
            'name' => 'required|string',
            "description" => "string|nullable",
            "permissions" => "array",
        ];
    }

    public function process()
    {
        $actionClass = config("laravel-governor.models.action");
        $entityClass = config("laravel-governor.models.entity");
        $ownershipClass = config("laravel-governor.models.ownership");
        $permissionClass = config("laravel-governor.models.permission");
        $roleClass = config("laravel-governor.models.role");
        $role = $this->role
            ?? new $roleClass;
        $role->fill($this->all());

        if ($this->filled('permissions')) {
            $allActions = (new $actionClass)->all();
            $allOwnerships = (new $ownershipClass)->all();
            $allEntities = (new $entityClass)->all();
            $role->permissions()->delete();

            foreach ($this->permissions as $entity => $actions) {
                foreach ($actions as $action => $ownership) {
                    if ('no' !== $ownership) {
                        $currentAction = $allActions->find($action);
                        $currentOwnership = $allOwnerships->find($ownership);
                        $currentEntity = $allEntities->find($entity);
                        $currentPermission = new $permissionClass;
                        $currentPermission->ownership()->associate($currentOwnership);
                        $currentPermission->action()->associate($currentAction);
                        $currentPermission->role()->associate($role);
                        $currentPermission->entity()->associate($currentEntity);
                        $currentPermission->save();
                    }
                }
            }
        }

        $role->save();
    }
}
