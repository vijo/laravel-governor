<?php namespace GeneaLabs\LaravelGovernor\Providers;

use GeneaLabs\Bones\Macros\BonesMacrosServiceProvider;
use GeneaLabs\LaravelGovernor\Listeners\CreatedListener;
use GeneaLabs\LaravelGovernor\Listeners\CreatingListener;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\AggregateServiceProvider;
use Illuminate\Support\Facades\Event;

class LaravelGovernorServiceProvider extends AggregateServiceProvider
{
    protected $defer = false;
    protected $policies = [
        'GeneaLabs\LaravelGovernor\Action' => 'GeneaLabs\LaravelGovernor\Policies\ActionPolicy',
        'GeneaLabs\LaravelGovernor\Assignment' => 'GeneaLabs\LaravelGovernor\Policies\AssignmentPolicy',
        'GeneaLabs\LaravelGovernor\Entity' => 'GeneaLabs\LaravelGovernor\Policies\EntityPolicy',
        'GeneaLabs\LaravelGovernor\Ownership' => 'GeneaLabs\LaravelGovernor\Policies\OwnershipPolicy',
        'GeneaLabs\LaravelGovernor\Permission' => 'GeneaLabs\LaravelGovernor\Policies\PermissionPolicy',
        'GeneaLabs\LaravelGovernor\Role' => 'GeneaLabs\LaravelGovernor\Policies\RolePolicy',
    ];
    protected $providers = [
        BonesMacrosServiceProvider::class,
    ];

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(GateContract $gate)
    {
        $this->registerPolicies($gate);

        if (! $this->app->routesAreCached()) {
            require __DIR__ . '/../Http/routes.php';
        }

        Event::listen('eloquent.creating: *', CreatingListener::class);
        Event::listen('eloquent.created: *', CreatedListener::class);

        $this->publishes([__DIR__ . '/../../config/config.php' => config_path('genealabs-laravel-governor.php')], 'genealabs-laravel-governor');
        $this->publishes([__DIR__ . '/../../public' => public_path('genealabs-laravel-governor')], 'genealabs-laravel-governor');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'genealabs-laravel-governor');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('genealabs-laravel-governor', function ($app) {
			return new LaravelGovernor($app);
		});
    }

    public function registerPolicies(GateContract $gate)
    {
        foreach ($this->policies as $key => $value) {
            $gate->policy($key, $value);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['genealabs-laravel-governor'];
    }
}