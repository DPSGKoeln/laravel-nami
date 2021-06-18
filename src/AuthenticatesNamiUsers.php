<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

trait AuthenticatesNamiUsers {
    
    use AuthenticatesUsers;

    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|numeric',
            'password' => 'required|string',
        ]);
    }

    public function username()
    {
        return 'mglnr';
    }

}
