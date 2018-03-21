<?php namespace GeneaLabs\LaravelGovernor\Http\Controllers;

use GeneaLabs\LaravelGovernor\Action;
use GeneaLabs\LaravelGovernor\Entity;
use GeneaLabs\LaravelGovernor\Http\Requests\CreateRoleRequest;
use GeneaLabs\LaravelGovernor\Http\Requests\UpdateRoleRequest;
use GeneaLabs\LaravelGovernor\Ownership;
use GeneaLabs\LaravelGovernor\Permission;
use GeneaLabs\LaravelGovernor\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RolesController extends Controller
{
    public function index() : View
    {
        $framework = $this->framework;
        $this->authorize('view', (new Role()));
        $roles = (new Role)->orderBy('name')->get();

        return view("genealabs-laravel-governor::roles.{$framework}-index")
            ->with(compact(
                "framework",
                "roles"
            ));
    }

    public function create() : View
    {
        $role = new Role();
        $this->authorize('create', $role);

        return view('genealabs-laravel-governor::roles.create', compact('role'));
    }

    public function store(CreateRoleRequest $request) : RedirectResponse
    {
        (new Role)->create($request->except(['_token']));

        return redirect()->route('genealabs.laravel-governor.roles.index');
    }

    public function edit($name) : View
    {
        $role = (new Role)->with('permissions')->find($name);
        $this->authorize('edit', $role);
        collect(array_keys(app('Illuminate\Contracts\Auth\Access\Gate')->policies()))
            ->each(function ($entity) {
                $entity = strtolower(collect(explode('\\', $entity))->last());

                return (new Entity)
                    ->firstOrCreate([
                        'name' => $entity,
                    ]);
            });
        $entities = (new Entity)->whereNotIn('name', ['permission', 'entity'])->get();
        $actions = (new Action)->all();
        $ownerships = (new Ownership)->all();
        $permissionMatrix = [];

        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                $selectedOwnership = 'no';

                foreach ($role->permissions as $permissioncheck) {
                    if (($permissioncheck->entity->name === $entity->name)
                        && ($permissioncheck->action->name === $action->name)
                    ) {
                        $selectedOwnership = $permissioncheck->ownership->name;
                    }
                }

                $permissionMatrix[$entity->name][$action->name] = $selectedOwnership;
            }
        }

        $ownershipOptions = array_merge(['no' => 'no'], $ownerships->pluck('name', 'name')->toArray());

        return view('genealabs-laravel-governor::roles.edit', compact('role', 'permissionMatrix', 'ownershipOptions'));
    }

    public function update(UpdateRoleRequest $request) : RedirectResponse
    {
        $request->process();

        return redirect()->route('genealabs.laravel-governor.roles.index');
    }

    public function destroy($name) : RedirectResponse
    {
        $role = (new Role)->find($name);
        $this->authorize('remove', $role);
        $role->delete();

        return redirect()->route('genealabs.laravel-governor.roles.index');
    }
}
