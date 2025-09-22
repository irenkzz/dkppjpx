s<?php
include "../config/koneksi.php";
opendb();
// fungsi untuk menghindari injeksi dari user yang jahil
function anti_injection($data){
	$filter  = stripslashes(strip_tags(htmlspecialchars($data,ENT_QUOTES)));
	return $filter;
}
$kunci=base64_decode($key);
$username = anti_injection($_POST['username']);
$password = anti_injection(md5($_POST['password'].$kunci));

// menghindari sql injection
$injeksi_username = escape_string($username);
$injeksi_password = escape_string($password);
	
// pastikan username dan password adalah berupa huruf atau angka.
if (!ctype_alnum($injeksi_username) OR !ctype_alnum($injeksi_password)){
  echo "Sekarang loginnya tidak bisa di injeksi lho.";
}
else{
	$query  = "SELECT * FROM users WHERE username='$username' AND password='$password' AND blokir='N'";
	$login  = querydb($query);
	$ketemu = $login->num_rows;
	$r      = $login->fetch_array();

	// Apabila username dan password ditemukan (benar)
	if ($ketemu > 0){
		session_start();

		// bikin variabel session
    $_SESSION['namauser']    = $r['username'];
    $_SESSION['passuser']    = $r['password'];
    $_SESSION['namalengkap'] = $r['nama_lengkap'];
    $_SESSION['leveluser']   = $r['level'];
      
    // bikin id_session yang unik dan mengupdatenya agar slalu berubah 
    // agar user biasa sulit untuk mengganti password Administrator 
    $sid_lama = session_id();
	  session_regenerate_id();
    $sid_baru = session_id();
    querydb("UPDATE users SET id_session='$sid_baru' WHERE username='$username'");

    header("location:media.php?module=beranda");
	}
	else{
		echo "<script>alert('Gagal Login.'); window.location = 'index.php'</script>";
	}
}
closedb();
?>