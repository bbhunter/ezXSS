<?php

class Install extends Controller
{
    /**
     * Renders the install index and returns the content.
     *
     * @return string
     */
    public function index()
    {
        $this->view->setTitle('Install');
        $this->view->renderTemplate('system/install');

        // Make sure the platform is not already installed
        try {
            $this->model('Setting')->get('timezone');
            redirect('dashboard/index');
        } catch (Exception $e) {}

        if (isPOST()) {
            try {
                $this->validateCsrfToken();
                $username = _POST('username');
                $password = _POST('password');

                // Validate user data
                if (preg_match('/[^A-Za-z0-9]/', $username)) {
                    throw new Exception('Invalid characters in the username. Use a-Z0-9');
                }

                if (strlen($username) < 2 || strlen($username) > 25) {
                    throw new Exception('Username needs to be between 2-25 long');
                }

                if (
                    strlen($password) < 8 || !preg_match('@[A-Z]@', $password) ||
                    !preg_match('@[0-9]@', $password) || !preg_match('@[^\w]@', $password)
                ) {
                    throw new Exception('Password not strong enough');
                }

                // Create the database tables and rows
                $sql = file_get_contents(__DIR__ . '/../sql/4.3.sql');
                $database = Database::openConnection();
                $database->exec($sql);
                $database->exec('ALTER DATABASE `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;');

                $this->model('Setting')->set('timezone', date_default_timezone_get());

                // Create and login user
                $this->model('User')->create($username, $password, 7);
                $user = $this->model('User')->login($username, $password);
                $this->session->create($user);
                redirect('dashboard/index');
            } catch (Exception $e) {
                $this->view->renderMessage($e->getMessage());
            }
        }

        return $this->showContent();
    }
}