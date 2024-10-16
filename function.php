<?php

session_start();

function logoutSession()
{
    unset($_SESSION["loggedIn"]);
    unset($_SESSION["loggedInUser"]);
}


// Input field validation
function validateInputData($inputData)
{
    global $conn;
    $ValidatedData = mysqli_real_escape_string($conn, $inputData);
    return trim($ValidatedData);
}

// Redirect with status and optional message
function redirect($url, $status, $customMessage = null)
{
    $messages = [
        'success' => 'Operation was successful!',
        'error' => 'Operation failed. Please try again.'
        // Add more status types and default messages as needed
    ];

    $message = ($customMessage !== null) ? $customMessage : $messages[$status];

    $_SESSION['status'] = [
        'type' => $status,
        'message' => $message
    ];

    header('Location: ' . $url);
    exit(0);
}

// Display alert message
function alertMessage()
{
    if (isset($_SESSION['status'])) {
        $status = $_SESSION['status']['type'];
        $message = $_SESSION['status']['message'];

        $alertClass = ($status === 'success') ? 'alert-success' : 'alert-danger';

        echo '<div class="alert ' . $alertClass . ' text-center alert-dismissible fade show alert-sm" role="alert">
            <p class="mb-0">' . $message . '</p>    
        </div>';

        unset($_SESSION['status']);
    }
}


// CRUD Operations

// CREATE (INSERT) operation
function insertRecord($tableName, $data)
{
    global $conn;

    $table = validateInputData($tableName);

    $columns = array_keys($data);
    $values = array_values($data);

    $finalColumn = implode(',', $columns);
    $finalValues = "'" . implode("', '", $values) . "'";

    $query = "INSERT INTO $table ($finalColumn) VALUES ($finalValues)";
    $result = mysqli_query($conn, $query);

    return $result;
}

// UPDATE operation
function updateRecord($tableName, $id, $data)
{
    global $conn;

    $table = validateInputData($tableName);
    $id = validateInputData($id);

    $updateDataString = "";

    foreach ($data as $column => $value) {
        $updateDataString .= $column . '=' . "'$value',";
    }

    $finalUpdateDate = substr(trim($updateDataString), 0, -1);

    $query = "UPDATE $table SET $finalUpdateDate WHERE id = '$id'";
    $result = mysqli_query($conn, $query);

    return $result;
}

// DISPLAY operation
// Display all records
function getAllRecords($tableName, $status = NULL)
{
    global $conn;

    $table = validateInputData($tableName);
    $status = validateInputData($status);

    if ($status == 'status') {
        $query = "SELECT * FROM $table WHERE status='0'";
    } else {
        $query = "SELECT * FROM $table";
    }

    return mysqli_query($conn, $query);
}

// Display by specific (id) record
function getRecordByID($tableName, $id)
{
    global $conn;

    $table = validateInputData($tableName);
    $id = validateInputData($id);

    $query = "SELECT * FROM $table WHERE id='$id'";
    $result = mysqli_query($conn, $query);

    if ($result) {
        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);

            $response = [
                'status' => 200,
                'data' => $row,
                'message' => 'Record Found',
            ];
        } else {
            $response = [
                'status' => 404,
                'message' => 'No Data Found',
            ];
        }
    } else {
        $response = [
            'status' => 500,
            'message' => 'Something went wrong'
        ];
    }

    return $response;
}

// DELETE operation
function deleteRecord($tableName, $id)
{
    global $conn;

    $table = validateInputData($tableName);
    $id = validateInputData($id);

    $query = "DELETE FROM $table WHERE id = '$id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    return $result;
}

// Check if id parameter exists or not
function getIdParam($type)
{
    if (isset($type)) {
        if ($_GET[$type] != '') {
            return $_GET[$type];
        } else {
            echo '<h5>No id Found!.</h5>';
        }
    } else {
        echo '<h5>No id given!.</h5>';
    }
}

// Json response
function jsonResponse($status, $status_type, $message)
{
    $response = [
        'status' => $status,
        'status_type' => $status_type,
        'message' => $message,
    ];
    echo json_encode($response);
    return;
}

// Get record count
function getRecordCount($tableName)
{
    global $conn;

    $table = validateInputData($tableName);

    $query = "SELECT * FROM $table";
    $query_num = mysqli_query($conn, $query);
    if ($query_num) {
        $totalCount = mysqli_num_rows($query_num);
        return $totalCount;
    } else {
        return "Something went wrong";
    }
}

function getLimitedRecords($table, $start, $limit)
{
    global $conn;
    $query = "SELECT * FROM $table LIMIT $start, $limit";
    return mysqli_query($conn, $query);
}

function getTotalRecordsCount($table)
{
    global $conn;
    $query = "SELECT COUNT(*) AS total FROM $table";
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    return $data['total'];
}


function getRecordNameById($tableName, $id)
{
    global $conn;

    // Validate the input parameters
    $table = validateInputData($tableName);
    $id = validateInputData($id);

    // Prepare the query to fetch the name based on the provided ID
    $query = "SELECT name FROM $table WHERE id = '$id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    // Check if the query was successful and if a record was found
    if ($result && mysqli_num_rows($result) == 1) {
        $record = mysqli_fetch_assoc($result);
        return $record['name']; // Return the 'name' of the record
    }

    // Return null or an appropriate message if the record was not found
    return null;
}


// FILE UPLOAD/DELETE HANDLING
// ====================================

// Handle File Upload
function handleFileUpload($inputName, $destinationPath, $existingFile = null)
{
    if ($_FILES[$inputName]['size'] > 0) {
        $path = "../$destinationPath";
        $image_ext = pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION);
        $filename = time() . '.' . $image_ext;

        move_uploaded_file($_FILES[$inputName]['tmp_name'], $path . "/" . $filename);
        $finalImagePath = "$destinationPath/$filename";

        // Delete existing file if provided
        if ($existingFile && file_exists("../$existingFile")) {
            unlink("../$existingFile");
        }

        return $finalImagePath;
    } else {
        return $existingFile ? $existingFile : '';
    }
}

// Handle File Deletion
function deleteFile($filePath)
{
    if (file_exists("../$filePath")) {
        unlink("../$filePath");
    }
}
?>
