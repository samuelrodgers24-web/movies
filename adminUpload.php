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
function insertRecord($db, $table, $fields, $stats) {
    // Build column names and values for SQL
    $columns = implode(", ", array_keys($fields));
    $values = implode(", ", array_map(function($v) use ($db) {
        return "'" . $db->real_escape_string($v) . "'";
    }, array_values($fields)));
    // Simple SQL Insert
    $sql = "INSERT INTO $table ($columns) VALUES ($values)";

    if ($db->query($sql)) {
        $stats[$table]['success']++;
    } else {
        $stats[$table]['fail']++;
        echo "SQL Error on $table: " . $db->error . "<br/>";
    }

    return $stats;
}

function insertMovieLine($db, $parts, $stats) {
    if (count($parts) >= 3) {
        $fields = ['name' => $parts[1], 'year' => $parts[2]];
        $stats = insertRecord($db, 'movies', $fields, $stats);
    } else {
        $stats['bad_lines']++;
    }
    return $stats;
}

function insertDirectorLine($db, $parts, $stats) {
    if (count($parts) >= 4) {
        // Update directors
        $stats = insertRecord($db, 'directors', ['name' => $parts[1]], $stats);
        // Also need to update directed_by
        $stats = insertRecord($db, 'directed_by', ['movie' => $parts[2], 'year' => $parts[3], 'director' => $parts[1]], $stats);
    } else {
        $stats['bad_lines']++;
    }
    return $stats;
}

function insertActorLine($db, $parts, $stats) {
    if (count($parts) >= 5) {
        // Check gender
        $gender = strtolower($parts[0]) === 'actor' ? 'Male' : 'Female';
        // Update actors
        $stats = insertRecord($db, 'actors', ['name' => $parts[1], 'gender' => $gender], $stats);
        // Also need to update performed_in
        $stats = insertRecord($db, 'performed_in', ['actor' => $parts[1], 'movie' => $parts[2], 'year' => $parts[3], 'role' => $parts[4]], $stats);
    } else {
        $stats['bad_lines']++;
    }
    return $stats;
}
    
function parseAndInsertFile($db, $filename, $stats) {
    $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $stats['total_lines']++;
        $parts = array_map('trim', explode("\t", $line));
        $type = strtolower($parts[0] ?? '');

        switch ($type) {
            case 'movie':
                $stats = insertMovieLine($db, $parts, $stats);
                break;
            case 'director':
                $stats = insertDirectorLine($db, $parts, $stats);
                break;
            case 'actor':
            case 'actress':
                $stats = insertActorLine($db, $parts, $stats);
                break;
            default:
                $stats['bad_lines']++;
                break;
        }
    }

    return $stats;
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
        
        // Prepare stat tracking
        $stats = [
            'movies' => ['success' => 0, 'fail' => 0],
            'directors' => ['success' => 0, 'fail' => 0],
            'actors' => ['success' => 0, 'fail' => 0],
            'directed_by' => ['success' => 0, 'fail' => 0],
            'performed_in' => ['success' => 0, 'fail' => 0],
            'total_lines' => 0,
            'bad_lines' => 0
        ];
        
        // Do all the server and SQL stuff
        $stats = parseAndInsertFile($db, $filename, $stats);
        
        
        // Display information
        echo "<h4>Upload Statistics:</h4>";
        echo "Total lines processed: {$stats['total_lines']}<br/>";
        echo "Malformed lines: {$stats['bad_lines']}<br/>";
        echo "Movies inserted: {$stats['movies']['success']}, Failed: {$stats['movies']['fail']}<br/>";
        echo "Directors inserted: {$stats['directors']['success']}, Failed: {$stats['directors']['fail']}<br/>";
        echo "Actors inserted: {$stats['actors']['success']}, Failed: {$stats['actors']['fail']}<br/>";
        echo "Directed_by inserted: {$stats['directed_by']['success']}, Failed: {$stats['directed_by']['fail']}<br/>";
        echo "Performed_in inserted: {$stats['performed_in']['success']}, Failed: {$stats['performed_in']['fail']}<br/>";
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
