<?php namespace GeneaLabs\LaravelGovernor\Providers;

use GeneaLabs\LaravelCasts\Providers\Service as LaravelCastsService;
use GeneaLabs\LaravelGovernor\Console\Commands\Publish;
use GeneaLabs\LaravelGovernor\Http\ViewComposers\Layout;
use GeneaLabs\LaravelGovernor\Listeners\CreatedListener;
use GeneaLabs\LaravelGovernor\Listeners\CreatingListener;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\AggregateServiceProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;

class Service extends AggregateServiceProvider
{
    protected $defer = false;
    protected $providers = [
        LaravelCastsService::class,
    ];

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function boot(GateContract $gate)
    {
        app('events')->listen('eloquent.creating: *', CreatingListener::class);
        app('events')->listen('eloquent.created: *', CreatedListener::class);

        $this->publishes([
            __DIR__ . '/../../config/config.php' => config_path('genealabs-laravel-governor.php')
        ], 'config');
        $this->publishes([
            __DIR__ . '/../../public' => public_path('genealabs-laravel-governor')
        ], 'assets');
        $this->publishes([
            __DIR__ . '/../../resources/views' => base_path('resources/views/vendor/genealabs-laravel-governor')
        ], 'views');
        $this->publishes([
            __DIR__ . '/../../database/migrations' => base_path('database/migrations')
        ], 'migrations');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'genealabs-laravel-governor');

        if (Schema::hasTable('entities')) {
            $this->parsePolicies($gate);
        }
    }

    public function register()
    {
        parent::register();

        $this->mergeConfigFrom(__DIR__ . '/../../config/config.php', 'genealabs-laravel-governor');
        $this->commands(Publish::class);
    }

    public function provides() : array
    {
        return [];
    }

    protected function parsePolicies(GateContract $gate)
    {
        $actionClass = config("laravel-governor.models.action");
        $entityClass = config("laravel-governor.models.entity");
        $ownershipClass = config("laravel-governor.models.ownership");
        $permissionClass = config("laravel-governor.models.permission");
        $roleClass = config("laravel-governor.models.role");

        $reflection = new ReflectionClass($gate);
        $property = $reflection->getProperty('policies');
        $property->setAccessible(true);
        collect($property->getValue($gate))
            ->transform(function ($policyClass) {
                return $this->newEntity($policyClass);
            })
            ->values()
            ->filter()
            ->each(function ($entity) use ($actionClass, $entityClass, $ownershipClass, $permissionClass, $roleClass) {
                (new $entityClass)->firstOrCreate(['name' => $entity]);
                $superadmin = (new $roleClass)->whereName('SuperAdmin')->first();
                $ownership = (new $ownershipClass)->whereName('any')->first();
                (new $actionClass)->all()->each(function ($action) use ($entity, $superadmin, $ownership, $permissionClass) {
                    $permission = new $permissionClass;
                    $permission->role()->associate($superadmin);
                    $permission->action()->associate($action);
                    $permission->ownership()->associate($ownership);
                    $permission->entity()->associate($entity);
                    $permission->save();
                });
            });
        (new $entityClass)
            ->with("permissions")
            ->whereDoesntHave("permissions", function ($query) {
                $query->where("role_key", "SuperAdmin");
            })
            ->get()
            ->each(function ($entity) use ($actionClass, $permissionClass) {
                (new $actionClass)->all()->each(function ($action) use ($entity, $permissionClass) {
                    (new $permissionClass)->firstOrCreate([
                        "role_key" => "SuperAdmin",
                        "action_key" => $action->name,
                        "ownership_key" => "any",
                        "entity_key" => $entity->name,
                    ]);
                });
            });
    }

    protected function newEntity(string $policyClass)
    {
        $policyClass = collect(explode('\\', $policyClass))->last();
        $entity = str_replace('policy', '', strtolower($policyClass));

        if (in_array($entity, ['assignment', 'entity', 'permission', 'role', 'action', 'ownership'])) {
            return;
        }

        if (! app('db')->table('entities')->where('name', $entity)->exists()) {
            return $entity;
        }
    }
}
