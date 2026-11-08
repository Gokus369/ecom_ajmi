<?php

namespace App\Controllers;

class Errors extends BaseController
{
    /** Set as the 404 override in Config\Routing, so the framework has already sent the status. */
    public function notFound(): string
    {
        return view('errors/not_found', ['title' => 'Page not found']);
    }
}
