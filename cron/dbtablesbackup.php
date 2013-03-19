<?
/* This script performs a dump-style backup of my entire database. (A virtually identical script also runs for the New Resolution Licensing Platform.) I have it automatically executed every day via a cron job. However, I could also easily execute it manually by simply running the script directly via my browser. The script itself comes from "Peter's Useful Crap" Blog (see www.theblog.ca/mysql-email-backup). In order to run it, I needed to first install Mail (see http://pear.php.net/manual/en/package.mail.mail.php) and Mail_mime (see http://pear.php.net/manual/en/package.mail.mail-mime.example.php) via cPanel's PEAR gateway. 
Note: I originally played with Peter's version of the script that compresses the dump into a tar.gz zip format, then reverted to a non-zip approach. (One can use 7-Zip (or some other zip archive/extraction utility) to decompress. Use of compression reduces the dumped file size by aout 75%!) I've included three comments below to show how to easily modify the script to effect a non-zipped dump.
*/

// Create the mysql backup file
// edit this section
$dbhost = "localhost"; // usually localhost
$dbuser = "paulme6_merlyn";
$dbpass = "fePhaCj64mkik";
$dbname = "paulme6_abridg";
$sendto = "Webmaster <paul@abridg.com>";
$sendfrom = "Automated Backup <donotreply@abridg.com>";
$sendsubject = "Daily MySQL Backup for ".date("F d, Y")." (Abridg)";
$bodyofemail = "Attached is the daily backup for ".date("F d, Y [h i a - T]").", generated via a cron job on dbtablesbackup.php.\n\nBy far the easiest way to restore my DB tables from the attached dump file is to use phpMyAdmin's Import utility. Create a DB (or import into an existing one) by selecting the desired target DB. Then using the Import utility (click the Import tab), simply browse to the (unzipped) dump file (which can be stored anywhere on my laptop computer [e.g. on Desktop]), and click ‘Go’. The DB tables will be restored in a matter of seconds. If the file is zipped, use 7-Zip or some other utility to unzip the tar.gz dump file before presenting it for use in a backup restoration.";
// don't need to edit below this section

/* To effect a zipped backup, uncomment the four lines below and comment out the two lines below them: */
//$backupfile = $dbname.date("Y-m-d").'.sql';
//$backupzip = $backupfile . '.tar.gz';
//system("mysqldump -h $dbhost -u $dbuser -p$dbpass $dbname > $backupfile"); // Good documentation on the mysqldump command at http://articles.sitepoint.com/article/backing-up-mysqldump
//system("tar -czvf $backupzip $backupfile");

$backupfile = $dbname . date("Y-m-d") . '.sql';
system("mysqldump -h $dbhost -u $dbuser -p$dbpass $dbname > $backupfile"); // Good documentation on the mysqldump command at http://articles.sitepoint.com/article/backing-up-mysqldump

include('Mail.php');
include('Mail/mime.php');

$message = new Mail_mime();
$text = "$bodyofemail";
$message->setTXTBody($text);
$message->AddAttachment($backupfile); /* To effect a zipped back-up, comment out this line and uncomment the line below instead. */
//$message->AddAttachment($backupzip);
$body = $message->get();
$extraheaders = array("From"=>"$sendfrom", "Subject"=>"$sendsubject");
$headers = $message->headers($extraheaders);
$mail = Mail::factory("mail");
$mail->send("$sendto", $headers, $body);

// Delete the file from your server
unlink($backupfile); /* To effect a non-zip back-up, delete the line below and uncomment this line instead. */
//unlink($backupzip);
?>
