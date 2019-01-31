<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
	<title>PayPal IPN Listener</title>
</head>

<?php
	use PHPMailer\PHPMailer\PHPMailer;
	require "PHPMailer/PHPMailer.php";
	require "PHPMailer/Exception.php";

	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
		header('Location: index.php');
		exit();
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "cmd=_notify-validate&" . http_build_query($_POST));
	$response = curl_exec($ch);
	curl_close($ch);

	file_put_contents("testipn.txt", $response);

	if ($response == "VERIFIED" && $_POST['receiver_email'] == "noreply@example.net") {
		$payerEmail = $_POST['payer_email'];
		$name = $_POST['first_name'] . " " . $_POST['last_name'];

		$price = $_POST['mc_gross'];
		$currency = $_POST['mc_currency'];
		$paymentStatus = $_POST['payment_status'];
		$paymentDate = date("F jS\, Y");

        $email_vars = array(
            'NAME' => $name,
            'DATE' => $paymentDate,
            'PRICE' => $price,
        );

        $emailBody = file_get_contents('mail.html');

        foreach($email_vars as $k=>$v) {
        $emailBody = str_replace('{'.strtoupper($k).'}', $v, $emailBody);
        }

		if ($paymentStatus == "Completed") {
			$mail = new PHPMailer();

			$mail->setFrom("noreply@example.net", "ACME Corp");
			$mail->addAddress($payerEmail, $name);
			$mail->isHTML(true);
			$mail->Subject = "Payment Received";
			$mail->Body = $emailBody;

			$mail->send();
		}
	}
?>
