<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminTable;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function __invoke(Request $request, string $resource, AdminTable $table)
    {
        return $table->response($request, $resource);
    }
}
