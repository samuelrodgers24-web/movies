<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0//EN"
            "http://www.w3.org/TR/REC-html40/strict.dtd">
<html>
<head>
<title>uMovies :: Movies</title>
<style type="text/css">
@import url(uMovies.css);
</style>
</head>
<body>

<div id="links">
<a href="./">Home<span> Access the database of movies, actors and directors. Free to all!</span></a>
<a href="admin.php">Administrator<span> Administrator access. Password required.</span></a>
</div>


<div id="content">
<h1>uMovies&trade;</h1>
<p>
Welcome to <em>uMovies</em>, your destination for information on <a href="movies.php" title="access movies information">movies</a>, <a href="actors.php" title="access actors information">actors</a> and <a href="directors.php" title="access directors information">directors</a>.
</p>

<h2>Administrator Access</h2>

<p>
<?php
    
// SESSION INFO
mysqli_report(MYSQLI_REPORT_ALL ^ MYSQLI_REPORT_STRICT);
session_start();
if (isset($_SESSION['perma_pass'])) {
    $password = $_SESSION['perma_pass'];
}
    
$db = new mysqli( 'localhost', 'uMoviesAdmin', $password, 'umovies' );
if ($db->connect_errno) {
    echo "<h3>Connection Error</h3>";
}
else {
    echo "<h3>Deleting Information</h3>";
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_all'])) {
        
        @$db->query("DELETE FROM performed_in");
        @$db->query("DELETE FROM directed_by");
        @$db->query("DELETE FROM actors");
        @$db->query("DELETE FROM directors");
        @$db->query("DELETE FROM movies");
        echo "<h3>All data deleted!</h3>";
    } 
    else {
        echo "<h3>Problem deleting all data!</h3>";
    }
    
    echo "<form action='adminMenu.php' method='post'>
        <input type='submit' value='Back to Admin Menu' />
      </form>";
}

?>
</p>

<p><copyright>Roberto .A. Flores &copy; 2027</copyright></p>
</div>

</body>
</html>
