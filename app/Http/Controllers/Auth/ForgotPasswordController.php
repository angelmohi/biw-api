<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    | NOTE: Since the system now uses usernames instead of emails in the 'email' field,
    | password reset functionality via email will not work unless additional
    | email field is implemented or alternative reset method is used.
    |
    */

    use SendsPasswordResetEmails;
}
