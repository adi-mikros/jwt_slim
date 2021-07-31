<?php
// Application middleware
 
// e.g: $app->add(new \Slim\Csrf\Guard);
use Tuupola\Middleware\HttpBasicAuthentication;

$container = $app->getContainer();
$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler("../logs/app.log");
    $logger->pushHandler($file_handler);
    return $logger;
};

$container["jwt"] = function ($container) {
    return new StdClass;
};



$app->add(new \Slim\Middleware\JwtAuthentication([
	"path" => "/api",
    "logger" => $container['logger'],
    "secret" => "jlkwdfhasljkl3sdafa2232adigantengdansangatkayaamin24wflqwjwklj234j23423jkljkll",
	"secure" => false,
    "rules" => [
        new \Slim\Middleware\JwtAuthentication\RequestPathRule([
            "path" => "/",
            "passthrough" => ["/token", "/not-secure", "/home"]
        ]),
        new \Slim\Middleware\JwtAuthentication\RequestMethodRule([
            "passthrough" => ["OPTIONS"]
        ]),
    ],
    "callback" => function ($request, $response, $arguments) use ($container) {
        $container["jwt"] = $arguments["decoded"];
    },
    "error" => function ($request, $response, $arguments) {
        $data["status"] = "error";
        $data["message"] = $arguments["message"];
        return $response
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
]));

// $app->add(new \Slim\Middleware\HttpBasicAuthentication([
//     "path" => "/androapi/token",
//     "users" => [
//         "user" => "password"
//     ]
// ]));


#middleware untuk CORS
$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});



//middlewware fungsional -> 24-01-2021 17:43
#otentifikasi JWT cara Adi
$adijwt = function($request, $response, $next){
    $otentifikasi=$request->getHeaderLine('authorization');
    $otentifikasi=str_replace("Bearer ","",$otentifikasi);
  
    $ot_split = explode(".",$otentifikasi);
    $header = $ot_split[0];
    $payload=$ot_split[1];
    $signature=$ot_split[2];
  
    $payload_dec = base64_decode($payload);
  
    $object = json_decode($payload_dec);
  
    $usernama = $object->usernama;
  
    //cek token betul gag
    $jum = $this->db->query("select count(*) from pengguna where usernama='$usernama' and token_app='$otentifikasi'")->fetchColumn();
    if($jum<=0){
      return;
    }

    #ambil data lengkap
    $hasilx = $this->db->query("select * from pengguna where usernama='$usernama' and token_app='$otentifikasi'")->fetch(PDO::FETCH_ASSOC);
    
    #buat variable untuk dikirim ke callback
    $request = $request->withAttribute('otentifikasi', $otentifikasi);
    $request = $request->withAttribute('usernama', $usernama);
    $request = $request->withAttribute('id', $hasilx['id']);
  
    $response = $next($request, $response); #kirim variable atau terima umpan balik dari callback
    return $response;
  };
  
  