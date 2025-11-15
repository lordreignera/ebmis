<?php
//error_reporting(E_ALL); ini_set('display_errors', 1);

  $servername = "localhost";
  $username = "root";
  $password = "@Esther12345";
  $dbname = "ebims";

$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("DB Connection failed: " . mysqli_connect_error());
}

// Step 1: Fetch pending rows
$sql = "SELECT id, ref,trans_id FROM raw_payments WHERE status in ('00','01') and (pay_status IS NULL or pay_message = 'Pending')";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $id  = $row['id'];
        $ref = $row['trans_id'];


$f = 'reference='.urlencode($ref);
//print($f);
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://emuria.net/flexipay/checkFromMMStatusProd.php',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => $f,
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/x-www-form-urlencoded'
  ),
));

$response = curl_exec($curl);
        $err      = curl_error($curl);
        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err . "\n";
            continue;
        }

        // Step 3: Parse API response (JSON)
        $data = json_decode($response, true);

        if (is_array($data) && isset($data['statusCode'])) {
            $statusCode   = $data['statusCode'];
            $statusDesc   = $data['statusDescription'];
            $flexiRef     = $data['flexipayReferenceNumber'] ?? null;
            $payDate      = date('Y-m-d H:i:s');

            if($statusCode == "01"){
              $status = "01";
            }else{
              $status = "Processed";
            }

            // Step 4: Update DB
            $update = "UPDATE raw_payments 
                       SET status='" . mysqli_real_escape_string($conn, $status) . "',
                           pay_status='" . mysqli_real_escape_string($conn, $statusCode) . "',
                           pay_message='" . mysqli_real_escape_string($conn, $statusDesc) . "',
                           pay_date='" . $payDate . "'
                       WHERE id=" . intval($id);

            if (mysqli_query($conn, $update)) {
                echo "Updated ID $id with status $statusCode ($statusDesc)\n";
            } else {
                echo "Error updating ID $id: " . mysqli_error($conn) . "\n";
            }
        } else {
            echo "Invalid response for ref $ref: $response\n";
        }


//exit();
    }
} else {
    echo "No pending records found.\n";
}

mysqli_close($conn);

?>