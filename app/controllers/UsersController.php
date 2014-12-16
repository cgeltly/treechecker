<?php
/*
* TreeChecker: Error recognition for genealogical trees
*
* Copyright (C) 2014 Digital Humanities Lab, Faculty of Humanities, Universiteit Utrecht
* Corry Gellatly <corry.gellatly@gmail.com>
* Martijn van der Klis <M.H.vanderKlis@uu.nl>
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*/
class UsersController extends BaseController
{
protected $layout = "layouts.main";
/**
* Add filters:
* - dashboard, index and show: need authorization
* - index, show: only for admin role
*/
public function __construct()
{
parent::__construct();
$this->beforeFilter('auth', array('only' => array('getDashboard', 'getIndex', 'getShow')));
$this->beforeFilter('is_admin', array('only' => array('getIndex', 'getShow')));
}
/**
* Register page
*/
public function getRegister()
{
$this->layout->content = View::make('users.register');
}
/**
* Login page
*/
public function getLogin()
{
$this->layout->content = View::make('users.login');
}
/**
* Logging out of the application
* @return The login page, with a successful logout message
*/
public function getLogout()
{
Auth::logout();
return Redirect::to('users/login')->with('message', Lang::get('users/message.logged_out'));
}
/**
* Creates a user
* @return The login page when everything is OK, the register page when things are faulty
*/
public function postCreate()
{
$validator = Validator::make(Input::all(), User::$rules);
if ($validator->passes())
{
$user = new User;
$user->role = 'typic'; // Standard user permission
$user->first_name = Input::get('first_name');
$user->last_name = Input::get('last_name');
$user->email = Input::get('email');
$user->password = Hash::make(Input::get('password'));
$user->save();
return Redirect::to('users/login')->with('message', Lang::get('users/message.register_finish'));
}
else
{
return Redirect::to('users/register')->withErrors($validator)->withInput();
}
}
/**
* Send user to different dashboards, depending on their role
*/
public function getDashboard()
{
$user = Auth::user();
switch ($user->role)
{
case 'admin':
$this->layout->content = View::make('admin_dashboard');
break;
case 'typic':
$gedcom_count = $user->gedcoms()->count();
$this->layout->content = View::make('dashboard', compact('user', 'gedcom_count'));
break;
}
}
/**
* Post the sign-in form
* @return The dashboard when the login is OK, the login page when it's incorrect
*/
public function postSignin()
{
if (Auth::attempt(array('email' => Input::get('email'), 'password' => Input::get('password'))))
{
return Redirect::to('users/dashboard')->with('message', Lang::get('users/message.logged_in'));
}
else
{
return Redirect::to('users/login')
->with('error', Lang::get('users/message.login_incorrect'))
->withInput();
}
}
/**
* Shows the details for the given User id
* @param int $id
*/
public function getShow($id)
{
$user = User::findOrFail($id);
$this->layout->content = View::make('users/detail', compact('user'));
}
/**
* Show a list of all the Users
*/
public function getIndex()
{
$this->layout->content = View::make('users/index');
}
/**
* Show a list of all the Users formatted for Datatables.
* @return Datatables JSON
*/
public function getData()
{
$users = User::select(array('id', 'first_name', 'last_name', 'email', 'role'));
return Datatables::of($users)
->edit_column('first_name', '{{ HTML::link("users/show/" . $id, $first_name) }}')
->edit_column('email', '{{ HTML::mailto($email) }}')
->remove_column('id')
->make();
}
}