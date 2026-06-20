<?php

namespace App\Controllers;

use Desinova\Aero\Controller;

class HomeController extends Controller
{
    public function home()
    {
        return view('home');
    }
}