<?php
// Test both ports with correct hostname
$host = 'indoeurosync.com';
$tests = [
    ['ssl://' . $host . ':465',  'Port 465 SSL'],
    ['tcp://' . $host . ':587',  'Port 587 STARTTLS'],
];

$smtpUser = 'no-reply@indoeurosync.com';
$smtpPass = '9@Indoeurosync';

foreach ($tests as [$addr, $label]) {
    echo "<h3>Testing: $label ($addr)</h3>";

    $ctx = stream_context_create(['ssl' => [
        'verify_peer'      => false,
        'verify_peer_name' => false,
    ]]);

    $socket = @stream_socket_client($addr, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$socket) {
        echo "<span style='color:red'>✗ Cannot connect: $errstr ($errno)</span><br><br>";
        continue;
    }
    echo "<span style='color:green'>✓ Connected</span><br>";

    $read = function($label) use ($socket) {
        $out = '';
        while ($line = fgets($socket, 1024)) {
            echo "<b>$label:</b> " . htmlspecialchars(trim($line)) . "<br>";
            $out = $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $out;
    };

    // Drain full greeting
    $read('Greeting');

    // STARTTLS for port 587
    if (strpos($addr, '587') !== false) {
        fwrite($socket, "EHLO indoeurosync.com\r\n");
        $read('EHLO');
        fwrite($socket, "STARTTLS\r\n");
        $r = fgets($socket, 1024);
        echo "<b>STARTTLS:</b> " . htmlspecialchars(trim($r)) . "<br>";
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    }

    fwrite($socket, "EHLO indoeurosync.com\r\n");
    $read('EHLO');

    fwrite($socket, "AUTH LOGIN\r\n");
    $r = $read('AUTH');
    if (strpos($r, '334') === false) {
        echo "<span style='color:red'>✗ AUTH LOGIN failed — server rejected command</span><br><br>";
        fclose($socket);
        continue;
    }

    fwrite($socket, base64_encode($smtpUser) . "\r\n");
    $r = $read('Username');
    if (strpos($r, '334') === false) {
        echo "<span style='color:red'>✗ Username rejected</span><br><br>";
        fclose($socket);
        continue;
    }

    fwrite($socket, base64_encode($smtpPass) . "\r\n");
    $r = $read('Password');
    if (strpos($r, '235') === false) {
        echo "<span style='color:red'>✗ Wrong password or account locked</span><br><br>";
        fclose($socket);
        continue;
    }

    echo "<span style='color:green;font-size:1.2em'><b>✓ AUTHENTICATED on $label</b></span><br><br>";
    fwrite($socket, "QUIT\r\n");
    fclose($socket);
}
?>