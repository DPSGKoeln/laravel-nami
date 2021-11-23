<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

trait AuthenticatesNamiUsers {
    
    use AuthenticatesUsers;

    protected function validateLogin(Request $request)
    {
        $request->validate([
            'mglnr' => 'required_if:provider,nami',
            'email' => 'required_if:provider,database',
            'password' => 'required|string',
        ]);
    }

    public function username()
    {
        return 'mglnr';
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        return $request->only($this->username(), 'email', 'password', 'provider');
    }

}
