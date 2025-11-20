<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0//EN"
            "http://www.w3.org/TR/REC-html40/strict.dtd">
<html>
<script language=JavaScript>
    function verify( form ) {
        file = form.elements[ "uploaded" ];
        if (( file.value != null ) && ( file.value != "" )) {
            return confirm("Upload " + file.value + "?");;
    }
        alert( "The file name cannot be empty." );
        return false;
    }
</script>
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
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
session_start();
$password = $_POST['password'];
if (isset($_POST['password'])) {
    $_SESSION['perma_pass'] = $_POST['password'];
}

$db = new mysqli( 'localhost', 'uMoviesAdmin', $password, 'umovies' );
if ($db->connect_errno) {
    echo "<h3>Incorrect Password</h3>";
}
else {
    echo "<h3>Uploading Data File</h3>";
    echo "<form enctype='multipart/form-data' action='adminUpload.php' method='post' onsubmit='verify(this);'>
        <input type='file' id='uploaded' name='uploaded' size='30' />
        <input type='submit' value='Upload' />

    </form>";
}
$db->close();
?>
<p><copyright>Roberto .A. Flores &copy; 2027</copyright></p>
</div>

</body>
</html>
