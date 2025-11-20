<?php
require_once('fungsi_validasi.php');

$dbhost="localhost";
$dbuser="root";
$dbpassword="";
$dbname="dkppjpx";

$dbconnection = null;

function opendb()
{
    global $dbhost, $dbuser, $dbpassword, $dbname, $dbconnection;
    $dbconnection = new mysqli($dbhost, $dbuser, $dbpassword, $dbname);
    if ($dbconnection->connect_error) {
        die("Koneksi gagal: " . $dbconnection->connect_error);
    }
}

function closedb()
{
    global $dbconnection;
    $dbconnection->close();
}

function escape_string($string)
{
    global $dbconnection;
    return $dbconnection->real_escape_string($string);
}

function insert_id()
{
    global $dbconnection;
    return $dbconnection->insert_id;
}

/**
 * Run a prepared SELECT and return mysqli_result (requires mysqlnd).
 * Usage: querydb_prepared("SELECT ... WHERE id = ?", "i", [$id])
 */
function querydb_prepared($sql, $types = "", $params = [])
{
    global $dbconnection;
    $stmt = $dbconnection->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed: " . $dbconnection->error);
    }
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    return $stmt->get_result();
}

/**
 * Run a prepared INSERT/UPDATE/DELETE.
 * Returns: affected rows.
 */
function exec_prepared($sql, $types = "", $params = [])
{
    global $dbconnection;
    $stmt = $dbconnection->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed: " . $dbconnection->error);
    }
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }
    return $stmt->affected_rows;
}

function querydb($query)
{
    global $dbconnection;
    $result = $dbconnection->query($query);
    if ($result === false) {
        $backtrace = debug_backtrace();
        $error_line = isset($backtrace[0]['line']) ? $backtrace[0]['line'] : 'Tidak dapat mengambil informasi baris.';
        die("<b>Warning</b>: Gagal melakukan query in <b>" . $dbconnection->error . "</b> on line <b>$error_line</b>");
    }
    return $result;
}

function query_result($result, $row, $field)
{
    $result->data_seek($row);  // Pindahkan pointer ke baris yang diinginkan
    $data = $result->fetch_array();  // Ambil data sebagai array
    return $data[$field];  // Kembalikan data dari kolom yang diinginkan
}



$key="SU5ESElAIyE=";
$val = new DKPPJPXvalidasi;
?>
