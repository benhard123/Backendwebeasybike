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
use \Firebase\JWT\JWT;

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/test', function () {
    return response()->json(['Test' => 'Jalan Boss']);
});

// $router->post('/rfiddaftar', function (Request $request) {
//     $query = app('db')->insert("INSERT INTO Data_User (rfid, nama) values (:rfid, :nama)", $request->json()->all());
//     return response()->json(["message" => "berhasil didaftarkan"]);
// });

$router->post('/rfidcheck', function (Request $request) {
    $query = app('db')->select("SELECT data_user_id FROM Data_RFID WHERE rfid = :rfid",['rfid' => $request->rfid]);
    if (count($query)>0){
        $query = app('db')->select("SELECT * FROM Data_User WHERE data_user_id = :id",['id' => $query[0]->data_user_id]);
        $hasil = array_merge($request->json()->all(),["HASIL"=>$query]);
        return response()->json($hasil);
    }
    else{
        $hasil = array_merge($request->json()->all(),["HASIL"=>[]]);
        return response()->json($hasil,404);
    }
});

$router->post('/pinjamsepeda', function (Request $request) {
    $query = app('db')->select("SELECT COUNT(data_user_id) AS jumlah FROM Data_RFID WHERE rfid = :rfid",['rfid' => $request->rfid]);
    if($query[0]->jumlah == 1){
        $query = app('db')->select("SELECT in_use, battery_percentage FROM Bike WHERE bike_id = :bike_id",['bike_id' => $request->bike_id]);
        if(count($query)>0){
            if($query->in_use == 1){
                return response()->json(["message"=>"Sepeda Sedang dipakai"],406);
            }
            // else if($query->battery_percentage == 0){
            //     return response()->json(["message"=>"Sepeda Habis baterai"],406);
            // }
            else{
                $query = app('db')->update("UPDATE Bike set in_use=1 WHERE bike_id = :bike_id", ['bike_id' => $request->bike_id]);
                return response()->json(["message"=>"Sepeda siap digunakan"]);
            }
        }
        else{
            return response()->json(["message"=>"Sepeda Tidak Terdaftar"],404);
        }
    }
    else{
        return response()->json(["message"=>"Pengguna Tidak Terdaftar"],404);
    }
});


$router->post('/gpsaccept',function(Request $request) {
    $test=app('db')->select("SELECT COUNT(bike_id) AS jumlah FROM Bike WHERE bike_id = :id",['id' => $request->id]);
    if ($test[0]->jumlah >0){
        $query = app('db')->update("UPDATE Bike set latitude= :latitude , longitude= :longitude WHERE bike_id = :id", $request->json()->all());
    }
    else if($test[0]->jumlah ==0){
        $query = app('db')->insert("INSERT INTO Bike values( :id , :latitude , :longitude)", $request->json()->all());
    }
    // const koordinatgeofence = [
    //     { lat: -6.932651, lng: 107.772106 },
    //     { lat: -6.932191, lng: 107.773204 },
    //     { lat: -6.932079, lng: 107.773937 },
    //     { lat: -6.931424, lng: 107.776048 },
    //     { lat: -6.931418, lng: 107.776363 },
    //     { lat: -6.931891, lng: 107.776472 },
    //     { lat: -6.919930, lng: 107.774055 },
    //     { lat: -6.921711, lng: 107.769723 },
    //     { lat: -6.930406, lng: 107.773581 },
    //     { lat: -6.930960, lng: 107.771675 },
    //     { lat: -6.932651, lng: 107.772106 },
    //   ];
    $poligon = array("-6.932651 107.772106",
                    "-6.932191 107.773204",
                    "-6.932079 107.773937",
                    "-6.931424 107.776048",
                    "-6.931418 107.776363",
                    "-6.919930 107.774055",
                    "-6.921711 107.769723",
                    "-6.930406 107.773581",
                    "-6.930960 107.771675",
                    "-6.932651 107.772106");
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
    $test=app('db')->select("SELECT `data_user_id` FROM `Username` WHERE `username`= :username AND `password` = :password",['username' => $request->username, 'password' => $request->password]);
    if(count($test)<=0){
        // $hasil = array_merge(["message"=>"user tidak ditemukan"],$request->json()->all());
        // return response()->json($hasil,404);
        return response()->json(["message"=>"user tidak ditemukan"],404);
    }
    $secret_key = "OH_NOOO";
    $issuer_claim = "THE_ISSUER";
    $audience_claim = "THE_AUDIENCE";
    $issuedat_claim = time(); // issued at
    $notbefore_claim = $issuedat_claim + 10; //not before in seconds
    $expire_claim = $issuedat_claim + 60; // expire time in seconds
    $token = array(
        "iss" => $issuer_claim,
        "aud" => $audience_claim,
        "iat" => $issuedat_claim,
        "nbf" => $notbefore_claim,
        "exp" => $expire_claim,
        "data" => array(
            "data_user_id" => $test[0]->data_user_id,
    ));
    $jwt = JWT::encode($token, $secret_key);

    return response()->json(["message" => "berhasil masuk", "jwt" => $jwt]);
}]);

$router->get('/gpsdata',['middleware' => 'cors', function(){
    $test=app('db')->select("SELECT * FROM Bike");
    return response()->json($test);
}]);