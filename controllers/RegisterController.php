<?php

class RegisterController extends Controller
{
    private $model;
    private $lists;
    

    public function __construct($f3)
    {
        parent::__construct($f3);
        $this->model = new User();
        $this->lists = new Lists();
    }

    /**
     * GET: Display the register page
     */
    public function render()
    {
        if ($this->isLoggedIn()) {
            $this->f3->reroute("@app");
        }

        $this->set("css", ["css/login.css"]);
        
        $this->setPageTitle("Register");
        $this->set("form", "includes/register.html");
        $this->set("container", "register-container");

        echo $this->template->render("index.html");
    }

    /**
     * POST: Register the user if the form validates.
     */
    public function register()
    {
        // Sanitize form inputs
        $this->set("POST", [
            "username" => trim($this->get("POST.username")),
            "email" => trim($this->get("POST.email")),
            "password" => trim($this->get("POST.password")),
            "password-confirm" => trim($this->get("POST.password-confirm")),
        ]);

        if ($this->isFormValid()) {
            // Try to add user to database
            $user = $this->model->getUserByUsername($this->get("POST.username"));

            // User doesn't exists
            if (!$user) {
                // Create the user
                $this->model->createUser();
                $this->f3->reroute("@home");
            }
            // User already exists
            else {
                $this->set("email", $this->get("POST.email"));
                $this->set("errors", ["This username already exists."]);
            }
        }
        // Form is invalid
        else {
            $this->set("username", $this->get("POST.username"));
            $this->set("email", $this->get("POST.email"));
        }
        // Form is invalid or the user already exists
        $this->render();
    }

    /**
     * Validate the data for the form after a POST method
     * @return bool true if the form is valid
     */
    private function isFormValid()
    {
        $errors = [];

        if ($this->get("POST.username") == ""){
            array_push($errors, "Username is required.");
        }
        if ($this->get("POST.email") == ""){
            array_push($errors, "Email is required.");
        }
        else if (!filter_var($this->get("POST.email"), FILTER_VALIDATE_EMAIL)) {
            array_push($errors, "Valid email address is required.");
        }

        // Password validation
        $pass = $this->get("POST.password");
        $passConfirm = $this->get("POST.password-confirm");

        if ($pass == ""){
            array_push($errors, "Password is required.");
        }
        else if ($passConfirm == "") {
            array_push($errors, "Please confirm the password.");
        }
        // Compare password/confirm to make sure they match.
        else if (strcmp($passConfirm, $pass) != 0) {
            array_push($errors, "Password doesn't match.");
        }

        return $this->validateForm($errors);
    }
}