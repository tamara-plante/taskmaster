<?php

// Handle non-database pages
class ContactUsController extends Controller 
{
    public function contactUs($f3) 
    {
        // Setup the css
        $this->set("css", ["css/contact-us.css"]);
        $this->setPageTitle("Contact Us");
        // Determine which header to use based on login status
        if ($this->isLoggedIn()) {
            $headerFile = "includes/header.html"; // Path for logged in users
        } else {
            $headerFile = "includes/header-guest.html"; // Path for guests
        }
        $this->set("header", $headerFile);
        $this->set("username", isset($_SESSION["username"]) ? $_SESSION["username"] : "user");
        $this->set("avatar", isset($_SESSION["avatar"]) ? $_SESSION["avatar"] : "public/images/avatar.png");        
        echo $this->template->render("contact-us.html");
    }
}
