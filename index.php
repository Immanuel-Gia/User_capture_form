<?php
include 'config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>Swal.fire('Invalid Email', 'Please enter a valid email address.', 'error');</script>";
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png'];

        if (!in_array($fileExt, $allowedExt)) {
            echo "<script>Swal.fire('Invalid File Type', 'Only JPG and PNG files are allowed.', 'error');</script>";
        } else {
            $newFileName = uniqid('IMG-', true) . "." . $fileExt;
            $uploadPath = 'uploads/' . $newFileName;
            if (!is_dir('uploads')) mkdir('uploads', 0755, true);

            if (move_uploaded_file($fileTmp, $uploadPath)) {
                $stmt = $conn->prepare("INSERT INTO users (name, email, image_path) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $email, $uploadPath);
                $stmt->execute();
                $stmt->close();
                echo "<script>Swal.fire('Success!', 'User has been added successfully.', 'success').then(() => window.location.href='index.php');</script>";
            } else {
                echo "<script>Swal.fire('Upload Failed', 'Unable to save image.', 'error');</script>";
            }
        }
    } else {
        echo "<script>Swal.fire('Image Missing', 'Please upload an image.', 'error');</script>";
    }
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $result = $conn->query("SELECT image_path FROM users WHERE id = $id");
    $data = $result->fetch_assoc();
    if ($data && file_exists($data['image_path'])) {
        unlink($data['image_path']);
    }
    $conn->query("DELETE FROM users WHERE id = $id");
    echo "<script>Swal.fire('Deleted!', 'User has been deleted.', 'success').then(() => window.location.href='index.php');</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Management System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        form input, form button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        form button {
            background-color: #28a745;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }
        #preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            display: none;
            border-radius: 50%;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }
        .delete-btn {
            color: red;
            text-decoration: none;
            font-weight: bold;
        }
        .toggle-btn {
            margin-top: 30px;
            background-color: #007bff;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>User Registration</h2>

        <!-- Add Form Always Visible -->
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="name" placeholder="Enter Name" required>
            <input type="email" name="email" placeholder="Enter Email" required>
            <input type="file" name="image" accept=".jpg,.jpeg,.png" required onchange="previewImage(event)">
            <img id="preview" src="#" alt="Preview Image">
            <button type="submit" onclick="return confirmAdd(event)">Add User</button>
        </form>

        <!-- Toggle Table Button -->
        <button class="toggle-btn" onclick="toggleTable()">Show/Hide Users Table</button>

        <!-- Users Table -->
        <div id="userTable">
            <h2>Registered Users</h2>
            <table>
                <tr>
                    <th>ID</th><th>Name</th><th>Email</th><th>Image</th><th>Created At</th><th>Action</th>
                </tr>
                <?php
                $result = $conn->query("SELECT * FROM users ORDER BY id DESC");
                while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><img src="<?= $row['image_path'] ?>" alt="Profile"></td>
                    <td><?= $row['created_at'] ?></td>
                    <td>
                        <a href="javascript:void(0);" class="delete-btn" onclick="
                            Swal.fire({
                                title: 'Are you sure?',
                                text: 'This action cannot be undone!',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'Yes, delete it!'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'index.php?delete=<?= $row['id'] ?>';
                                }
                            });">
                            Delete
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <script>
        function previewImage(event) {
            const preview = document.getElementById('preview');
            const file = event.target.files[0];
            if (file) {
                preview.src = URL.createObjectURL(file);
                preview.style.display = 'block';
            } else {
                preview.src = '#';
                preview.style.display = 'none';
            }
        }

        function confirmAdd(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Add User?',
                text: 'Do you want to submit this user?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, submit!'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.form.submit();
                }
            });
        }

        function toggleTable() {
            const tableDiv = document.getElementById('userTable');
            if (tableDiv.style.display === 'none' || tableDiv.style.display === '') {
                tableDiv.style.display = 'block';
            } else {
                tableDiv.style.display = 'none';
            }
        }

        // Hide the users table on load
        window.onload = function () {
            document.getElementById('userTable').style.display = 'none';
        };
    </script>
</body>
</html>