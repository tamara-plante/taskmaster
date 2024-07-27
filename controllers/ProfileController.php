<?php
class ProfileController extends Controller
{
    private $model;

    public function __construct($f3)
    {
        parent::__construct($f3);
        $this->model = new User();
    }

    public function render()
    {
        // Setup the CSS and pass data to the view
        $this->set("css", ["css/login.css"]);
        $this->setPageTitle("Update Profile");
        $this->set("form", "includes/profile-update.html");
        $this->set("container", "profile-container");
        $this->set("username", isset($_SESSION["username"]) ? $_SESSION["username"] : "user");
        $this->set("avatar", isset($_SESSION["avatar"]) ? $_SESSION["avatar"] : "public/images/avatar.png");        

        // Handle session messages
        $this->set("successMessage", $this->get("SESSION.successMessage") ?? NULL);
        $this->clear("SESSION.successMessage");
        $this->set("deleteSuccessMessage", $this->get("SESSION.deleteSuccessMessage") ?? NULL);
        $this->clear("SESSION.deleteSuccessMessage");

        echo $this->template->render("index.html");
    }

    // Clear session messages
    private function clear($key)
    {
        $this->set($key, NULL);
    }

    public function update()
    {
        // Trim and sanitize input values
        $this->set("POST", [
            "username" => trim($this->get("POST.username")),
            "password" => trim($this->get("POST.password")),
            "password-confirm" => trim($this->get("POST.password-confirm")),
        ]);

        $avatar = null;
        // Check if an avatar file is uploaded and handle the upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $avatar = $this->uploadAvatar($_FILES['avatar']);
        }

        // Validate form input
        if ($this->isFormValid()) {
            // Retrieve sanitized and validated input data
            $username = $this->get("POST.username");
            $password = $this->get("POST.password");
            $userId = $_SESSION["userId"];

            // Update user details in the database
            $updateSuccess = $this->model->updateUser($userId, $username, $password, $avatar);

            if ($updateSuccess) {
                // Update session variables if the database update is successful
                if ($username) {
                    $_SESSION['username'] = $username; 
                }
                if ($password) {
                    $_SESSION['password'] = $password; 
                }
                if ($avatar) {
                    $_SESSION['avatar'] = $avatar;
                }
                // Set a success message and redirect to the profile page
                $this->set("SESSION.successMessage", "User updated successfully.");
                $this->f3->reroute("@profile");
            }

        } else {
            // Set the username back to the view if validation fails
            $this->set("username", $this->get("POST.username"));
        }

        // Render the view again
        $this->render();
    }


    // Handle avatar upload
    private function uploadAvatar($file)
    {
        $targetDir = "public/images/avatars/";
        $targetFile = $targetDir . basename($file["name"]);
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Validate image file
        $check = getimagesize($file["tmp_name"]);
        if ($check === false) {
            return null;
        }

        // Validate file size (limit to 2MB)
        if ($file["size"] > 2000000) {
            return null;
        }

        // Allow specific file formats
        if (!in_array($imageFileType, ["jpg", "png", "jpeg", "gif"])) {
            return null;
        }

        // Handle file existence
        if (file_exists($targetFile)) {
            $targetFile = $targetDir . uniqid() . "." . $imageFileType;
        }

        // Move uploaded file
        if (move_uploaded_file($file["tmp_name"], $targetFile)) {
            return $targetFile;
        } else {
            return null;
        }
    }

    // Delete user account
    public function delete()
    {
        $userId = $_SESSION["userId"];
        $this->model->deleteUser($userId);
        $this->f3->reroute("@logout");
    }

    // Validate form data
    private function isFormValid()
    {
        $errors = [];
        // Retrieve sanitized and validated input data
        $username = $this->get("POST.username");
        $pass = $this->get("POST.password");
        $passConfirm = $this->get("POST.password-confirm");

        // Check if username exists
        if ($username) {
            $existingUser = $this->model->getUserByUsername($username);
            if (!empty($existingUser)) {
                array_push($errors, "Username already exists.");
            }
        } 

        // Validate password confirmation
        if ($pass && $passConfirm == "") {
            array_push($errors, "Please confirm the password.");
        } else if (strcmp($passConfirm, $pass) != 0) {
            array_push($errors, "Password doesn't match.");
        }

        return $this->validateForm($errors);
    }
}
?>
