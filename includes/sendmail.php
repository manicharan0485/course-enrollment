<?php
/**
 * send_mail.php
 * Drop-in SMTP mailer for indoeurosync.com
 *
 * Usage:
 *   require_once 'send_mail.php';
 *   $result = sendSmtpMail('to@example.com', 'Subject', '<p>HTML body</p>');
 *   if ($result === true) { // success } else { echo $result; // error string }
 */

function sendSmtpMail($to, $subject, $body, &$debug) {
    $smtpHost  = 'mail.indoeurosync.com';
    $smtpPort  = 465;
    $smtpUser  = 'no-reply@indoeurosync.com';
    $smtpPass  = '9@Indoeurosync';
    $fromName  = 'Indo-Euro Sync';
    $fromEmail = 'no-reply@indoeurosync.com';

    $debug[] = "Connecting to $smtpHost:$smtpPort via SSL...";
    $socket = @stream_socket_client(
        "ssl://$smtpHost:$smtpPort",
        $errno, $errstr, 30,
        STREAM_CLIENT_CONNECT,
        stream_context_create(['ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ]])
    );
    if (!$socket) {
        $debug[] = "✗ Connection failed: $errstr ($errno)";
        return false;
    }
    $debug[] = "✓ Connected";

    // Read helper: drains all lines of a multi-line response
    // e.g. "220-text\r\n220-text\r\n220 text\r\n"
    // stops when it hits the line with a SPACE after the code (not a dash)
    $readAll = function($socket, $label) use (&$debug) {
        $last = '';
        while ($line = fgets($socket, 1024)) {
            $debug[] = "$label: " . trim($line);
            $last = $line;
            // If char[3] is a space, this is the last line of the response
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $last; // return last line so caller can check the code
    };

    // Drain full 220 greeting (can be multiple 220- lines)
    $readAll($socket, 'Greeting');

    // EHLO — drain full 250 response
    fwrite($socket, "EHLO indoeurosync.com\r\n");
    $readAll($socket, 'EHLO');

    // AUTH LOGIN
    fwrite($socket, "AUTH LOGIN\r\n");
    $r = $readAll($socket, 'AUTH');
    if (strpos($r, '334') === false) {
        $debug[] = "✗ AUTH LOGIN failed";
        fclose($socket);
        return false;
    }

    // Username
    fwrite($socket, base64_encode($smtpUser) . "\r\n");
    $r = $readAll($socket, 'User');
    if (strpos($r, '334') === false) {
        $debug[] = "✗ Username rejected";
        fclose($socket);
        return false;
    }

    // Password
    fwrite($socket, base64_encode($smtpPass) . "\r\n");
    $r = $readAll($socket, 'Pass');
    if (strpos($r, '235') === false) {
        $debug[] = "✗ Auth failed - wrong password or account issue";
        fclose($socket);
        return false;
    }
    $debug[] = "✓ Authenticated";

    // MAIL FROM
    fwrite($socket, "MAIL FROM:<$fromEmail>\r\n");
    $r = $readAll($socket, 'MAIL FROM');

    // RCPT TO
    fwrite($socket, "RCPT TO:<$to>\r\n");
    $r = $readAll($socket, 'RCPT TO');
    if (strpos($r, '250') === false) {
        $debug[] = "✗ RCPT TO rejected";
        fclose($socket);
        return false;
    }

    // DATA
    fwrite($socket, "DATA\r\n");
    $readAll($socket, 'DATA');

    // Send message
    $msg  = "From: $fromName <$fromEmail>\r\n";
    $msg .= "To: $to\r\n";
    $msg .= "Subject: $subject\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
    $msg .= "Reply-To: digital@indoeurosync.com\r\n";
    $msg .= "\r\n";
    $msg .= $body . "\r\n.\r\n";

    fwrite($socket, $msg);
    $sent = fgets($socket, 1024);
    $debug[] = "SEND result: " . trim($sent);

    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    if (strpos($sent, '250') !== false) {
        $debug[] = "✓ Email SENT to $to";
        return true;
    }
    $debug[] = "✗ Send failed";
    return false;
}