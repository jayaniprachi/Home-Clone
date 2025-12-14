<?php
include 'includes/config.php';
include 'includes/functions.php';

$page_title = "Login";

if (isLoggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $errors = [];
    
    if (empty($username) || empty($password)) {
        $errors[] = "Please enter username and password";
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['success'] = "Welcome back, " . $user['username'] . "!";
            
            redirect($user['is_admin'] ? 'admin/' : 'index.php');
        } else {
            $errors[] = "Invalid username or password";
        }
    }
}

include 'includes/header.php';
?>

<div class="container" style="max-width: 400px; margin: 50px auto;">
    <div class="card">
        <div class="card-body">
            <h2 class="text-center mb-3">Login</h2>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Username or Email</label>
                    <input type="text" class="form-control" name="username" value="<?php echo $_POST['username'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <div class="text-center mt-3">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
            
            
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>