<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

//Route to main page. Contains form to upload the log file
$app->get('/', function() use ($app) {
    return view('form', []);
});
//Route to results page. Contains chart and rules table
$app->get('/results', function () use ($app) {
    return view('chart', []);
});
$app->get('/error', function() use ($app) {
    return view('error', []);
});
// Route to download .csv file
$app->get('/rawfile', function() use ($app) {
    return response()->download('rules.csv');
    });
$app->get('/file', function() use ($app) {
    return response()->download('humanRules.csv');
});
$app->post('/part', 'LogController@part');
//Route to method to handle  uploaded file
$app->post('/upload', 'LogController@index');
//Route to method to get the data for chart drawing
$app->post('/chart', 'LogController@getChartPoints');
//Route to method to get the ids for current chart point
$app->post('/roots', 'LogController@getParentIds');
//Route  to method to get the tree structure
$app->post('/table', 'LogController@getTree');
//Route  to method to get the table contents
$app->post('/contents', 'LogController@getTableContents');
//Route to method to generate the raw .csv file
$app->post('/rawfile', 'LogController@getOutputFile');
//Route to method to generate the human-readable .csv file
$app->post('/file', 'LogController@getHumanOutputFile');


