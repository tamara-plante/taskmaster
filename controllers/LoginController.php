<?php

class LoginController extends Controller
{
    private $model;


    public function __construct($f3)
    {
        parent::__construct($f3);
        $this->model = new User();
    }

    /**
     * GET: Display the login page
     */
    public function render()
    {
        // Logged in
        if ($this->isLoggedIn()) {
            $this->f3->reroute("@app");
        }

        $this->setPageTitle("Login");
        $this->set("css", ["css/login.css"]);
        $this->set("form", "includes/login.html");
        $this->set("container", "login-container");

        echo $this->template->render("index.html");
    }

    /**
     * POST: Authenticate the user
     */
    public function login()
    {
        // Sanitize form inputs
        $this->set("POST", [
            "username" => trim($this->get("POST.username")),
            "password" => trim($this->get("POST.password")),
        ]);

        if ($this->isFormValid()) {
            // Get user
            $user = $this->model->getUserByUsername($this->get("POST.username"));

            // Compare password
            if ($user && password_verify($this->get("POST.password"), $user["password"])) {
                // Success! Cookies for everyone.
                $_SESSION["auth"] = true;
                $_SESSION["userId"] = $user["id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION['avatar'] = $user["avatar"];


                // redirect user
                $this->f3->reroute("@app");
            }
            // User doesn't exists or password didn't match.
            else {
                $this->set("errors", ["Invalid credentials. Please try again."]);
            }
        }
        // Form is not valid or invalid password.
        $this->set("username", $this->get("POST.username"));
        $this->render();
    }

    /**
     * Validate the data for the form after a POST method
     * @return boolean true if the form is valid
     */
    private function isFormValid()
    {
        $errors = [];

        // Validate username
        if ($this->get("POST.username") == ""){
            array_push($errors, "Username is required.");
        }
        // Validate password
        if ($this->get("POST.password") == ""){
            array_push($errors, "Password is required.");
        }

        return $this->validateForm($errors);
    }
}