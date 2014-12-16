# TreeChecker

TreeChecker is a Laravel application that allows users to run error checks on their GEDCOM family tree files. 
GEDCOM files can be uploaded and parsed using a slightly modified version of the Webtrees parser, 
then checked for a variety of errors (see the documentation at http://www.treechecker.net/wiki/ for a specification). 

TreeChecker comes with a database model that allows for multiple users and family tree files. 
The Laravel Eloquent ORM makes querying a breeze and allows for rapid development of new features. 

## (Development) Installation of TreeChecker

### Preliminaries

- The application is written in the [Laravel framework](http://laravel.com/). Some useful resources on Laravel are posted below. 
- Before installing, make sure to have Apache, MySQL and PHP (>=5.4) installed.
- Install [Composer](https://getcomposer.org/) for dependency management. 

### Initialization

- Clone the repository with git (`git clone https://github.com/UUDigitalHumanitieslab/gedcomcheck.git`)
- Run `composer install` to install the dependencies. 

#### Database seeding

- Create a database called treechecker on your local MySQL installation. 
- Check the settings in `app/config/database.php` to see whether the connection details for the database are correct. You might need to change database names or access credentials. 
- To migrate and seed the database (see http://laravel.com/docs/migrations for more info), use `php artisan migrate --seed`. 

### Back-end
- As stated before the back-end is written in the [Laravel framework](http://laravel.com/), version 4.2.x. 
- The parsing part of the application relies heavily on the [webtrees](http://www.webtrees.net/index.php/en/) parser (version 1.5.3). 
- For display of tables, we employ the [blimm/datatables package](https://github.com/bllim/laravel4-datatables-package) (version 1.3 onwards). 

### Front-end 
- The front-end of the application uses [Bootstrap](http://getbootstrap.com/) for its CSS. 
- It runs quite a few JavaScript packages (all via CDN): 
    - [jQuery](http://jquery.com/) and [jQueryUI](http://jqueryui.com/)
    - [DataTables](http://datatables.net/)
    - [D3.js](http://d3js.org/)
    - [Google Charts](https://developers.google.com/chart/)
- The front-end can be launched by going to `localhost/gedcomcheck/public/home` in your browser. 

### Testing
- TreeChecker is set up to use [PHPUnit](https://phpunit.de/). You can run the tests by calling `phpunit` in a terminal. 
- You can create a coverage report by uncommenting lines in `phpunit.xml`. 

### Last but not least 

#### Want to know more about Laravel? 

- The Laravel documentation can be found from http://laravel.com/docs/introduction onwards. Some useful links: 
  - Basic tutorial on Laravel: http://daylerees.com/codebright/build-an-app-one
  - Authentication tutorial on Laravel: http://code.tutsplus.com/tutorials/authentication-with-laravel-4--net-35593
  - Laravel starter site: https://github.com/andrewelkins/Laravel-4-Bootstrap-Starter-Site
  - Laravel cheat sheet: http://cheats.jesse-obrien.ca/
  - List of Laravel packages: https://github.com/tuwannu/awesome-laravel
  - Faker, for generating test data: https://github.com/fzaninotto/Faker