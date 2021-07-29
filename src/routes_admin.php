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

		$hasilx = $this->db->query("insert into pengguna set usernama='$usernama',
																email='$email',
																pass='$pass'");

																$id = $this->db->lastInsertId();

																echo $id;
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
		$exp =  date("m/d/Y h:i:s a", time() + 2592000); //untuk waktu exp token diitung detik
		$settings = $this->get('settings'); // get settings array.
		$token = JWT::encode(['usernama' => $usernama,  "iat" => $time, "exp" => $exp], $settings['jwt']['secret'], "HS256");

   
		#update last_login
		$updatex = $this->db->query("update pengguna set token_app='$token' 
    	where usernama='$usernama' and pass='$pass'");


		$hasilx = $this->db->query("select * from pengguna where usernama='$usernama' and pass='$pass'")->fetch(PDO::FETCH_ASSOC);
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
	#cek token masih aktif tidak
	$app->put('/cektoken', function ($request, $response, $datae) {
		$kiriman = $request->getBody();
		$datakirim = json_decode($kiriman);
		$usernama = $datakirim->usernama;

		$otentifikasi = $request->getHeaderLine('authorization');
		$otentifikasi = str_replace("Bearer ", "", $otentifikasi);
		//cek token betul gag
		$jum = $this->db->query("select count(*) from user where token_app='$otentifikasi'")->fetchColumn();
		if ($jum <= 0) {
			echo 'blokirbos';
		}

		#ambil iduser dari database
		$hasilapp = $this->db->query("select * from user where token_app='$otentifikasi'")->fetch(PDO::FETCH_ASSOC);
		$iduser = $hasilapp['iduser'];

		#update last_aktif
		$hasil = $this->db->query("update user set last_aktif=now() where iduser='$iduser' and token_app='$otentifikasi' and hapus='0'");

		#jika nomor tidak sesuai dengan token langsunk logout juga aja
		$jumhasil2 = $this->db->query("select count(*) from user where iduser='$iduser' and  token_app='$otentifikasi' and hapus='0'")->fetchColumn();
		if ($jumhasil2 <= 0) {
			echo 'blokirbos';
		}
	});


	#input data 
	$app->post('/gantipass', function ($request, $response) {

		$otentifikasi = $request->getHeaderLine('authorization');
		$otentifikasi = str_replace("Bearer ", "", $otentifikasi);

		#ambil $idskul
		$hasil2 = $this->db->query("select * from user where token_app='$otentifikasi'")->fetch(PDO::FETCH_ASSOC);
		$iduser = $hasil2['iduser'];


		//cek token betul gag
		$jum = $this->db->query("select count(*) from user where iduser='$iduser' and token_app='$otentifikasi'")->fetchColumn();
		if ($jum <= 0) {
			return;
		}
		#update last_aktif
		$hasil = $this->db->query("update user set last_aktif=now() where iduser='$iduser' and token_app='$otentifikasi'");

		$postdata = $request->getBody(); #--> Sama dengan :  $postdata = file_get_contents("php://input");
		$req = json_decode($postdata);

		$pass = $req->pass;

		$hasil = $this->db->query("update user set pass='$pass' where iduser='$iduser'");
	});
});
