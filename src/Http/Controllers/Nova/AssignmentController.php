<?php namespace GeneaLabs\LaravelGovernor\Http\Controllers\Nova;

use GeneaLabs\LaravelGovernor\Http\Controllers\Controller;
use GeneaLabs\LaravelGovernor\Role;
use Illuminate\Http\Response;

class AssignmentController extends Controller
{
    public function update(string $role) : Response
    {
        $role = (new Role)
            ->find($role);
        $role->users()->sync(request("user_ids"));

        return response(null, 204);
    }
}