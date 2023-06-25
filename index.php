<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Leaf\Controllers\APIController;
use Leaf\Helpers\create_all_tables;



$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$capsule = new Capsule;

$capsule->addConnection([
    "driver" => _env("DB_CONNECTION"),
    "host" =>_env("DB_HOST"),
    "port" => _env("DB_PORT"),
    "database" => _env("DB_DATABASE"),
    "username" => _env("DB_USERNAME"),
    "password" => _env("DB_PASSWORD"),
]);

//register events
$capsule->getContainer()->singleton(
    Illuminate\Contracts\Events\Dispatcher::class,
    Illuminate\Events\Dispatcher::class
);
//Make this Capsule instance available globally.
$capsule->setAsGlobal();

// Setup the Eloquent ORM.
$capsule->bootEloquent();

app()->cors();

app()->get('/', function () {
    (new APIController())->processor();
});
app()->post('/', function () {
    (new APIController())->processor();
});


//migrate route. This route will migrate all the migrations in the migrations folder. FOR DEVELOPMENT ONLY!!!


app()->post('/api/processor', function () {
    response()->json([
        'message' => 'Welcome! to the API'
    ]);
    (new APIController())->processor();
});
app()->get('/migrate', function () {
    if (_env("APP_ENV") == "local") {
        $create_all_tables = new create_all_tables();
        $create_all_tables->up();

        response()->json([
            'message' => 'Migrations completed!'
        ]);
    } else {
        response()->json([
            'message' => 'Migrations can only be run in development environment!'
        ]);
    }
});

app()->run();
