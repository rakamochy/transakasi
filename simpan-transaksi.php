	<?php
 	include "../config/koneksi.php";
 	include "../config/fungsi_url.php";
 	include "../config/fungsi_rupiah.php";
 	include "../config/class_paging.php";
 	include "../config/time_since.php";
 		session_start();
 		// error_reporting(0);

include "../config/header.php";
?>

<?php
$id = $_SESSION['id'];
$password = $_SESSION['password'];

$sql = "SELECT * FROM kustomer WHERE id_kustomer='$id' AND password='$password'";
$hasil = mysqli_query($koneksi2, $sql);
$r = mysqli_fetch_array($hasil);
if($r['kelamin'] == 'L'){
	$kelamin='Laki-laki';
}
else {
	$kelamin='Perempuan';
}
// fungsi untuk mendapatkan isi keranjang belanja
function isi_keranjang(){
	$koneksi2 = mysqli_connect("localhost","root","","lapakku");
	if (mysqli_connect_errno()){
	echo "Koneksi database gagal : " . mysqli_connect_error();
	}
	$isikeranjang = array();
	$sid = $_SESSION['id'];
	$sed = "SELECT * FROM orders_temp WHERE id_session='$sid'";
	$sqli = mysqli_query($koneksi2, $sed);
	while ($r=mysqli_fetch_array($sqli)) {
		$isikeranjang[] = $r;
	}
	return $isikeranjang;
}

$tgl_skrg = date("Ymd");
$jam_skrg = date('Y-m-d H:i:s');	

$id = mysqli_fetch_array(mysqli_query($koneksi2, "SELECT id_kustomer FROM kustomer WHERE id_kustomer='$id' AND password='$password'"));

// mendapatkan nomor kustomer
$id_kustomer=$id['id_kustomer'];

$ref_id = $_SESSION['id'].date('Ymdhis');


// simpan data pemesanan 
mysqli_query($koneksi2, "INSERT INTO orders(tgl_order,jam_order,id_kustomer,pending,akun) VALUES('$tgl_skrg','$jam_skrg','$id_kustomer','1','$ref_id')");

  
// mendapatkan nomor orders
$id_orders=mysqli_insert_id($koneksi2);

// panggil fungsi isi_keranjang dan hitung jumlah produk yang dipesan
$isikeranjang = isi_keranjang();
$jml          = count($isikeranjang);

// simpan data detail pemesanan  
for ($i = 0; $i < $jml; $i++){
  mysqli_query($koneksi2, "INSERT INTO orders_detail(id_orders, id_produk, jumlah) 
               VALUES('$id_orders',{$isikeranjang[$i]['id_produk']}, {$isikeranjang[$i]['jumlah']})");
			   
	mysqli_query($koneksi2, "UPDATE orders SET id_produk={$isikeranjang[$i]['id_produk']}
					WHERE id_orders='$id_orders'");
	
}
  
// setelah data pemesanan tersimpan, update data pemesanan di tabel pemesanan sementara (orders_temp)

for ($i = 0; $i < $jml; $i++) {
  mysqli_query($koneksi2, "UPDATE orders_temp SET status='1'
	  	         WHERE id_orders_temp = {$isikeranjang[$i]['id_orders_temp']}");
}


    echo "<div class='container m-t-md'>
				<div class='row'>
					<div class='col-sm-12 link-info'>
		<div class='panel b-a'>
			<div class='panel-heading b-b b-light'>
				<span class='font-bold'><i class='fa fa-exchange m-r-xs'></i> Keranjang Belanja</span>
			</div>
	 <table class='table table-striped m-b-none'>
      <tr><td>Nama Lengkap   </td><td> : <b>$r[nama_lengkap]</b> </td></tr>
	  <tr><td>Jenis Kelamin  </td><td> : $kelamin </td></tr>
	  <tr><td>Alamat  </td><td> : $r[alamat] </td></tr>
	  <tr><td>Kota		     </td><td> : $r[daerah] </td></tr>	  
      <tr><td>Telpon         </td><td> : $r[hp] </td></tr>
      <tr><td>E-mail         </td><td> : $r[email] </td></tr>
	  </table></div>
      
      Nomor Order: <b>$id_orders</b><br />
	  Akun Transksi : $ref_id <br /><br/>";
	 
      $daftarproduk=mysqli_query($koneksi2, "SELECT * FROM orders_detail,produk 
                                 WHERE orders_detail.id_produk=produk.id_produk 
                                 AND id_orders='$id_orders'");

echo "<table id='applicantTable' class='table table-striped table-hover' cellpadding='2' cellspacing='0'>
      <tr><th>No</th><th>Nama Produk</th><th>Berat(Kg)</th><th>Qty</th><th>Harga Satuan</th><th>Sub Total</th></tr>";
      
$pesan="Terimakasih telah melakukan pemesanan online di toko online kami<br />
        Nama: $r[nama_lengkap] <br />
        Alamat: $r[alamat] <br/>
        Telpon: $r[hp] <br /><hr />
        
        Nomor Order: $id_orders <br />
        Data order Anda adalah sebagai berikut: <br /><br />";
        
$no=1;
while ($d=mysqli_fetch_array($daftarproduk)){
   $disc        = ($d[diskon]/100)*$d[harga];
   $hargadisc   = number_format(($d[harga]-$disc),0,",","."); 
   $subtotal    = ($d['harga']-$disc) * $d['jumlah'];

   $subtotalberat = $d['berat'] * $d['jumlah']; // total berat per item produk 
   $totalberat  = $totalberat + $subtotalberat; // grand total berat all produk yang dibeli

   $total       = $total + $subtotal;
   $subtotal_rp = format_rupiah($subtotal);    
   $total_rp    = format_rupiah($total);    
   $harga       = format_rupiah($d['harga']);

   echo "<tr><td>$no</td><td>$d[nama_produk]</td><td align=center>$d[berat]</td><td align=center>$d[jumlah]</td>
                             <td align=right>$harga</td><td align=right>$subtotal_rp</td></tr>";

   $pesan.="$d[jumlah] $d[nama_produk] -> Rp. $harga -> Subtotal: Rp. $subtotal_rp <br />";
   $no++;
}

$kota=$r['id_kota'];

$ongkos=mysqli_fetch_array(mysqli_query($koneksi2, "SELECT ongkos_kirim FROM kota WHERE id_kota='$kota'"));

$ongkoskirim1=$ongkos['ongkos_kirim'];
$ongkoskirim = $ongkoskirim1 * $totalberat;

$grandtotal    = $total + $ongkoskirim; 

$ongkoskirim_rp = format_rupiah($ongkoskirim);
$ongkoskirim1_rp = format_rupiah($ongkoskirim1); 
$grandtotal_rp  = format_rupiah($grandtotal);  

// dapatkan email_pengelola dan nomor rekening dari database
$sql2 = mysqli_query($koneksi2, "select email,rekening,phone from identitas");
$j2   = mysqli_fetch_array($sql2);

$pesan.="<br /><br />Total : Rp. $total_rp 
         <br />Ongkos Kirim untuk Tujuan Kota Anda : Rp. $ongkoskirim1_rp/Kg 
         <br />Total Berat : $totalberat Kg
         <br />Total Ongkos Kirim  : Rp. $ongkoskirim_rp		 
         <br />Grand Total : Rp. $grandtotal_rp 
         <br /><br />Silahkan lakukan pembayaran sebanyak Grand Total yang tercantum, rekeningnya: $j2[rekening]
         <br />Apabila sudah transfer, konfirmasi ke nomor: $j2[phone]";

$subjek="Pemesanan Online";

// Kirim email dalam format HTML
$dari = "From: $j2[email]\r\n";
$dari .= "Content-type: text/html\r\n";

// Kirim email ke kustomer
mail($email,$subjek,$pesan,$dari);

// Kirim email ke pengelola toko online
mail("$j2[email]",$subjek,$pesan,$dari);



echo "<tr><td colspan=5 align=right>Total : Rp. </td><td align=right><b>$total_rp</b></td></tr>
      <tr><td colspan=5 align=right>Ongkos Kirim untuk Tujuan Kota Anda: Rp. </td><td align=right><b>$ongkoskirim1_rp</b>/Kg</td></tr>      
	    <tr><td colspan=5 align=right>Total Berat : </td><td align=right><b>$totalberat Kg</b></td></tr>
      <tr><td colspan=5 align=right>Total Ongkos Kirim : Rp. </td><td align=right><b>$ongkoskirim_rp</b></td></tr>      
      <tr><td colspan=5 align=right>Grand Total : Rp. </td><td align=right><b>$grandtotal_rp</b></td></tr>
      </table>";
echo "<hr /><p>
			<div style='color:#E1473D;border:1px solid #E78686;padding:10px;background:#FFE1E1;'>
			  Silahkan Melakukan Pembayaran Pada Rekening dibawah ini, <br/>
			  Anda dapat melakukan Konfirmasi Pembayaran Melalui SMS Ke NO : <b>$j2[phone]</b> 
			  <br/>Dengan Format : 
			  <b>#No Orders # Nama Lengkap # Nominal Transfer #No. Rekening #</b><br/> Contoh : 
			  <b> # $id_orders#$_SESSION[nama]#$grandtotal_rp#Rekening: $j2[rekening] </b></div> <br />
               Apabila tidak melakukan pembayaran dalam 3 hari, maka transaksi dianggap batal. </p>    	
				<a href='history_transaksi.html' class='btn btn-success m-t-md m-b-sm' type='subtmit'>Konfirmasi Sekarang</a>				
              </div>
          </div>    
          </div>
          </div>";  


?>