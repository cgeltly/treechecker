<?php

// There's no place like home
Route::get('/', function()
{
    return Redirect::to('home');
});


// RESTful Controllers
Route::controller('users', 'UsersController');
Route::controller('check', 'CheckController');
Route::controller('errors', 'ErrorsController');
Route::controller('events', 'EventsController');
Route::controller('families', 'FamiliesController');
Route::controller('gedcoms', 'GedcomsController');
Route::controller('parse', 'ParseGedcomController');
Route::controller('json_parse', 'ParseJsonController');
Route::controller('home', 'HomeController');
Route::controller('about', 'AboutController');
Route::controller('individuals', 'IndividualsController');
Route::controller('fileuploads', 'FileUploadsController');
Route::controller('lifespan', 'LifespanController');

