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
        $hasil = array_merge($request->json()->all(),["message"=>"pengguna tidak ditemukan"]);
        return response()->json($hasil,404);
    }
});

$router->post('/pinjamsepeda', function (Request $request) {
    $query = app('db')->select("SELECT data_user_id FROM Data_RFID WHERE rfid = :rfid",['rfid' => $request->rfid]);
    if(count($query)>0){
        $id_user = $query[0]->data_user_id;
        $query = app('db')->select("SELECT in_use, battery_percentage FROM Bike WHERE bike_id = :bike_id",['bike_id' => $request->bike_id]);
        if(count($query)>0){
            if($query[0]->in_use == 1){
                return response()->json(["message"=>"Sepeda Sedang dipakai"],406);
            }
            // else if($query->battery_percentage == 0){
            //     return response()->json(["message"=>"Sepeda Habis baterai"],406);
            // }
            else{
                $query = app('db')->update("UPDATE Bike set in_use=1, charging=0 WHERE bike_id = :bike_id", ['bike_id' => $request->bike_id]);
                $query = app('db')->insert("INSERT INTO Lend_History(bike_id,data_user_id) values( :bike_id , :id_user)", ['bike_id' => $request->bike_id , 'id_user' => $id_user]);
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
    else if($test[0]->jumlah == 0){
        $query = app('db')->insert("INSERT INTO Bike(bike_id,latitude,longitude) values( :id , :latitude , :longitude)", $request->json()->all());
    }
    // const koordinatgeofence = [
    //     { lat: -6.932651, lng: 107.772106 },
    //     { lat: -6.932191, lng: 107.773204 },
    //     { lat: -6.932079, lng: 107.773937 },
    //     { lat: -6.931424, lng: 107.776048 },
    //     { lat: -6.931418, lng: 107.776363 },
    //     { lat: -6.931891, lng: 107.776472 },
    //     { lat: -6.931680, lng: 107.777202 },
    //     { lat: -6.931374, lng: 107.777674 },
    //     { lat: -6.930756, lng: 107.778274 },
    //     { lat: -6.929845, lng: 107.778828 },
    //     { lat: -6.928698, lng: 107.778869 },
    //     { lat: -6.927363, lng: 107.778429 },
    //     { lat: -6.926586, lng: 107.778096 },
    //     { lat: -6.925641, lng: 107.777132 },
    //     { lat: -6.924842, lng: 107.776576 },
    //     { lat: -6.924700, lng: 107.775979 },
    //     { lat: -6.923277, lng: 107.775454 },
    //     { lat: -6.922336, lng: 107.774795 },
    //     { lat: -6.921567, lng: 107.774395 },
    //     { lat: -6.921150, lng: 107.774845 },
    //     { lat: -6.920242, lng: 107.774638 },
    //     { lat: -6.919546, lng: 107.773759 },
    //     { lat: -6.919406, lng: 107.772430 },
    //     { lat: -6.919537, lng: 107.771042 },
    //     { lat: -6.920028, lng: 107.769949 },
    //     { lat: -6.920234, lng: 107.769602 },
    //     { lat: -6.921432, lng: 107.769658 },
    //     { lat: -6.921602, lng: 107.769462 },
    //     { lat: -6.921665, lng: 107.769304 },
    //     { lat: -6.921920, lng: 107.769153 },
    //     { lat: -6.922393, lng: 107.769152 },
    //     { lat: -6.922599, lng: 107.769260 },
    //     { lat: -6.930421, lng: 107.772756 },
    //     { lat: -6.930960, lng: 107.771675 },
    //     { lat: -6.932651, lng: 107.772106 },
    //   ];
    $poligon = array("-6.932651 107.772106",
                    "-6.932191 107.773204",
                    "-6.932079 107.773937",
                    "-6.931424 107.776048",
                    "-6.931891 107.776472",
                    "-6.931680 107.777202",
                    "-6.931374 107.777674",
                    "-6.930756 107.778274",
                    "-6.929845 107.778828",
                    "-6.928698 107.778869",
                    "-6.927363 107.778429",
                    "-6.926586 107.778096",
                    "-6.925641 107.777132",
                    "-6.924842 107.776576",
                    "-6.924700 107.775979",
                    "-6.923277 107.775454",
                    "-6.922336 107.774795",
                    "-6.921567 107.774395",
                    "-6.921150 107.774845",
                    "-6.920242 107.774638",
                    "-6.919546 107.773759",
                    "-6.919406 107.772430",
                    "-6.919537 107.771042",
                    "-6.920028 107.769949",
                    "-6.920234 107.769602",
                    "-6.921432 107.769658",
                    "-6.921602 107.769462",
                    "-6.921665 107.769304",
                    "-6.921920 107.769153",
                    "-6.922393 107.769152",
                    "-6.922599 107.769260",
                    "-6.930421 107.772756",
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
        $query = app('db')->update("UPDATE Bike set in_location= 1 where bike_id= :id", ['id' => $request->id]);
        $hasil = array_merge(['Didalam zona' => 'Ya'],$request->json()->all());
        return response()->json($hasil);
    }
    $intersections = 0;
    $vertices_count = count($vertices);

    for($i=1; $i<$vertices_count; $i++){
        $vertex1 = $vertices[$i-1];
        $vertex2 = $vertices[$i];
        if ($vertex1['y'] == $vertex2['y'] and $vertex1['y'] == $request->longitude and $request->latitude > min($vertex1['x'], $vertex2['x'])){ // Check if point is on an horizontal polygon boundary
            $query = app('db')->update("UPDATE Bike set in_location= 1 where bike_id= :id", ['id' => $request->id]);
            $hasil = array_merge(['Didalam zona' => 'Ya'],$request->json()->all());
            return response()->json($hasil);
        }
        if ($request->longitude > min($vertex1['y'], $vertex2['y']) and $request->longitude <= max($vertex1['y'], $vertex2['y']) and $request->latitude <= max($vertex1['x'], $vertex2['x']) and $vertex1['y'] != $vertex2['y']) { 
            $xinters = ($request->longitude - $vertex1['y']) * ($vertex2['x'] - $vertex1['x']) / ($vertex2['y'] - $vertex1['y']) + $vertex1['x']; 
            if ($xinters == $request->latitude) { // Check if point is on the polygon boundary (other than horizontal)
                $query = app('db')->update("UPDATE Bike set in_location= 1 where bike_id= :id", ['id' => $request->id]);
                $hasil = array_merge(['Didalam zona' => 'Ya'],$request->json()->all());
                return response()->json($hasil);
            }
            if ($vertex1['x'] == $vertex2['x'] || $request->latitude <= $xinters) {
                $intersections++; 
            }
        }
    }
    if ($intersections % 2 != 0) {
        $query = app('db')->update("UPDATE Bike set in_location= 1 where bike_id= :id", ['id' => $request->id]);
        $hasil = array_merge(['Didalam zona' => 'Ya'],$request->json()->all());
        return response()->json($hasil);
    } else {
        $query = app('db')->update("UPDATE Bike set in_location= 0 where bike_id= :id", ['id' => $request->id]);
        $hasil = array_merge(['Didalam zona' => 'Tidak'],$request->json()->all());
        return response()->json($hasil);
    }
    $query = app('db')->update("UPDATE Bike set in_location= 0 where bike_id= :id", ['id' => $request->id]);
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
    $secret_key = getenv("SECRET");
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

$router->post('/createaccount', ['middleware' => 'cors', function(Request $request){
    $test=app('db')->select("SELECT `data_user_id` FROM `Username` WHERE `username`= :username AND `password` = :password",['username' => $request->username, 'password' => $request->password]);
    return response()->json(["message" => "berhasil masuk", "jwt" => $jwt]);
}]);

$router->get('/gpsdata',['middleware' => 'cors', function(){
    $test=app('db')->select("SELECT * FROM Bike");
    return response()->json($test);
}]);

$router->post('/baterryaccept',function(Request $request) {
    $test=app('db')->select("SELECT COUNT(bike_id) AS jumlah FROM Bike WHERE bike_id = :id",['id' => $request->id]);
    if ($test[0]->jumlah >0){
        $query = app('db')->update("UPDATE Bike set in_use = 0 ,charging= :charging , battery_percentage= :battery_percentage WHERE bike_id = :id", $request->json()->all());
    }
    else if($test[0]->jumlah == 0){
        $query = app('db')->insert("INSERT INTO Bike(bike_id,in_use,battery_percentage,charging) values( :id , 0 , :battery_percentage , :charging)", $request->json()->all());
    }
    $hasil = array_merge(['message' => 'Data baterai sudah disimpan'],$request->json()->all());
    return response()->json($hasil);
});