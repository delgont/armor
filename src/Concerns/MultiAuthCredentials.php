<?php

namespace Delgont\Armor\Concerns;

use Illuminate\Http\Request;

trait MultiAuthCredentials
{

     /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function multiAuthCredentials(Request $request)
    {
        $username = filter_var($request->{method_exists($this, 'username') ? $this->username() : 'email'}, FILTER_VALIDATE_EMAIL) ? 'email' : $this->getSecondaryColumn();

        return [
         $username => $request->{method_exists($this, 'username') ? $this->username() : 'email'},
         'password' => $request->password
        ];
    }

    /**
     * Get the second colum that will be used with email and the second field by default name column defined in the user table
     * @return string
     */
    protected function getSecondaryColumn ()
    {
        return 'name';
    }

}
