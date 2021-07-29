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
	"path" => "/androapi",
    "logger" => $container['logger'],
    "secret" => "jlkwdfhasljkl324wflqwjwklj234j23423jkljkll",
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

$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "path" => "/androapi/token",
    "users" => [
        "user" => "password"
    ]
]));


#middleware untuk CORS
$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});



/*
// Application middleware
 use \Slim\Middleware\JwtAuthentication;
// e.g: $app->add(new \Slim\Csrf\Guard);
$app->add(new JwtAuthentication([
    "attribute" => "decoded_token_data",
    "secret" => "akuadalahgiantgiantsangjagon1987kaloberjalantoktok",
    "algorithm" => ["HS256"],
    "error" => function ($response, $arguments) {
        $data["status"] = "error";
        $data["message"] = $arguments["message"];
        return $response
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
]));


#middleware untuk CORS
$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

*/




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
  
    // echo $payload_dec;
  
    $iduser = $object->iduser;
    $idagen = $object->idagen;
    $usernama = $object->usernama;
    $iat = $object->iat;
    $exp = $object->exp;
  
    // echo date("Y-m-d H:i:s", $iat);
    // echo " - ";
    // echo date("Y-m-d H:i:s", $exp);
    // echo " - ";
    // echo time(); #tampilkan integer hari ini
    // echo " - ";
    // echo  date("Y-m-d H:i:s", time());
  
    #ambil levelnya
    $hasil2=$this->db->query("select levelx from user where iduser='$iduser' and token_app='$otentifikasi'")->fetch(PDO::FETCH_ASSOC);
    $levelx=$hasil2['levelx'];
  
    //cek token betul gag
    $jum = $this->db->query("select count(*) from user where iduser='$iduser' and token_app='$otentifikasi'")->fetchColumn();
    if($jum<=0){
      return;
    }
    #update last_aktif
    $hasil=$this->db->query("update user set last_aktif=now() where iduser='$iduser' and token_app='$otentifikasi'");
  
    #buat variable untuk dikirim ke callback
    $request = $request->withAttribute('otentifikasi', $otentifikasi);
    $request = $request->withAttribute('iduser', $iduser);
    $request = $request->withAttribute('idagen', $idagen);
    $request = $request->withAttribute('usernama', $usernama);
    $request = $request->withAttribute('otentifikasi', $iat);
    $request = $request->withAttribute('otentifikasi', $exp);
  
    $response = $next($request, $response); #kirim variable atau terima umpan balik dari callback
    return $response;
  };
  
  
  #otentifikasi JWT cara Adi khusus admin pusat
  $adijwt_pusat = function($request, $response, $next){
    $otentifikasi=$request->getHeaderLine('authorization');
    $otentifikasi=str_replace("Bearer ","",$otentifikasi);
  
    $ot_split = explode(".",$otentifikasi);
    $header = $ot_split[0];
    $payload=$ot_split[1];
    $signature=$ot_split[2];
  
    $payload_dec = base64_decode($payload);
  
    $object = json_decode($payload_dec);
  
    $iduser = $object->iduser;
    $idagen = $object->idagen;
    $usernama = $object->usernama;
    $iat = $object->iat;
    $exp = $object->exp;
  
    #ambil levelnya
    $hasil2=$this->db->query("select levelx from user where iduser='$iduser' and token_app='$otentifikasi'")->fetch(PDO::FETCH_ASSOC);
    $levelx=$hasil2['levelx'];
  
    $settings = $this->get('settings');
    $levelx_setting = $settings['levelx']['levelx'];
    $kode_pusat_setting = $settings['levelx']['kode_pusat'];
  
    #cek apakah pusat dan superadmin
    if($levelx<>$levelx_setting){
      return;
    }
    if($idagen<>$kode_pusat_setting){
      return;
    }
  
    //cek token betul gag
    $jum = $this->db->query("select count(*) from user where iduser='$iduser' and token_app='$otentifikasi'")->fetchColumn();
    if($jum<=0){
      return;
    }
    #update last_aktif
    $hasil=$this->db->query("update user set last_aktif=now() where iduser='$iduser' and token_app='$otentifikasi'");
  
    #buat variable untuk dikirim ke callback
    $request = $request->withAttribute('otentifikasi', $otentifikasi);
    $request = $request->withAttribute('iduser', $iduser);
    $request = $request->withAttribute('idagen', $idagen);
    $request = $request->withAttribute('usernama', $usernama);
    $request = $request->withAttribute('otentifikasi', $iat);
    $request = $request->withAttribute('otentifikasi', $exp);
  
    $response = $next($request, $response); #kirim variable atau terima umpan balik dari callback
    return $response;
  };
  
  
  
  //contoh
  $mw = function ($request, $response, $next) {
      #deklarasikan variable untuk dikirim ke callback
      $request = $request->withAttribute('foo', 'bar');
  
      $response->getBody()->write('BEFORE');
      $response = $next($request, $response); #kirim variable atau terima umpan balik dari callback
      $response->getBody()->write('AFTER');
  
      return $response;
  };
  

//=== CARA PAKE HARUS DALAM GROUP ===

//   $app->group('/api/v2', function(\Slim\App $app) {

// 	#tampilkan data mutasi masuk 7 hari
// 	$app->get('/moota/mutasi7', function($request, $response, $datae){
//     $settings = $this->get('settings');
//     $token_moota = $settings['token_moota'];

//     #tampilkan mutasi terakhir
//     $bank_id='oEpzwQZ5WNw';
//     $jumlah=10;
//     $curl = curl_init();
//     curl_setopt($curl, CURLOPT_URL, 'https://app.moota.co/api/v1/bank/'.$bank_id.'/mutation/recent/'.$jumlah);
//     curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
//     curl_setopt($curl, CURLOPT_HTTPHEADER, [
//         'Accept: application/json',
//         'Authorization: Bearer '.$token_moota
//     ]);
//     $response = curl_exec($curl);
//     $json=json_encode($response);
//     print_r($json);
// 	});
// })->add($adijwt);

//=== CARA PAKE HARUS DALAM GROUP end ===