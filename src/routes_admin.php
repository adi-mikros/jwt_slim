<?php

use Slim\Http\Request;
use Slim\Http\Response;
use \Firebase\JWT\JWT;

$app->post('/registrasi', function ($request, $response) {
	$postdata = $request->getBody(); #--> Sama dengan :  $postdata = file_get_contents("php://input");
	$req = json_decode($postdata);

	$usernama = $req->usernama;
	$email = $req->email;
	$pass = $req->pass;

	//cek usernama udah digunakan atau belum
	$jum = $this->db->query("select count(*) FROM pengguna where usernama='$usernama'")->fetchColumn();
	if ($jum > 0) {
		$pesan="Nama Pengguna udah digunakan. ganti yang lain!";
		$rows=array();
		array_push($rows, array('status'=>'gagal','pesan'=>$pesan));
		$json = json_encode($rows[0]);
		print_r($json);
		return;
	}

	$hasilx = $this->db->query("insert into pengguna set usernama='$usernama',
																email='$email',
																pass='$pass'");

	$id = $this->db->lastInsertId();

	$pesan="Registrasi berhasil!";
	$rows=array();
	array_push($rows, array('status'=>'sukses','pesan'=>$pesan, 'idx'=>$id));
	$json = json_encode($rows[0]);
	print_r($json);

});

$app->put('/login', function ($request, $response) {
	$postdata = $request->getBody(); #--> Sama dengan :  $postdata = file_get_contents("php://input");
	$req = json_decode($postdata);

	$usernama = $req->usernama;
	$pass = $req->pass;
	

	$jum = $this->db->query("select count(*) FROM pengguna where usernama='$usernama' and pass='$pass'")->fetchColumn();
	#jika ada isinya langsung munculkan data yg sudah dikerjakan

	if ($jum > 0) {
		#tampilkan data untuk user
		$time = time(); //untuk waktu saat ini
		// $exp =  date("m/d/Y h:i:s a", time() + 2592000); //untuk waktu exp token diitung detik
		$exp =  time() + 2592000; //untuk waktu exp token diitung detik selama 1 bulan
		$settings = $this->get('settings'); // get settings array.
		$token = JWT::encode(['usernama' => $usernama,  "iat" => $time, "exp" => $exp], $settings['jwt']['secret'], "HS256");

   
		#update last_login
		$updatex = $this->db->query("update pengguna set token_app='$token' 
    	where usernama='$usernama' and pass='$pass'");


		$hasilx = $this->db->query("select usernama,token_app,id,email from pengguna where usernama='$usernama' and pass='$pass'")->fetch(PDO::FETCH_ASSOC);
		$pesan="Login berhasil!";
		$rows=array();
		array_push($rows, array('status'=>'sukses','pesan'=>$pesan,'data'=>$hasilx));
		$json = json_encode($rows[0]);
		print_r($json);
	} else {
		$pesan="Nama pengguna atau kata sandi salah!";
		$rows=array();
		array_push($rows, array('status'=>'gagal','pesan'=>$pesan));
		$json = json_encode($rows[0]);
		print_r($json);
	}
});

#untuk cek koneksi
$app->get('/cekkoneksi', function ($request, $response) {
	echo 'OK';
});



$app->group('/api', function (\Slim\App $app) {
	#ambil data user
	$app->get('/pengguna', function ($request, $response) {
		$otentifikasi = $request->getHeaderLine('authorization');
		$otentifikasi = str_replace("Bearer ", "", $otentifikasi);
		//cek token betul gag
		$jum = $this->db->query("select count(*) from pengguna where token_app='$otentifikasi'")->fetchColumn();
		if ($jum <= 0) {
			return;
		}
		#ambil iduser dari database
		$hasilapp = $this->db->query("select * from pengguna where token_app='$otentifikasi'")->fetch(PDO::FETCH_ASSOC);
		$usernama = $hasilapp['usernama'];
		$pass = $hasilapp['pass'];

		$hasilx = $this->db->query("select usernama,token_app,id,email from pengguna where usernama='$usernama' and pass='$pass'")->fetch(PDO::FETCH_ASSOC);
		$json = json_encode($hasilx);
		print_r($json);
	});

	$app->get('/bro', function ($request, $response) {
		echo  $request->getAttribute('usernama');
	});

})->add($adijwt);
