<?php
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    require 'src/Exception.php';
    require 'src/PHPMailer.php';
    require 'src/SMTP.php';
header('Content-Type: application/json');
$data= json_decode(file_get_contents('php://input'), true);
$id=uniqid();
$icalContent = "BEGIN:VCALENDAR\r\n";
$icalContent .= "VERSION:2.0\r\n";
$icalContent .= "PRODID:-//Your Company//Your Calendar//EN\r\n";
$icalContent .= "BEGIN:VEVENT\r\n";
$icalContent .= "UID:" . $id . "@yourdomain.com\r\n";
$icalContent .= "DTSTAMP:" . gmdate('Ymd') . 'T' . gmdate('His') . "Z\r\n";
$icalContent .= "DTSTART:20240105T090000Z\r\n";
$icalContent .= "DTEND:20240105T100000Z\r\n";
$icalContent .= "SUMMARY:Your Event Summary\r\n";
$icalContent .= "DESCRIPTION:Your Event Description\r\n";
$icalContent .= "END:VEVENT\r\n";
$icalContent .= "END:VCALENDAR\r\n";
$fileName = $id.".ics";
$filePath = "C:/wwwroot/ctb/ics/" . $fileName;
$newStart = $_POST['start']; // 新的起始时间
$newEnd = $_POST['end'];   // 新的终止时间
$newSummary = $_POST['summary'];
$newDescription = $_POST['desc'];

$icalContent = str_replace("DTSTART:20240105T090000Z", "DTSTART:" . $newStart, $icalContent);
$icalContent = str_replace("DTEND:20240105T100000Z", "DTEND:" . $newEnd, $icalContent);
$icalContent = str_replace("SUMMARY:Your Event Summary", "SUMMARY:" . $newSummary, $icalContent);
$icalContent = str_replace("DESCRIPTION:Your Event Description", "DESCRIPTION:" . $newDescription, $icalContent);
file_put_contents($filePath, $icalContent);
    emailto($fileName);
function emailto($name)
    {
        $mail = new PHPMailer(true);
        try {
            $mail->CharSet = "UTF-8";
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host = 'us2.smtp.mailhostbox.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'notification@gdhfi.tech';
            $mail->Password = 'aT)@TpGn6';
            $mail->SMTPSecure = '';
            $mail->Port = 25;

            $mail->From = 'notification@gdhfi.tech';
            $mail->FromName = 'Calendar';
            $mail->addAddress($_POST['recv'], 'User');
            $mail->addReplyTo('notification@gdhfi.tech', 'info');
            $yanzheng = $code;
            $mail->isHTML(true);
            $mail->Subject = '新增日程';
            $mail->Body = '你有新的日历<a href="http://1.117.63.78:11451/addcal.php?qid='.$name.'">Add to Calendar</a>';
            $mail->AltBody = '你有新的日历<a href="http://1.117.63.78:11451/addcal.php?qid='.$name.'">Add to Calendar</a>';

            $mail->send();
            echo json_encode(['success'=>true]);
        } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => '发生错误。' . $e->getMessage()]);
        }
    }

?>