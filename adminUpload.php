<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0//EN"
            "http://www.w3.org/TR/REC-html40/strict.dtd">
<html>
<head>
<title>uMovies</title>
<style type="text/css">
@import url(uMovies.css);
</style>
</head>
<body>

<div id="links">
<a href="./">Home<span> Access the database of movies, actors and directors. Free to all!</span></a>
<a href="admin.html">Administrator<span> Administrator access. Password required.</span></a>
</div>


<div id="content">
<h1>uMovies&trade;</h1>
<p>
Welcome to <em>uMovies</em>, your destination for information on <a href="movies.php" title="access movies information">movies</a>, <a href="actors.php" title="access actors information">actors</a> and <a href="directors.php" title="access directors information">directors</a>.
</p>
<p>
</p>

<h2>Administrator Access</h2>
<?php
// SESSION INFO
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
session_start();
if (isset($_SESSION['perma_pass'])) {
    $password = $_SESSION['perma_pass'];
}
if (isset($_POST['uploaded'])) {
    $_SESSION['perma_path'] = $_POST['uploaded'];
}
if (isset($_SESSION['perma_path'])) {
    $filepath = $_SESSION['perma_path'];
}
    
// UPLOAD FUNCTIONS
function create_table( $data, $b=1, $p=4, $s=4 ) {
    echo "<table border=$b cellpadding=$p cellspacing=$s>";
    foreach ($data as $item) {
        echo "<tr><td align='center'> $item </td></tr>";
    }
    echo "</table><p>";
}
    
// INITIATE CONNECTION   
$db = new mysqli( 'localhost', 'uMoviesAdmin', $password, 'umovies' );
if ($db->connect_errno) {
    echo "<h3>Connection Error</h3>";
}
else {
    echo "<h3>Uploading Data File</h3>";
    $filename = $_FILES['uploaded'][ 'tmp_name' ];
    $okay = true;
    // WAS THE FILE UPLOADED?
    if ($_FILES[ 'uploaded' ][ 'error' ] > 0) {
        echo 'A problem was detected:<br/>';
        switch ($_FILES[ 'uploaded' ][ 'error' ]) {
            case 1: echo '* File exceeded maximum size allowed by server.<br/>'; break;
            case 2: echo '* File exceeded maximum size allowed by application.<br/>'; break;
            case 3: echo '* File could not be fully uploaded.<br/>'; break;
            case 4: echo '* File was not uploaded.<br/>';
        }
        $okay = false;
    }
    
    // IS THE MIME TYPE CORRECT?
    if ($okay && $_FILES[ 'uploaded' ][ 'type' ] != 'text/plain') {
        echo 'A problem was detected:<br/>';
        echo '* File is not a text file.<br/>';
        $okay = false;
    }
    
    // READ & DISPLAY FILE CONTENTS
    if ($okay){
        echo 'File uploaded successfully';
        $file = fopen ( $filename, 'r' );
        $fileContents = nl2br ( fread ( $file, filesize( $filename )));
        fclose( $file );
        echo "<hr/> $fileContents <hr/>";
    }
    
    
    // CAN THE FILE BE MOVED TO MY FOLDER
    $filename = 'file.txt';
    if ($okay) {
        if (is_uploaded_file($_FILES[ 'uploaded' ][ 'tmp_name' ])) {
            if (!move_uploaded_file($_FILES[ 'uploaded' ][ 'tmp_name' ], $filename)) {
                echo 'A problem was detected:</br>';
                echo '* Could not copy file to final destination.<br/>';
                $okay = false;
            }
        }
        else {
            echo 'A problem was detected:<br/>';
            echo '* File to copy is not an uploaded file.<br/>';
            $okay = false;
        }
    }
    
}
    

$db->close();
?>
<p><copyright>Roberto .A. Flores &copy; 2027</copyright></p>
</div>

</body>
</html>
