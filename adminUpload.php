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
<a href="admin.php">Administrator<span> Administrator access. Password required.</span></a>
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
    //$sql = "INSERT INTO $table ($columns) VALUES ($values)";
    // Ignore duplicates
    $sql = "INSERT IGNORE INTO $table ($columns) VALUES ($values)";

    if ($db->query($sql)) {
        // Check affected rows to see if an insert actually happened
        if ($db->affected_rows > 0) {
            $stats[$table]['success']++;
            // Track last inserted record
            $stats[$table]['last_inserted'] = $fields;
        } else {
            // Record was a duplicate
            $stats[$table]['fail']++;
        }
    } else {
        $stats[$table]['fail']++;
        echo "Error inserting into $table: " . $db->error . "<br/>";
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
            'movies' => ['success' => 0, 'fail' => 0, 'last_inserted' => null],
            'directors' => ['success' => 0, 'fail' => 0, 'last_inserted' => null],
            'actors' => ['success' => 0, 'fail' => 0, 'last_inserted' => null],
            'directed_by' => ['success' => 0, 'fail' => 0, 'last_inserted' => null],
            'performed_in' => ['success' => 0, 'fail' => 0, 'last_inserted' => null],
            'total_lines' => 0,
            'malformed_lines' => 0
        ];
        // Do all the server and SQL stuff
        $stats = parseAndInsertFile($db, $filename, $stats);
        // Finish stat compilation
        $total_movies = $stats['movies']['success'] + $stats['movies']['fail'];
        $total_actors = $stats['actors']['success'] + $stats['actors']['fail'];
        $total_directors = $stats['directors']['success'] + $stats['directors']['fail'];
        $total_directed_by = $stats['directed_by']['success'] + $stats['directed_by']['fail'];
        $total_performed_in = $stats['performed_in']['success'] + $stats['performed_in']['fail'];
        // TODO add some kind of handling null value for last inserted
        $last_movie = $stats['movies']['last_inserted']['name'];
        $last_actor = $stats['actors']['last_inserted']['name'];
        $last_director = $stats['directors']['last_inserted']['name'];
        $last_directed_by = $stats['directed_by']['last_inserted']['movie'] . '/' . $stats['directed_by']['last_inserted']['director'];
        $last_performed_in = $stats['performed_in']['last_inserted']['movie'] . '/' .  $stats['performed_in']['last_inserted']['actor'] . '/' . $stats['performed_in']['last_inserted']['role'];
        
        
        // Display information
        echo "
        <ul>
            <li>Added <b>{$stats['movies']['success']}</b> movies out of {$total_movies}  movie records ({$stats['movies']['fail']} failures) [Last added: <i>{$last_movie}</i>]</li>
            <li>Added <b>{$stats['actors']['success']}</b> actors out of {$total_actors}  actor records ({$stats['actors']['fail']} failures) [Last added: <i>{$last_actor}</i>]</li>
            <li>Added <b>{$stats['directors']['success']}</b> directors out of {$total_directors}  movie records ({$stats['directors']['fail']} failures) [Last added: <i>{$last_director}</i>]</li>
            <li>Added <b>{$stats['directed_by']['success']}</b> directions out of {$total_directed_by}  movie/director records ({$stats['directed_by']['fail']} failures) [Last added: <i>{$last_directed_by}</i>]</li>
            <li>Added <b>{$stats['performed_in']['success']}</b> performances out of {$total_performed_in}  actor/movie/role records ({$stats['performed_in']['fail']} failures) [Last added: <i>{$last_performed_in}</i>]</li>
            
        </ul>";
    }
    echo "<form action='adminMenu.php' method='post'>
        <input type='submit' value='Back to Admin Menu' />
      </form>";
    
    // CAN THE FILE BE MOVED TO MY FOLDER
    // not sure what this is for but it was in the slides
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
