<?php

// There's no place like home
Route::get('/', function()
{
    return Redirect::to('home');
});

// RESTful Controllers
Route::controller('parse', 'ParseController');
Route::controller('check', 'CheckController');
Route::controller('errors', 'ErrorsController');
Route::controller('events', 'EventsController');
Route::controller('families', 'FamiliesController');
Route::controller('gedcoms', 'GedcomsController');
Route::controller('home', 'HomeController');
Route::controller('individuals', 'IndividualsController');
Route::controller('users', 'UsersController');
Route::controller('fileuploads', 'FileUploadsController');
