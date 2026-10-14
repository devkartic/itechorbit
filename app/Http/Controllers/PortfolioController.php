<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PortfolioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $user = User::portfolio_collections_for_user(1);

        return view('portfolio.app', compact('user'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  Portfolio  $portfolio
     * @return Response
     */
    public function show($key)
    {
        $user = User::where('portfolio_id', $key)->firstOrFail();
        $user = User::portfolio_collections_for_user($user->id);
        if (! $user) {
            abort(404);
        }
        if (empty($user->portfolio_status)) {
            abort(403, 'Access Forbidden! Portfolio has been disabled.');
        }

        return view('portfolio.app', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Portfolio $portfolio)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(Request $request, Portfolio $portfolio)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Portfolio $portfolio)
    {
        //
    }
}
