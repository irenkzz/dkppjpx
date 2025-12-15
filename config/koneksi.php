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

/**
 * Check whether a column exists in a table (returns bool, suppresses errors).
 */
function db_column_exists($table, $column)
{
    global $dbconnection, $dbname;
    if (!$dbconnection instanceof mysqli) {
        return false;
    }

    $sql = "
        SELECT COUNT(*) AS cnt
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
    ";
    $stmt = $dbconnection->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("sss", $dbname, $table, $column);
    if (!$stmt->execute()) {
        $stmt->close();
        return false;
    }
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return isset($row['cnt']) ? ((int)$row['cnt'] > 0) : false;
}



$key="SU5ESElAIyE=";
$val = new DKPPJPXvalidasi;
?>
