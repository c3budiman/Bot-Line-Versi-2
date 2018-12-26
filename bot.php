<?php
require_once('./line_class.php');

$channelAccessToken = 'C+rP3iraU1jkh4xRXmf1EiSmshbxBkxYmNM8ZyN7wTTYPSiEPxcjShO7k9GlBH88mwOK711vKTlQDUa1anRyn96rHzLDznyYBBoVgXg1ZrpYW8rr/pzWT+/P5rZG7SyWXmhVRrNMdkBDtJPYzms/jQdB04t89/1O/w1cDnyilFU='; //sesuaikan
$channelSecret = 'cee9a47046aed36d01fd4de159285569';//sesuaikan
$client = new LINEBotTiny($channelAccessToken, $channelSecret);
$userId 	= $client->parseEvents()[0]['source']['userId'];
$replyToken = $client->parseEvents()[0]['replyToken'];
$timestamp	= $client->parseEvents()[0]['timestamp'];
$message 	= $client->parseEvents()[0]['message'];
$messageid 	= $client->parseEvents()[0]['message']['id'];
$profil = $client->profil($userId);
$pesan_datang = $message['text'];

//function section....
$fungsi = explode(" ",$pesan_datang);
function toNumber($dest)
{
		if ($dest)
				return ord(strtolower($dest)) - 96;
		else
				return 0;
}

//pesan bergambar dan tidak...
if($message['type']=='text')
{
	if($pesan_datang=='1')
	{
		$balas = array(
							'replyToken' => $replyToken,
							'messages' => array(
								array(
										'type' => 'text',
										'text' => 'Halo '.$profil->displayName.', Anda memilih menu 1,'
									)
							)
						);
	}
	else
	if($fungsi[0]=='Jadwal')
	{
		$get_sub = array();
		$kelas = substr($pesan_datang,7);
		$url = "https://freesent.me/api-line/jadwal.php?kelas=".$kelas."";
		$json = file_get_contents($url);
		$hasil = json_decode($json);
		if ($hasil->data[0]->kelas) {
			$fetchmessage = "Kelas kamu : ".$hasil->data[0]->kelas."\n";
			foreach ($hasil->data as $datanya) {
				$fetchmessage .= $datanya->hari." | ".$datanya->waktu." | ".$datanya->ruang."\n".$datanya->mata_kuliah."\n\n";
			}
		} else {
			$fetchmessage = 'Tidak ditemukan jadwal untuk kelas : '.$kelas;
		}

		$get_sub[] = array(
									'type' => 'text',
									'text' => $fetchmessage
								);

		$balas = array(
					'replyToken' 	=> $replyToken,
					'messages' 		=> $get_sub
				 );
	}
	else
	if($fungsi[0]=='LoginGucc')
	{
		$get_sub = array();
		$usernamepassword = substr($pesan_datang,10);
		$usernamepasswordex = explode(" ",$usernamepassword);
		if ($usernamepasswordex[0] && $usernamepasswordex[1]) {
			//db conn :
			$servername = "localhost";
			$username = "root";
			$password = "c3543211";
			$dbname = "gucc";
			$conn = mysqli_connect($servername, $username, $password, $dbname);
			if (!$conn) {
				 $tugas = "connection to db failed. dang c3budiman u need to fix it!";
			}
			//select dulu ada ga di db user ini pernah login ga?
			$sql = "SELECT * from absen where uid_line='".$profil->userId."'";
			$result = $conn->query($sql);
			if ($result->num_rows > 0) {
				$url = "http://lpug.gunadarma.ac.id/absengucc/api/login";
				$data = array('username' => $usernamepasswordex[0], 'password' => $usernamepasswordex[1]);
				$options = array(
						'http' => array(
								'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
								'method'  => 'POST',
								'content' => http_build_query($data)
						)
				);
				$context  = stream_context_create($options);
				$result = file_get_contents($url, false, $context);
				if ($result === FALSE) { echo "gagal"; die(); }
				$hasil = json_decode($result);

				if ($hasil->message != "user not found") {
					$token = mysqli_real_escape_string($conn,$hasil->message);
					$sql = "UPDATE absen set token_gucc='".$token."' WHERE uid_line='".$profil->userId."'";
					if ($conn->query($sql) === TRUE) {
							$tugas = $profil->displayName." Kamu pernah login, jadi saya refresh token nya";
					} else {
							$tugas = "Gagal Update db, coba lagi nanti!";
					}
				} else {
					$tugas = "Gagal Login apakah username dan password benar?";
				}


				$conn->close();
				mysqli_close($conn);
			  $messagenya = $tugas;
			} else {
				//ga pernah login...
				//bagian insert token ke db per user line....
				$url = "http://lpug.gunadarma.ac.id/absengucc/api/login";
				$data = array('username' => $usernamepasswordex[0], 'password' => $usernamepasswordex[1]);
				$options = array(
				    'http' => array(
				        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				        'method'  => 'POST',
				        'content' => http_build_query($data)
				    )
				);
				$context  = stream_context_create($options);
				$result = file_get_contents($url, false, $context);
				if ($result === FALSE) { echo "gagal"; die(); }
				$hasil = json_decode($result);

				$token = mysqli_real_escape_string($conn,$hasil->message);
				$sql = "INSERT INTO absen (uid_line, token_gucc) VALUES ('".$profil->userId."', '".$token."')";
				if ($conn->query($sql) === TRUE) {
						$tugas = $profil->displayName." Anda Berhasil Login, token telah disimpan";
				} else {
						$tugas = "Gagal Login coba cek lagi username passsword coy!";
				}

				$conn->close();
				mysqli_close($conn);
				$messagenya = $tugas;
			}
		} else {
			$messagenya = 'masukkan perintah seperti ini : LoginGucc <username> <password>';
		}

		$get_sub[] = array(
									'type' => 'text',
									'text' => $messagenya

								);

		$balas = array(
					'replyToken' 	=> $replyToken,
					'messages' 		=> $get_sub
				 );
	}
	else
	if($fungsi[0]=='AbsenGucc')
	{
		$get_sub = array();

		$servername = "localhost";
		$username = "root";
		$password = "c3543211";
		$dbname = "gucc";

		$conn = mysqli_connect($servername, $username, $password, $dbname);
		if (!$conn) {
		   $fetchmessage = "connection to db failed. dang c3budiman u need to fix it!";
		}

		$sql = "SELECT * from absen where uid_line='".$profil->userId."'";
		$result = mysqli_query($conn, $sql);
		if (mysqli_num_rows($result) > 0) {
		    while($row = mysqli_fetch_assoc($result)) {
						$url = "http://lpug.gunadarma.ac.id/absengucc/api/login/absence?username=".$row['token_gucc']."";
						$json = file_get_contents($url);
						$hasil = json_decode($json);
						$fetchmessage = "Status Absen anda : \n";
						if ($hasil->success == "true" || $hasil->success == true || $hasil->message == "absence success!") {
								$fetchmessage .= "Absen sukses !";
						} else {
							$fetchmessage .= "absen gagal, silahkan login kembali dengan perintah : \nLoginGucc <username> <password>";
						}
		    }
		} else {
		    $fetchmessage = "Token belum masuk ke db, silahkan login dengan perintah LoginGucc <username> <password>";
		}

		mysqli_close($conn);

		$get_sub[] = array(
									'type' => 'text',
									'text' => $fetchmessage
								);

		$balas = array(
					'replyToken' 	=> $replyToken,
					'messages' 		=> $get_sub
				 );
	}
	else
	if($fungsi[0]=='SubmitReportGucc')
	{
		$get_sub = array();
		$reportnya = substr($pesan_datang,strlen($fungsi[0])+1);
		$servername = "localhost";
		$username = "root";
		$password = "c3543211";
		$dbname = "gucc";

		$conn = mysqli_connect($servername, $username, $password, $dbname);
		if (!$conn) {
		   $fetchmessage = "connection to db failed. dang c3budiman u need to fix it!";
		}

		$sql = "SELECT * from absen where uid_line='".$profil->userId."'";
		$result = mysqli_query($conn, $sql);
		if (mysqli_num_rows($result) > 0) {
		    while($row = mysqli_fetch_assoc($result)) {
						$url = "http://lpug.gunadarma.ac.id/absengucc/api/report/submit?username=".$row['token_gucc']."";
						$data = array('username' => $row['token_gucc'], 'message' => $reportnya);
						$options = array(
								'http' => array(
										'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
										'method'  => 'POST',
										'content' => http_build_query($data)
								)
						);
						$context  = stream_context_create($options);
						$result = file_get_contents($url, false, $context);
						if ($result === FALSE) { echo "gagal"; die(); }
						$hasil = json_decode($result);

						$fetchmessage = "Status report anda : \n";
						if ($hasil->success == "true" || $hasil->success == true) {
								$fetchmessage .= "Report Sukses !";
						} else {
							$fetchmessage .= "Report gagal, silahkan login kembali dengan perintah : \nLoginGucc <username> <password>";
						}
		    }
		} else {
		    $fetchmessage = "Token belum masuk ke db, silahkan login dengan perintah LoginGucc <username> <password>";
		}

		mysqli_close($conn);

		$get_sub[] = array(
									'type' => 'text',
									'text' => $fetchmessage
								);

		$balas = array(
					'replyToken' 	=> $replyToken,
					'messages' 		=> $get_sub
				 );
	}
	else
	if($fungsi[0]=='LogoutAbsenGucc')
	{
		$get_sub = array();

		$servername = "localhost";
		$username = "root";
		$password = "c3543211";
		$dbname = "gucc";

		$conn = mysqli_connect($servername, $username, $password, $dbname);
		if (!$conn) {
			 $fetchmessage = "connection to db failed. dang c3budiman u need to fix it!";
		}

		$sql = "SELECT * from absen where uid_line='".$profil->userId."'";
		$result = mysqli_query($conn, $sql);
		if (mysqli_num_rows($result) > 0) {
				while($row = mysqli_fetch_assoc($result)) {
						$url = "http://lpug.gunadarma.ac.id/absengucc/api/login/logout?username=".$row['token_gucc']."";
						$data = array('username' => $row['token_gucc']);
						$options = array(
								'http' => array(
										'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
										'method'  => 'POST',
										'content' => http_build_query($data)
								)
						);
						$context  = stream_context_create($options);
						$result = file_get_contents($url, false, $context);
						if ($result === FALSE) { echo "gagal"; die(); }
						$hasil = json_decode($result);

						$fetchmessage = "Status Absen anda : \n";
						if ($hasil->success == "true" || $hasil->success == true) {
								$fetchmessage .= "Logout sukses !";
						} else {
							$fetchmessage .= "Logout gagal, sepertinya kamu belum pernah login absen deh...";
						}
				}
		} else {
				$fetchmessage = "Token belum masuk ke db, silahkan login dengan perintah LoginGucc <username> <password>";
		}

		mysqli_close($conn);

		$get_sub[] = array(
									'type' => 'text',
									'text' => $fetchmessage
								);

		$balas = array(
					'replyToken' 	=> $replyToken,
					'messages' 		=> $get_sub
				 );
	}
	else
	if($fungsi[0]=='ReportGucc')
	{
		$get_sub = array();
		$servername = "localhost";
		$username = "root";
		$password = "c3543211";
		$dbname = "gucc";

		$conn = mysqli_connect($servername, $username, $password, $dbname);
		if (!$conn) {
		   $fetchmessage = "connection to db failed. dang c3budiman u need to fix it!";
		}

		$sql = "SELECT * from absen where uid_line='".$profil->userId."'";
		$result = mysqli_query($conn, $sql);
		if (mysqli_num_rows($result) > 0) {
		    while($row = mysqli_fetch_assoc($result)) {
						$url = "http://lpug.gunadarma.ac.id/absengucc/api/report/?username=".$row['token_gucc']."";
						$json = file_get_contents($url);
						$hasil = json_decode($json);
						$fetchmessage = "Last 5 Report yang anda submit adalah : \n";
						if ($hasil->message[0]->id) {
							foreach ($hasil->message as $datanya) {
								$fetchmessage .= "tanggal : ".$datanya->date."\nPesan Report : ".$datanya->message."\n\n";
							}
						}
		    }
		} else {
		    $fetchmessage = "Token belum masuk ke db, silahkan login dengan perintah LoginGucc <username> <password>";
		}

		mysqli_close($conn);

		$get_sub[] = array(
									'type' => 'text',
									'text' => $fetchmessage
								);

		$balas = array(
					'replyToken' 	=> $replyToken,
					'messages' 		=> $get_sub
				 );
	}
	else
	if($fungsi[0]=='RekapGucc')
	{
		$get_sub = array();
		$servername = "localhost";
		$username = "root";
		$password = "c3543211";
		$dbname = "gucc";

		$conn = mysqli_connect($servername, $username, $password, $dbname);
		if (!$conn) {
			 $fetchmessage = "connection to db failed. dang c3budiman u need to fix it!";
		}

		$sql = "SELECT * from absen where uid_line='".$profil->userId."'";
		$result = mysqli_query($conn, $sql);
		if (mysqli_num_rows($result) > 0) {
				while($row = mysqli_fetch_assoc($result)) {
						$url = "http://lpug.gunadarma.ac.id/absengucc/api/login/recap?username=".$row['token_gucc']."&date=2018-12&type=2";
						$json = file_get_contents($url);
						$hasil = json_decode($json);
						$fetchmessage = "Weekly Recap anda adalah : \n";
						if ($hasil->message[0]->week) {
							foreach ($hasil->message as $datanya) {
								$fetchmessage .= "Week : ".$datanya->week."\nHours spent : ".$datanya->hours2."\n\n";
							}
						}
				}
		} else {
				$fetchmessage = "Token belum masuk ke db, silahkan login dengan perintah LoginGucc <username> <password>";
		}

		mysqli_close($conn);

		$get_sub[] = array(
									'type' => 'text',
									'text' => $fetchmessage
								);

		$balas = array(
					'replyToken' 	=> $replyToken,
					'messages' 		=> $get_sub
				 );
	}
	else
	if($fungsi[0]=='AvailGucc')
	{
		$get_sub = array();
		$url = "http://lpug.gunadarma.ac.id/absengucc/api/avail";
		$json = file_get_contents($url);
		$hasil = json_decode($json);
		if ($hasil->message != []) {
			$fetchmessage = "Orang yang ada di ruangan saat ini : \n";
			foreach ($hasil->message as $datanya) {
				$fetchmessage .= "nama : ".$datanya->account."\nstatus :".$datanya->messageAvail."\n\n";
			}
		} else {
			$fetchmessage = 'Saat ini tidak ada orang di ruangan!';
		}

		$get_sub[] = array(
									'type' => 'text',
									'text' => $fetchmessage
								);

		$balas = array(
					'replyToken' 	=> $replyToken,
					'messages' 		=> $get_sub
				 );
	}
	else
	if($fungsi[0]=='NewsGucc')
	{
		$get_sub = array();
		$url = "http://lpug.gunadarma.ac.id/absengucc/api/news";
		$json = file_get_contents($url);
		$hasil = json_decode($json);
		if ($hasil->message != []) {
			$fetchmessage = "News Gucc Terupdate : \n";
			foreach ($hasil->message as $datanya) {
				$fetchmessage .= $datanya->title."\n".$datanya->date."\n".$datanya->message."\n\n";
				$fetchmessage .= "----------------------------------------------\n";
			}
		} else {
			$fetchmessage = 'Saat ini tidak ada news untuk di tampilkan!';
		}

		$get_sub[] = array(
									'type' => 'text',
									'text' => $fetchmessage
								);

		$balas = array(
					'replyToken' 	=> $replyToken,
					'messages' 		=> $get_sub
				 );
	}
	else
	if($pesan_datang=='Jadwal')
	{
		$get_sub = array();
		$aa =   array(
						'type' => 'image',
						'originalContentUrl' => 'https://raw.githubusercontent.com/c3budiman/FreeSent/master/absensi/public/avatar/avatar.png',
						'previewImageUrl' => 'https://raw.githubusercontent.com/c3budiman/FreeSent/master/absensi/public/avatar/avatar.png'
					);
		array_push($get_sub,$aa);

		$get_sub[] = array(
									'type' => 'text',
									'text' => 'Untuk Memunculkan Jadwal Dari Baak, Silahkan Ketik Jadwal <spasi> Kelas, contoh Jadwal 3ka01 (Coming Soon...)'
								);

		$balas = array(
					'replyToken' 	=> $replyToken,
					'messages' 		=> $get_sub
				 );
	}
	else
	if($pesan_datang=='Profil')
	{
		//download dlu poto si pengguna
		if (!file_exists('/var/www/html/FreeSent/absensi/public/api-line/'.$profil->userId.'.jpg')) {
			$ch = curl_init($profil->pictureUrl);
			$fp = fopen('/var/www/html/FreeSent/absensi/public/api-line/'.$profil->userId.'.jpg', 'wb');
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_exec($ch);
			curl_close($ch);
			fclose($fp);
		}

		//siapin array buat nampung
		$get_sub = array();
		$aa =   array(
						'type' => 'image',
						'originalContentUrl' => 'https://freesent.me/api-line/'.$profil->userId.'.jpg',
						'previewImageUrl' => 'https://freesent.me/api-line/'.$profil->userId.'.jpg'
					);
		$id =   array(
						'type' => 'text',
						'text' => 'ID : '.$profil->userId.''
					);
		array_push($get_sub,$aa);
		array_push($get_sub,$id);

		$get_sub[] = array(
									'type' => 'text',
									'text' => 'Nama : '.$profil->displayName.''
								);

		$balas = array(
					'replyToken' 	=> $replyToken,
					'messages' 		=> $get_sub
				 );
	}
	else
	if($pesan_datang=='Menu')
	{
		$get_sub = array();
		$get_sub[] = array(
									'type' => 'text',
									'text' =>
									'Jadwal -> untuk mengecek jadwal matkul dari universitas gunadarma'.chr(10).
									"\n".'Profil -> untuk mengecek profil line kamu'.chr(10).
									"\n".'Jam -> untuk mengecek waktu di server freesent'.chr(10).
									"\n".'Runtime -> untuk mengecek berapa lama server berjalan'.chr(10).
									"\n".'Anime -> untuk mengecek update anime oploverz'.chr(10).
									"\n".'Film <judul> -> untuk informasi ttg film'.chr(10).
									"\n".'Youtube <link> -> untuk mendownload video youtube'.chr(10).
									"\n".'Shalat <Nama-Kota> -> untuk mengetahui jadwal shalat'.chr(10).
									"\n".'Cuaca <Nama-Kota> -> untuk mengetahui cuaca di kota tersebut'.chr(10).
									"\n".'Apa <kata-eng> -> untuk mengetahui definisi dari suatu kata slank'.chr(10).
									"\n".'Lokasi -> untuk mengecek dimana lokasi kampus D'.chr(10).
									'Menu Lainnya coming soon....'."\n Created by https://github.com/c3budiman"
								);
		$balas = array(
					'replyToken' 	=> $replyToken,
					'messages' 		=> $get_sub
				 );
	}
	else
	if($pesan_datang=='MenuGucc')
	{
		$get_sub = array();
		$get_sub[] = array(
									'type' => 'text',
									'text' =>
									'LoginGucc -> LoginGucc [spasi] <username> [spasi] <password>'.chr(10).
									"\n".'AbsenGucc -> untuk absen ke dalam sistem gucc, ingat logout sebelum di autologout'.chr(10).
									"\n".'SubmitReportGucc -> SubmitReportGucc [spasi] <pesan report>'.chr(10).
									"\n".'LogoutAbsenGucc -> untuk keluar dari absen, pastikan telah isi report terlebih dahulu'.chr(10).
									"\n".'AvailGucc -> untuk mengecek available staff'.chr(10).
									"\n".'RekapGucc -> untuk mengecek rekapan anda'.chr(10).
									"\n".'ReportGucc -> untuk mengecek last 5 report yang anda submit'.chr(10).
									"\n".'NewsGucc -> untuk mengecek update news dari gucc'.chr(10).
									"\n".'Menu Lainnya coming soon....'."\n Created by https://github.com/c3budiman"
								);
		$balas = array(
					'replyToken' 	=> $replyToken,
					'messages' 		=> $get_sub
				 );
	}
	else
	if($pesan_datang=='Jam')
	{
		$balas = array(
							'replyToken' => $replyToken,
							'messages' => array(
								array(
										'type' => 'text',
										'text' => 'Jam Server Saya : '. date('Y-m-d H:i:s')
									)
							)
						);
	}
	else
	if($pesan_datang=='Lokasi')
	{
		$balas = array(
							'replyToken' => $replyToken,
							'messages' => array(
								array(
										'type' => 'location',
										'title' => 'Lokasi Kampus D.. Klik Detail',
										'address' => 'Margonda Raya',
										'latitude' => '-6.3652438',
										'longitude' => '106.8267824'
									)
							)
						);
	}
	else
	if($pesan_datang=='Runtime')
	{
		$output = shell_exec("uptime -p");
		$balas = array(
							'replyToken' => $replyToken,
							'messages' => array(
								array(
										'type' => 'text',
										'text' => $output
									)
							)
						);
	}
	else
	if($pesan_datang=='Sp'||$pesan_datang=='Ping')
	{
		$output = shell_exec("ping freesent.me -c 1 | grep time=");
		$balas = array(
							'replyToken' => $replyToken,
							'messages' => array(
								array(
										'type' => 'text',
										'text' => $output
									)
							)
						);
	}
	else
	if($fungsi[0]=='Film')
	{
		// unset($fungsi[0]);
		// foreach ($fungsi as $judul_film) {
		// 	$judul_full = $judul_full.$judul_film;
		// }
		$judul_full = urlencode(substr($pesan_datang,5));
		$uri = "http://www.omdbapi.com/?t=" . $judul_full . '&plot=full&apikey=d5010ffe';
		$response = file_get_contents($uri);
		$json = json_decode($response, true);
		$result = "\nJudul : ";
		$result .= $json['Title'];
		$result .= "\nRilis : ";
		$result .= $json['Released'];
		$result .= "\nTipe : ";
		$result .= $json['Genre'];
		$result .= "\nActors : ";
		$result .= $json['Actors'];
		$result .= "\nBahasa : ";
		$result .= $json['Language'];
		$result .= "\nNegara : ";
		$result .= $json['Country'];
		$balas = array(
							'replyToken' => $replyToken,
							'messages' => array(
								array(
										'type' => 'text',
										'text' => $result
									)
							)
						);
	}
	else
	if($fungsi[0]=='Shalat')
	{
		$keyword = substr($pesan_datang,7);
		$uri = "https://time.siswadi.com/pray/" . $keyword;
    $response = file_get_contents($uri);
    $json = json_decode($response, true);
    $result = "====[JadwalShalat]====";
    $result .= "\nLokasi : ";
		$result .= $json['location']['address'];
		$result .= "\nTanggal : ";
		$result .= $json['time']['date'];
		$result .= "\n\nShubuh : ";
		$result .= $json['data']['Fajr'];
		$result .= "\nDzuhur : ";
		$result .= $json['data']['Dhuhr'];
		$result .= "\nAshar : ";
		$result .= $json['data']['Asr'];
		$result .= "\nMaghrib : ";
		$result .= $json['data']['Maghrib'];
		$result .= "\nIsya : ";
		$result .= $json['data']['Isha'];
		$result .= "\n\nPencarian : Google";
		$result .= "\n====[JadwalShalat]====";

		$balas = array(
							'replyToken' => $replyToken,
							'messages' => array(
								array(
										'type' => 'text',
										'text' => $result
									)
							)
						);
	}
	else
	if($fungsi[0]=='Cuaca')
	{
		$keyword = substr($pesan_datang,6);
		$uri = "http://api.openweathermap.org/data/2.5/weather?q=" . $keyword . ",ID&units=metric&appid=e172c2f3a3c620591582ab5242e0e6c4";
		$response = file_get_contents($uri);
		$json = json_decode($response, true);
		$result = "====[InfoCuaca]====";
		$result .= "\nKota : ";
		$result .= $json['name'];
		$result .= "\nCuaca : ";
		$result .= $json['weather']['0']['main'];
		$result .= "\nDeskripsi : ";
		$result .= $json['weather']['0']['description'];
		$result .= "\n\nPencariaan : Google";
		$result .= "\n====[InfoCuaca]====";

		$balas = array(
							'replyToken' => $replyToken,
							'messages' => array(
								array(
										'type' => 'text',
										'text' => $result
									)
							)
						);
	}
	else
	if($fungsi[0]=='Youtube')
	{
		$keyword = substr($pesan_datang,8);
		$uri = "https://www.saveitoffline.com/process/?url=" . $keyword . '&type=json';
		$response = file_get_contents($uri);
		$json = json_decode($response, true);
		$result = "====[SaveOffline]====\n";
		$result .= "Judul : \n";
		$result .= $json['title'];
		$result .= "\n\nUkuran : \n";
		$result .= $json['urls'][0]['label'];
		$result .= "\n\nURL Download : \n";
		$result .= $json['urls'][0]['id'];
		$result .= "\n\nUkuran : \n";
		$result .= $json['urls'][1]['label'];
		$result .= "\n\nURL Download : \n";
		$result .= $json['urls'][1]['id'];
		$result .= "\n\nUkuran : \n";
		$result .= $json['urls'][2]['label'];
		$result .= "\n\nURL Download : \n";
		$result .= $json['urls'][2]['id'];
		$result .= "\n\nUkuran : \n";
		$result .= $json['urls'][3]['label'];
		$result .= "\n\nURL Download : \n";
		$result .= $json['urls'][3]['id'];
		$result .= "\n\nPencarian : Google\n";
		$result .= "====[SaveOffline]====";
		$balas = array(
							'replyToken' => $replyToken,
							'messages' => array(
								array(
										'type' => 'text',
										'text' => $result
									)
							)
						);
	}
	else
	if($fungsi[0].' '.$fungsi[1]=='Apa itu')
	{
		$keyword = substr($pesan_datang,8);
		$uri = "http://api.urbandictionary.com/v0/define?term=" . $keyword;
		$response = file_get_contents($uri);
		$json = json_decode($response, true);
		$result = $json['list'][0]['definition'];
		$result .= "\n\nExamples : \n";
		$result .= $json['list'][0]['example'];
		$balas = array(
							'replyToken' => $replyToken,
							'messages' => array(
								array(
										'type' => 'text',
										'text' => $result
									)
							)
						);
	}
	else
	if($fungsi[0]=='Todo')
	{
		$servername = "localhost";
		$username = "root";
		$password = "c3543211";
		$dbname = "todo";
		$conn = mysqli_connect($servername, $username, $password, $dbname);
		if (!$conn) {
		   $tugas = "connection to db failed. dang c3budiman u need to fix it!";
		}
		$sql = "SELECT * FROM todo";
		$result = mysqli_query($conn, $sql);
		if (mysqli_num_rows($result) > 0) {
		    while($row = mysqli_fetch_assoc($result)) {
		        // echo "id: " . $row["id"]. " - Name: " . $row["nama_tugas"] . "<br>";
						$tugas .= "No : ".$row["id"]."\n Nama Todo : ".$row["nama_tugas"]."\n Deskripsi : ".$row["deskripsi"]."\n\n";
		    }
		} else {
		    $tugas = "no todo, or todo is already cleared....";
		}

		mysqli_close($conn);
		$balas = array(
							'replyToken' => $replyToken,
							'messages' => array(
								array(
										'type' => 'text',
										'text' => $tugas
									)
							)
						);
	}
	else
	if($fungsi[0]=='Done')
	{
		$keyword = substr($pesan_datang,5);
		$servername = "localhost";
		$username = "root";
		$password = "c3543211";
		$dbname = "todo";
		$conn = mysqli_connect($servername, $username, $password, $dbname);
		if (!$conn) {
			 $tugas = "connection to db failed. dang c3budiman u need to fix it!";
		}
		$keyword = mysqli_real_escape_string($conn,$keyword);
		$sql = "DELETE FROM todo WHERE id=".$keyword;

		if ($conn->query($sql) === TRUE) {
		    $tugas = "Tugas no ".$keyword." Selesai";
		} else {
		    $tugas = "Tidak ada tugas dengan no segituh...";
		}

		$conn->close();

		mysqli_close($conn);
		$balas = array(
							'replyToken' => $replyToken,
							'messages' => array(
								array(
										'type' => 'text',
										'text' => $tugas
									)
							)
						);
	}
	else
	if($fungsi[0].' '.$fungsi[1]=='New todo')
	{
		$keyword = substr($pesan_datang, 9+strlen($fungsi[2]) );
		$servername = "localhost";
		$username = "root";
		$password = "c3543211";
		$dbname = "todo";
		$conn = mysqli_connect($servername, $username, $password, $dbname);
		if (!$conn) {
			 $tugas = "connection to db failed. dang c3budiman u need to fix it!";
		}
		$title = mysqli_real_escape_string($conn,$fungsi[2]);
		$keyword = mysqli_real_escape_string($conn,$keyword);
		$sql = "INSERT INTO todo (nama_tugas, deskripsi, creator) VALUES ('".$title."', '".$keyword."', '".$profil->displayName."')";

		if ($conn->query($sql) === TRUE) {
				$tugas = "Tugas ".$title." berhasil ditambahkan";
		} else {
				$tugas = "Tidak ada tugas dengan no segituh...";
		}

		$conn->close();

		mysqli_close($conn);
		$balas = array(
							'replyToken' => $replyToken,
							'messages' => array(
								array(
										'type' => 'text',
										'text' => $tugas
									)
							)
						);
	}
	else
	if($fungsi[0].' '.$fungsi[1]=='Menu todo')
	{
		$get_sub = array();
		$get_sub[] = array(
									'type' => 'text',
									'text' =>
									'Todo -> untuk mengecek todo yang tersedia untuk dikerjakan'.chr(10).
									"\n".'New todo <judul> <deskripsi> -> untuk menambah todo'.chr(10).
									"\n".'Done <no-todo> -> untuk memberitahu bahwa todo tersebut telah selesai'
								);
		$balas = array(
					'replyToken' 	=> $replyToken,
					'messages' 		=> $get_sub
				 );
	}
	else
	if($fungsi[0]=='Apakah')
	{
		$keyword = substr($pesan_datang,7);
		$char = str_split($keyword);
		$total = 0;
		foreach ($char as $c) {
			$total = ($total + toNumber($c))/3;
		}

		if ($total % 2 == 0) {
		  $jawaban = 'Ya';
		} else {
			$jawaban = 'Tidak';
		}

		$balas = array(
							'replyToken' => $replyToken,
							'messages' => array(
								array(
										'type' => 'text',
										'text' => $jawaban
									)
							)
						);
	}
	else
	if($fungsi[0]=='Github')
	{
		$keyword = urlencode(substr($pesan_datang,7));
		$opts = [
		        'http' => [
		                'method' => 'GET',
		                'header' => [
		                        'User-Agent: PHP'
		                ]
		        ]
		];

		$context = stream_context_create($opts);
		$uri = 'https://api.github.com/search/repositories?access_token=a68375b4d59e836a0691ffd6c4ffd203b71236c7&q='.$keyword;
		$response = file_get_contents($uri, false, $context);
		$json = json_decode($response, true);
    $result = "====[GithubRepo]====";
    $result .= "\nNama Repository : ";
    $result .= $json['items'][0]['name'];
    $result .= "\nNama Github : ";
    $result .= $json['items'][0]['full_name'];
    $result .= "\nLanguage : ";
    $result .= $json['items'][0]['language'];
    $result .= "\nUrl Github : ";
    $result .= $json['items'][0]['owner']['html_url'];
    $result .= "\nUrl Repository : ";
    $result .= $json['items'][0]['html_url'];
    $result .= "\nPrivate : ";
		$private = var_export($json['items'][0]['private'], true);
    $result .= $private;
    $result .= "\n\nPencarian : Google";
    $result .= "\n====[GithubRepo]====";

		$balas = array(
							'replyToken' => $replyToken,
							'messages' => array(
								array(
										'type' => 'text',
										'text' => $result
									)
							)
						);
	}
	else
	if($fungsi[0]=='Pic')
	{
		$keyword = urlencode(substr($pesan_datang,4));
		$opts = [
		        'http' => [
		                'method' => 'GET',
		                'header' => [
		                        'User-Agent: PHP'
		                ]
		        ]
		];

		$context = stream_context_create($opts);
		$uri = 'http://api.giphy.com/v1/gifs/search?q='.$keyword.'&api_key=dc6zaTOxFJmzC';
		$response = file_get_contents($uri, false, $context);
		$json = json_decode($response, true);
		$get_sub = array();
		$aa = array(
						'type' => 'image',
						'originalContentUrl' => 'https://i.giphy.com/media/'.$json['data'][0]['id'].'/giphy-downsized.gif',
						'previewImageUrl' => 'https://i.giphy.com/media/'.$json['data'][0]['id'].'/giphy-downsized.gif'
					);
		array_push($get_sub,$aa);

		$balas = array(
					'replyToken' 	=> $replyToken,
					'messages' 		=> $get_sub
				 );
	}
	else
	if($fungsi[0]=='Gif')
	{
		$keyword = urlencode(substr($pesan_datang,4));
		$opts = [
						'http' => [
										'method' => 'GET',
										'header' => [
														'User-Agent: PHP'
										]
						]
		];

		$context = stream_context_create($opts);
		$uri = 'http://api.giphy.com/v1/gifs/search?q='.$keyword.'&api_key=dc6zaTOxFJmzC';
		$response = file_get_contents($uri, false, $context);
		$json = json_decode($response, true);
		$get_sub = array();
		$aa = array(
						'type' => 'video',
						'originalContentUrl' => 'https://i.giphy.com/media/'.$json['data'][0]['id'].'/giphy.mp4',
						'previewImageUrl' => 'https://i.giphy.com/media/'.$json['data'][0]['id'].'/480w_s.jpg'
					);
		array_push($get_sub,$aa);

		$balas = array(
					'replyToken' 	=> $replyToken,
					'messages' 		=> $get_sub
				 );
	}
	else
	if($fungsi[0].' '.$fungsi[1]=='Say to')
	{
		$keyword = substr($pesan_datang,7+strlen($fungsi[2]));
		$profil_sent = $client->profil($userId);

		$balas = array(
							'replyToken' => $replyToken,
							'messages' => array(
								array(
										'type' => 'text',
										'text' => 'Mengirim pesan ke : '.$profil_sent->displayName
									)
							)
						);

		$push = array(
							'to' => $fungsi[2],
							'messages' => array(
								array(
										'type' => 'text',
										'text' => $keyword
									)
							)
						);


		$client->pushMessage($push);

	}
	else
	if($pesan_datang=='Push')
	{

		$balas = array(
							'replyToken' => $replyToken,
							'messages' => array(
								array(
										'type' => 'text',
										'text' => 'Testing PUSH pesan ke anda'
									)
							)
						);

		$push = array(
							'to' => $userId,
							'messages' => array(
								array(
										'type' => 'text',
										'text' => 'Pesan ini dari Freesent.me'
									)
							)
						);


		$client->pushMessage($push);

	}
	else
	if($pesan_datang=='Anime'||$pesan_datang=='anime')
	{
		$url = "https://www.oploverz.in";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		if (curl_errno($ch)) die(curl_error($ch));


		curl_close($ch);
		$re = '/<div class="thumb">(.*?)<\/div>/m';
		preg_match_all($re, $response, $matches, PREG_SET_ORDER, 0);
		foreach ($matches as $tes => $anime) {
			//print_r();
			$re = '/title="(.*?)"/m';
			preg_match_all($re, $anime[1], $judulnya, PREG_SET_ORDER, 0);
			$title_anime = $judulnya[0][1];

			$re = '/img width="140" height="78" src="(.*?)"/m';
			preg_match_all($re, $anime[1], $fotonya, PREG_SET_ORDER, 0);
			$pic_anime = $fotonya[0][1];

			$re = '/<a href="(.*?)"/m';
			preg_match_all($re, $anime[1], $linknya, PREG_SET_ORDER, 0);
			$link_anime = $linknya[0][1];

			$hasil = $hasil.$title_anime.chr(10).$link_anime.chr(10);
		}

		$balas = array(
							'replyToken' => $replyToken,
							'messages' => array(
								array(
										'type' => 'text',
										'text' => $hasil
									)
							)
		);
	}
}

$result =  json_encode($balas);
file_put_contents('./balasan.json',$result);
$client->replyMessage($balas);
