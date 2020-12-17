<?php

/** @var \Laravel\Lumen\Routing\Router $router */

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

use Illuminate\Http\Request;

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/test', function () {
    return response()->json(['Test' => 'Jalan Boss']);
});

$router->post('/gpsaccept',function(Request $request) {
    $test=app('db')->select("SELECT COUNT(bike_id) AS jumlah FROM Bike WHERE bike_id = :id",['id' => $request->id]);
    if ($test[0]->jumlah >0){
        try{
            $query = app('db')->update("UPDATE Bike set latitude= :latitude , longitude= :longitude WHERE bike_id = :id", $request->json()->all());
        }
        catch (Exception $e){
            return response()->json(["error_code"=> 500, "message"=> $e]);
        }
    }
    else if($test[0]->jumlah ==0){
        try{
            $query = app('db')->insert("INSERT INTO Bike values( :id , :latitude , :longitude)", $request->json()->all());
        }
        catch (Exception $e){
            return response()->json(["error_code"=> 500, "message"=> $e]);
        }
    }
    $poligon = array("-6.930246 107.774365","-6.928944 107.777785","-6.919930 107.774055","-6.921711 107.769723","-6.930246 107.774365");
    foreach($poligon as $vertex){
        $coordinates = explode(" ", $vertex);
        $vertices[] = array("x" => $coordinates[0], "y" => $coordinates[1]);
    }
    $pointOnVertex = false;
    foreach($vertices as $vertex){
        if ($request->latitude == $vertex["x"] and $request->longitude == $vertex["y"])
        $pointOnVertex = true;
    }
    if($pointOnVertex == true){
        $hasil = array_merge(['Didalam zona' => 'Ya'],$request->json()->all());
        return response()->json($hasil);
    }
    $intersections = 0;
    $vertices_count = count($vertices);

    for($i=1; $i<$vertices_count; $i++){
        $vertex1 = $vertices[$i-1];
        $vertex2 = $vertices[$i];
        if ($vertex1['y'] == $vertex2['y'] and $vertex1['y'] == $request->longitude and $request->latitude > min($vertex1['x'], $vertex2['x'])){ // Check if point is on an horizontal polygon boundary
            $hasil = array_merge(['Didalam zona' => 'Ya'],$request->json()->all());
            return response()->json($hasil);
        }
        if ($request->longitude > min($vertex1['y'], $vertex2['y']) and $request->longitude <= max($vertex1['y'], $vertex2['y']) and $request->latitude <= max($vertex1['x'], $vertex2['x']) and $vertex1['y'] != $vertex2['y']) { 
            $xinters = ($request->longitude - $vertex1['y']) * ($vertex2['x'] - $vertex1['x']) / ($vertex2['y'] - $vertex1['y']) + $vertex1['x']; 
            if ($xinters == $request->latitude) { // Check if point is on the polygon boundary (other than horizontal)
                $hasil = array_merge(['Didalam zona' => 'Ya'],$request->json()->all());
                return response()->json($hasil);
            }
            if ($vertex1['x'] == $vertex2['x'] || $request->latitude <= $xinters) {
                $intersections++; 
            }
        }
    }
    if ($intersections % 2 != 0) {
        $hasil = array_merge(['Didalam zona' => 'Ya'],$request->json()->all());
        return response()->json($hasil);
    } else {
        $hasil = array_merge(['Didalam zona' => 'Tidak'],$request->json()->all());
        return response()->json($hasil);
    }
    $hasil = array_merge(['Didalam zona' => 'Entahlah'],$request->json()->all());
    return response()->json($hasil);
});

$router->post('/login',['middleware' => 'cors', function(Request $request) {
    $test=app('db')->select("SELECT * FROM User");
    $hasil = array_merge(['Didalam zona' => 'Ya'],$request->json()->all());
    return response()->json($hasil);
}]);

$router->get('/gpsdata', function(){
    $test=app('db')->select("SELECT * FROM Bike");
    return response()->json($test);
});