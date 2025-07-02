<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $expectedApiKey = getenv('API_KEY');
    if (!$expectedApiKey) {
    // Log an error or return a server error, as this indicates a misconfiguration
        error_log("Error: API_KEY environment variable not set!");
        header('HTTP/1.1 500 Internal Server Error');
        echo "500 Internal Error";
        exit;
    }
    $apiKeyHeader = '';
    if (isset($_SERVER['HTTP_X_API_KEY'])) {
        $apiKeyHeader = $_SERVER['HTTP_X_API_KEY'];
    } elseif (function_exists('apache_request_headers')) {
        // For some server configurations, apache_request_headers might be needed
        $headers = apache_request_headers();
        if (isset($headers['X-API-Key'])) {
            $apiKeyHeader = $headers['X-API-Key'];
        }
    }

    // Validate the API key
    if (!hash_equals($expectedApiKey, $apiKeyHeader)) {
        header('HTTP/1.1 401 Unauthorized');
        echo "401 Unauthorized: API Key Required";
        exit; // Stop execution
    }

    $device = $_POST['device'];
    $temperature = $_POST['temperature'];
    $humidity = $_POST['humidity'];
    $co2 = $_POST['co2'];
    $heart_rate = $_POST['heart_rate'];
    
    // Set the timezone
    date_default_timezone_set('Asia/Singapore');
    
    // Get the current datetime
    $datetime = date("Y-m-d H:i:s");
    
    // File to store data in text format
    $counter_file = 'message_counter.txt';
    $globalMessageNumber = 0;

    // Read the current global message number
    if (file_exists($counter_file)) {
        $globalMessageNumber = (int)file_get_contents($counter_file);
    }
    // Increment for the new message
    $globalMessageNumber++;
    // Write back the new global message number
    file_put_contents($counter_file, $globalMessageNumber);

    // --- Log Rotation Logic (for display files) ---
    $max_display_lines = 1000; // Keep only the last 1000 lines for browser display
    $log_file_txt = 'data.txt'; // For internal logging/archive
    $log_file_csv = 'data.csv'; // For browser display

    // Append new data to the full log files (these can still grow indefinitely if not managed by logrotate)
    // You might want to consider deleting these or using external log rotation tools for these.
    $full_log_txt = 'full_data.txt'; // Full, unrotated log
    $full_log_csv = 'full_data.csv'; // Full, unrotated log

    $new_line_txt = "$globalMessageNumber, $datetime, Device: $device, Temperature: $temperature, Humidity: $humidity, CO2: $co2, Heart Rate: $heart_rate";
    $new_line_csv = "$globalMessageNumber, $datetime, $device, $temperature, $humidity, $co2, $heart_rate";

    // Append to the full, unrotated logs
    file_put_contents($full_log_txt, $new_line_txt . "\n", FILE_APPEND);
    file_put_contents($full_log_csv, $new_line_csv . "\n", FILE_APPEND);

    // Read existing lines for the *display* CSV (data.csv)
    $current_display_lines = file($log_file_csv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    // Append the new line to the display lines
    array_push($current_display_lines, $new_line_csv);

    // Truncate if exceeding max_display_lines
    if (count($current_display_lines) > $max_display_lines) {
        $current_display_lines = array_slice($current_display_lines, -$max_display_lines);
    }

    // Write back to the display CSV file
    file_put_contents($log_file_csv, implode("\n", $current_display_lines) . "\n");
    
    // Also truncate data.txt if you want it to mirror data.csv for display purposes
    $current_display_lines_txt = file($log_file_txt, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    array_push($current_display_lines_txt, $new_line_txt);
    if (count($current_display_lines_txt) > $max_display_lines) {
        $current_display_lines_txt = array_slice($current_display_lines_txt, -$max_display_lines);
    }
    file_put_contents($log_file_txt, implode("\n", $current_display_lines_txt) . "\n");

    
    // Return a confirmation message
    echo "Data received: Message Number: $messageNumber, Datetime: $datetime, Device: $device, Temperature: $temperature, Humidity: $humidity, CO2: $co2, Heart Rate: $heart_rate";
    exit;
} else {
    header('HTTP/1.1 405 Method Not Allowed');
    echo "405 Method Not Allowed";
    exit;
}
?>
