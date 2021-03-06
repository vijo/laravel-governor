<?php namespace GeneaLabs\LaravelGovernor\Http\Controllers\Nova;

use GeneaLabs\LaravelGovernor\Http\Controllers\Controller;
use GeneaLabs\LaravelGovernor\Role;
use Illuminate\Http\Response;

class AssignmentController extends Controller
{
    public function update(string $role) : Response
    {
        $roleClass = config("genealabs-laravel-governor.models.role");
        (new $roleClass)
            ->with("users")
            ->orderBy("name")
            ->get()
            ->where("name", $role)
            ->first()
            ->users()
            ->sync(request("user_ids"));

        return response(null, 204);
    }
}
