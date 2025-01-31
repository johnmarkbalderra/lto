<?php
ob_start(); // Start output buffering

include 'navbar.php';
include '../php/setting.php';
require_once "dbconnect.php";
$user_id = $_SESSION['user_id'];

$success = false;

function fetchOptions($conn, $table, $orderByColumn) {
    $sql = "SELECT * FROM $table ORDER BY $orderByColumn ASC"; 
    $result = $conn->query($sql);
    $options = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $options[] = $row;
        }
    }
    return $options;
}

// Retrieve options in alphabetical order
$bodyTypes = fetchOptions($conn, 'body_types', 'type_name');  
$vehicleBrands = fetchOptions($conn, 'vehicle_brands', 'brand_name');  
$vehicleColors = fetchOptions($conn, 'vehicle_colors', 'color_name');  
$vehicleFuels = fetchOptions($conn, 'vehicle_fuels', 'fuel_name');  

$currentYear = date("Y");
$yearRange = range(1990, $currentYear);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and retrieve POST data
    $body_type = $_POST['body_type'];
    $motor_vehicle_file_number = $_POST['motor_vehicle_file_number'];
    $engine_number = $_POST['engine_number'];
    $chassis_number = $_POST['chassis_number'];
    $plate_number = $_POST['plate_number'];
    $vehicle_brand = $_POST['vehicle_brand'];
    $year_release = $_POST['year_release'];
    $vehicle_color = $_POST['vehicle_color'];
    $vehicle_fuel = $_POST['vehicle_fuel'];

    // Handle file upload
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["vehicle_image"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is an actual image or fake image
    $check = getimagesize($_FILES["vehicle_image"]["tmp_name"]);
    if($check !== false) {
        $uploadOk = 1;
    } else {
        $uploadOk = 0;
    }

    // Check file size
    if ($_FILES["vehicle_image"]["size"] > 500000) {
        $uploadOk = 0;
    }

    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        // Handle the error accordingly
    } else {
        if (move_uploaded_file($_FILES["vehicle_image"]["tmp_name"], $target_file)) {
            // Insert vehicle information into the vehicle table
            $sql = "INSERT INTO vehicle (body_type, motor_vehicle_file_number, engine_number, chassis_number, plate_number, vehicle_brand, year_release, vehicle_color, vehicle_fuel, vehicle_image) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                die('Prepare failed: ' . $conn->error);
            }
            $stmt->bind_param("ssssssisss", $body_type, $motor_vehicle_file_number, $engine_number, $chassis_number, $plate_number, $vehicle_brand, $year_release, $vehicle_color, $vehicle_fuel, $target_file);
            $stmt->execute();

            // Get the last inserted vehicle_id
            $vehicle_id = $conn->insert_id;

            // Insert into the user_vehicles table to link the vehicle with the user
            $sql = "INSERT INTO user_vehicles (user_id, vehicle_id) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                die('Prepare failed: ' . $conn->error);
            }
            $stmt->bind_param("ii", $user_id, $vehicle_id);
            $stmt->execute();

            $stmt->close();
            $conn->close();

            // Set success to true to trigger SweetAlert
            $success = true;
        } else {
            // Handle the error accordingly
        }
    }
}

ob_end_flush(); // End output buffering
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Vehicle</title>
    <link rel="stylesheet" href="styles.css">
    <!-- Bootstrap CSS -->
    <!-- SweetAlert CSS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow p-4" style="width: 600px;">
        <h2 class="text-center mb-4">Register Your Vehicle</h2>
        <form method="POST" action="" enctype="multipart/form-data">
<div class="row mb-4">
    <div class="col-md-6">
        <label for="body_type" class="form-label" style="font-weight: 500; color: #333;">Body Type</label>
        <select class="form-select" id="body_type" name="body_type" required style="border-radius: 15px; padding: 12px 16px; background-color: #f4f4f4; font-size: 16px; color: #333; border: 1px solid #ccc; transition: background-color 0.3s ease;">
            <option value="" disabled selected>Select Body Type</option>
            <?php foreach ($bodyTypes as $type): ?>
                <option value="<?= htmlspecialchars($type['type_name']) ?>"><?= htmlspecialchars($type['type_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label for="vehicle_brand" class="form-label" style="font-weight: 500; color: #333;">Vehicle Brand</label>
        <select class="form-select" id="vehicle_brand" name="vehicle_brand" required style="border-radius: 15px; padding: 12px 16px; background-color: #f4f4f4; font-size: 16px; color: #333; border: 1px solid #ccc; transition: background-color 0.3s ease;">
            <option value="" disabled selected>Select Vehicle Brand</option>
            <?php foreach ($vehicleBrands as $brand): ?>
                <option value="<?= htmlspecialchars($brand['brand_name']) ?>"><?= htmlspecialchars($brand['brand_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- MV File No. & Engine Number -->
<div class="row mb-4">
    <div class="col-md-6">
        <label for="motor_vehicle_file_number" class="form-label" style="font-weight: 500; color: #333;">MV File No.</label>
        <input type="text" class="form-control" id="motor_vehicle_file_number" name="motor_vehicle_file_number" required style="border-radius: 15px; padding: 12px 16px; background-color: #f4f4f4; font-size: 16px; color: #333; border: 1px solid #ccc;">
    </div>
    <div class="col-md-6">
        <label for="engine_number" class="form-label" style="font-weight: 500; color: #333;">Engine Number</label>
        <input type="text" class="form-control" id="engine_number" name="engine_number" required style="border-radius: 15px; padding: 12px 16px; background-color: #f4f4f4; font-size: 16px; color: #333; border: 1px solid #ccc;">
    </div>
</div>

<!-- Chassis Number & Plate Number -->
<div class="row mb-4">
    <div class="col-md-6">
        <label for="chassis_number" class="form-label" style="font-weight: 500; color: #333;">Chassis Number</label>
        <input type="text" class="form-control" id="chassis_number" name="chassis_number" required style="border-radius: 15px; padding: 12px 16px; background-color: #f4f4f4; font-size: 16px; color: #333; border: 1px solid #ccc;">
    </div>
    <div class="col-md-6">
        <label for="plate_number" class="form-label" style="font-weight: 500; color: #333;">Plate Number</label>
        <input type="text" class="form-control" id="plate_number" name="plate_number" required style="border-radius: 15px; padding: 12px 16px; background-color: #f4f4f4; font-size: 16px; color: #333; border: 1px solid #ccc;">
    </div>
</div>

<!-- Year of Release & Vehicle Color -->
<div class="row mb-4">
    <div class="col-md-6">
        <label for="year_release" class="form-label" style="font-weight: 500; color: #333;">Year of Release</label>
        <select class="form-select" id="year_release" name="year_release" required style="border-radius: 15px; padding: 12px 16px; background-color: #f4f4f4; font-size: 16px; color: #333; border: 1px solid #ccc;">
            <option value="" disabled selected>Select Year</option>
            <?php foreach ($yearRange as $year): ?>
                <option value="<?= $year ?>"><?= $year ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label for="vehicle_color" class="form-label" style="font-weight: 500; color: #333;">Vehicle Color</label>
        <select class="form-select" id="vehicle_color" name="vehicle_color" required style="border-radius: 15px; padding: 12px 16px; background-color: #f4f4f4; font-size: 16px; color: #333; border: 1px solid #ccc;">
            <option value="" disabled selected>Select Color</option>
            <?php foreach ($vehicleColors as $color): ?>
                <option value="<?= htmlspecialchars($color['color_name']) ?>"><?= htmlspecialchars($color['color_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- Fuel Type & Vehicle Image -->
<div class="row mb-4">
    <div class="col-md-6">
        <label for="vehicle_fuel" class="form-label" style="font-weight: 500; color: #333;">Fuel Type</label>
        <select class="form-select" id="vehicle_fuel" name="vehicle_fuel" required style="border-radius: 15px; padding: 12px 16px; background-color: #f4f4f4; font-size: 16px; color: #333; border: 1px solid #ccc;">
            <option value="" disabled selected>Select Fuel Type</option>
            <?php foreach ($vehicleFuels as $fuel): ?>
                <option value="<?= htmlspecialchars($fuel['fuel_name']) ?>"><?= htmlspecialchars($fuel['fuel_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-6">
        <label for="vehicle_image" class="form-label" style="font-weight: 500; color: #333;">Upload OR/CR</label>
        <input type="file" class="form-control" id="vehicle_image" name="vehicle_image" accept="image/*" required style="border-radius: 15px; padding: 12px 16px; background-color: #f4f4f4; font-size: 16px; color: #333; border: 1px solid #ccc;">
    </div>
</div>

<!-- Submit Button -->
<div class="text-center mt-5">
    <button type="submit" class="btn btn-primary btn-lg w-100" style="border-radius: 15px; background-color: #007aff; border: none; padding: 16px; font-size: 18px; transition: background-color 0.3s ease-in-out;">
        Submit
    </button>
</div>
</form>
    </div>
</div>

<!-- SweetAlert Success Message -->
<?php if ($success): ?>
<script>
    Swal.fire({
        title: 'Success!',
        text: 'Congratulations on successfully registering your vehicle!',
        icon: 'success',
        confirmButtonText: 'OK'
    }).then(function() {
        window.location.href = 'vehicle.php';
    });
</script>
<?php endif; ?>

<!-- Bootstrap and SweetAlert scripts -->
</body>
</html>
