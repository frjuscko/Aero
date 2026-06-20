<?php

/** @var \Desinova\Aero\Router $router */

use App\Controllers\HomeController;


$router->get('/', [HomeController::class, 'home'])->name('home');