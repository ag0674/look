<!-- SPREADSHEET UPLOAD 
This code contains a form for the user to upload a file or text
The system will read from either text or file.
It will then transfer the read data to deposit.php
 -->
<?php
ini_set ( 'display_errors', 1 );ini_set ( 'display_startup_errors', 1 );error_reporting ( E_ALL );
include ("existsServer.php");
include ("sanitize.php");
include ("inputRates.php");

if (! isset ( $_SESSION )) {
	session_start ();
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
<title>Upload Document</title>
<header style="position= static; background-color:#82CAFA;text-align:left;padding:0px">
<img src="headr.PNG" style="positon: static"></img> </header>
<h1 align="center">Upload Document</h1>
<!--bootstrap layout -->
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet"href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
<script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
</head>

<body>
	<div class="container">
	
		<!--Form to submit text -->
		<p>
			<b>Type Servernames Here:</b>
		</p>
		<form method="post" action="upload.php">
			<div class="form-group">
				<textarea class="form-control" name="serverText" rows="5" id="comment"></textarea>
				<br> <input type="submit" name="submitServers" value="Submit Servernames">
			</div>
		<?php inputRatesFormForUpload(); ?>
		</form>

		<!-- form to upload a document -->		
		<h2>OR</h2>
		<p>
		<br><b>Select a document to upload for Lease Roll</b><br> Upload a
		.txt, .doc, or .docx. File must contain servernames only
		</p>
		<form action="upload.php" method="post" enctype="multipart/form-data">
			<input type="file" name="fileToUpload" id="fileToUpload"><br> 
			<input type="submit" value="Upload Document" name="submit">
		<?php inputRatesFormForUpload(); ?>
		</form>
	
<?php
// if server text is uploaded
if (isset ( $_POST ['submitServers'] )) {
	$serverText = "";
	$serverText = sanitizeString ( $_POST ["serverText"] );
	
	$contentArray = explode ( "\n", $serverText ); // make array
	$contentArray = removeSpace ( $contentArray ); // remove random space at end
	
	$modified = addServername ( $contentArray );
	
	$implodedArray = implode ( " ", $modified ); // turns back into string for query
	
	validServers ( $contentArray, $implodedArray ); // check if servers are valid
	                                            
	$contentArray = $implodedArray;
	$_SESSION ['contentArray'] = $contentArray;
	
	setVariables ();
?>
	<script type="text/javascript">
		window.location.href = 'http://localhost/PHP/deposit.php';
	</script>
<?php
	exit ();
}

// if a document is uploaded
if (isset ( $_POST ['submit'] )) {
	// $targetDir = "C:/testUploads/{$_FILES['fileToUpload']['name']}"; // Windows
	$targetDir = "/var/www/html/testUploads/{$_FILES['fileToUpload']['name']}"; // Linux
	$targetFile = $targetDir . basename ( $_FILES ['fileToUpload'] ['name'] );
	$docFileType = pathinfo ( $targetFile, PATHINFO_EXTENSION );
	
	// check if it is a text file
	if ($docFileType != "docx" && $docFileType != "doc" && $docFileType != "txt") {
		echo "<img src='http://wiki.univention.de/images/e/ee/Icon-16x16-warn.png'><strong>Warning: </strong>Document must be a DOC, DOCX, OR TXT FILE";
	} else {
		
		// file has been uploaded to destination
		if (move_uploaded_file ( $_FILES ['fileToUpload'] ['tmp_name'], $targetDir )) {
			echo "File is valid and uploaded <br>";
			
			// Open and read from the file
			$myFile = fopen ( $targetDir, "r" ) or die ( "Unable to open file!" );
			$fileContent = file_get_contents ( $targetDir );
			$fileContent = sanitizeString ( $fileContent );
			
			$contentArray = explode ( "\n", $fileContent ); // make array

			$modified = addServername ( $contentArray );
			
			$implodedArray = implode ( " ", $modified ); // turns into string for query
			
			validServers ( $contentArray, $implodedArray ); // check if servers are valid
			
			$contentArray = $implodedArray;
			$_SESSION ['contentArray'] = $contentArray;
			fclose ( $myFile );
			
			setVariables ();
			header ( "Location: http://localhost/PHP/deposit.php" );
			exit ();
		}
	}
}

/**
 * add Servername edits the list of servers
 * to begin with "servername= " so that running the query will be easier.
 *
 * @param array $contentArray        	
 * @return string
 */
function addServername($contentArray) {
	$i = 0;
	$last = count ( $contentArray ) - 1;
	
	for($i; $i < $last; $i ++) {
		$contentArray [$i] = "servername = '" . $contentArray [$i] . "' or ";
	}
	// last element doesn't need 'or'
	$contentArray [$last] = "servername = '" . $contentArray [$last] . "'";
	return $contentArray;
}
/**
 * validServerschecks the database to make sure a
 * given windows servername exists in the CPDB.
 * if any servername does not exist, this method kills the process
 *
 * @param array $contentArray        	
 */
function validServers($contentArray, $implodedArray) {
	$bad = existsServers ( $contentArray, $implodedArray );
	
	if ($bad == NULL) {
		echo "All servers are valid";
	} else {
		for($i = 0; $i < count ( $bad ); $i ++) {
			print "<br>Servername: <strong>" . $bad [$i] . "</strong> is invalid <br>";
		}
		die ( "Server names are invalid<br>" );
	}
}
/**
 * *When the user inputs server names into a text box,
 * *the names have a space.
 * We need to remove it.
 */
function removeSpace($contentArray) {
	$i = 0;
	$last = count ( $contentArray ) - 1;
	for($i; $i < $last; $i ++) {
		if (! is_numeric ( substr ( $contentArray [$i], - 1 ) )) {
			$contentArray [$i] = substr ( $contentArray [$i], 0, - 1 );
		}
	}
	return $contentArray;
}

?>
</div>
</body>
</html>