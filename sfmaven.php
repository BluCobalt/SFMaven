<?php
/**
 * Singe File Maven
 * A maven repository in a single file
 *
 * Edit only the specified lines, they are the user configurable settings.
 */

/**
 * The permissions to use when creating files/directories. This is ignored on windows.
 * default: 0775
 */
$mode = 0775;

/**
 * Amount of data to read at a time
 * default: 1024
 */
$chunk = 1024;

/**
 * Enable debug interface (work in progress)
 * default: false
 */
$debugEnabled = false;

/**
 * Debug interface requires auth
 * default: true
 */
$debugAuthRequired = true;

/**
 * Whether or not authentication is required for pushing to the repository
 * default: true
 */
$authRequired = true;

/**
 * An array of allowed tokens that get to push to the repository
 */
$auth = array(
    "first-token-here",
    "second-token-here"
);

/**
 * End of user configurable settings; do not edit anything below this comment unless you know what you are doing.
 */

/**
 * Ensure that the request method is GET or PUT, and throw a 405 if it isn't.
 */
function checkRequestMethod(): void
{
    $method = $_SERVER["REQUEST_METHOD"];
    if (!($method == "GET" || $method == "PUT"))
    {
        http_response_code(405);
        echo "Method " . $method . " is disallowed.";
        exit();
    }
}

/**
 * Make sure that the request is properly authenticated
 */
function checkAuth(): void
{
    global $authRequired;
    if ($authRequired)
    {
        if (!isset($_SERVER['PHP_AUTH_USER']))
        {
            header('WWW-Authenticate: Basic realm="authentication"');
            http_response_code(401);
            echo "Credentials are required.";
            exit;
        } else
        {
            global $auth;
            $authenticated = in_array($_SERVER["PHP_AUTH_PW"], $auth, true);
        }
        if (!$authenticated)
        {
            http_response_code(403);
            echo "Authentication FAILED for token ".$_SERVER["PHP_AUTH_PW"];
            exit;
        }
    }
}

checkRequestMethod();

if ($_SERVER["REQUEST_METHOD"] == "PUT")
{
    checkAuth();
    $path = $_SERVER["REQUEST_URI"];
    if (!file_exists(dirname($path)))
    {
        $oldmask = umask(0);
        mkdir(dirname($path), $mode, true);
        umask($oldmask);
    }
    $infile = fopen("php://input", "r");
    $outfile = fopen($path, "w");
    stream_copy_to_stream($infile, $outfile, $chunk);
    chmod($path, $mode);
    fclose($infile);
    fclose($outfile);
} elseif ($_SERVER["REQUEST_METHOD"] == "GET" && $debugEnabled)
{
    checkAuth();
    // some sort of diagnostics page would go here, with things like the php version and statistics
}
