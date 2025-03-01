<?php
// Database connection
$db = pg_connect("host=localhost dbname=bug_tracking_system user=postgres password=root");

if (!$db) {
    die("Error in connection: " . pg_last_error());
}

session_start();

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Functions
function loginUser($db, $username, $password) {
    $query = "SELECT * FROM users WHERE username = $1 AND password = $2";
    $result = pg_query_params($db, $query, array($username, $password));

    if (pg_num_rows($result) > 0) {
        $user = pg_fetch_assoc($result);
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_id'] = $user['id'];
        header("Location: index.php");
        exit();
    } else {
        return "Invalid credentials!";
    }
}

function registerUser($db, $username, $password, $role, $email) {
    $query = "INSERT INTO users (username, password, role, email) VALUES ($1, $2, $3, $4)";
    pg_query_params($db, $query, array($username, $password, $role, $email));
    header("Location: index.php?action=user_management"); // Redirect to User Management
    exit();
}

function deleteUser($db, $id) {
    $query = "DELETE FROM users WHERE id = $1";
    pg_query_params($db, $query, array($id));
    header("Location: index.php?action=user_management"); // Redirect to User Management
    exit();
}

function editUser($db, $id, $username, $password, $role, $email) {
    $query = "UPDATE users SET username = $1, password = $2, role = $3, email = $4 WHERE id = $5";
    pg_query_params($db, $query, array($username, $password, $role, $email, $id));
    header("Location: index.php?action=user_management"); // Redirect to User Management
    exit();
}

function addBug($db, $title, $description, $status, $priority, $category_id, $assigned_to) {
    $query = "INSERT INTO bugs (title, description, status, priority, category_id, assigned_to) VALUES ($1, $2, $3, $4, $5, $6)";
    pg_query_params($db, $query, array($title, $description, $status, $priority, $category_id, $assigned_to));
    header("Location: index.php?action=view_bugs"); // Redirect to View Bugs screen
    exit();
}

function editBug($db, $id, $title, $description, $status, $priority, $category_id, $assigned_to) {
    $query = "UPDATE bugs SET title = $1, description = $2, status = $3, priority = $4, category_id = $5, assigned_to = $6 WHERE id = $7";
    pg_query_params($db, $query, array($title, $description, $status, $priority, $category_id, $assigned_to, $id));

    // Record status change in bug history
    $history_query = "INSERT INTO bug_history (bug_id, change_type, old_value, new_value, changed_by) 
                      VALUES ($1, 'Status Change', (SELECT status FROM bugs WHERE id = $1), $2, $3)";
    pg_query_params($db, $history_query, array($id, $status, $_SESSION['user_id']));

    header("Location: index.php?action=view_bugs"); // Redirect to View Bugs screen
    exit();
}

function deleteBug($db, $id) {
    $query = "DELETE FROM bugs WHERE id = $1";
    pg_query_params($db, $query, array($id));
    header("Location: index.php?action=view_bugs"); // Redirect to View Bugs screen
    exit();
}

function addComment($db, $bug_id, $user_id, $comment) {
    $query = "INSERT INTO bug_comments (bug_id, user_id, comment) VALUES ($1, $2, $3)";
    pg_query_params($db, $query, array($bug_id, $user_id, $comment));
    header("Location: index.php?bug_id=$bug_id");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $error = loginUser($db, $_POST['username'], $_POST['password']);
    } elseif (isset($_POST['register'])) {
        registerUser($db, $_POST['username'], $_POST['password'], $_POST['role'], $_POST['email']);
    } elseif (isset($_POST['add_bug'])) {
        addBug($db, $_POST['title'], $_POST['description'], $_POST['status'], $_POST['priority'], $_POST['category_id'], $_POST['assigned_to']);
    } elseif (isset($_POST['edit_bug'])) {
        editBug($db, $_POST['id'], $_POST['title'], $_POST['description'], $_POST['status'], $_POST['priority'], $_POST['category_id'], $_POST['assigned_to']);
    } elseif (isset($_POST['add_comment'])) {
        addComment($db, $_POST['bug_id'], $_SESSION['user_id'], $_POST['comment']);
    } elseif (isset($_POST['edit_user'])) {
        editUser($db, $_POST['id'], $_POST['username'], $_POST['password'], $_POST['role'], $_POST['email']);
    }
}

// Handle Delete Bug
if (isset($_GET['delete_bug'])) {
    $bug_id = $_GET['delete_bug'];
    deleteBug($db, $bug_id);
}

// Handle Delete User
if (isset($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];
    deleteUser($db, $user_id);
}

// Fetch Bugs (Sorted by Created Date)
$query = "SELECT * FROM bugs ORDER BY created_at DESC";
$bugs_result = pg_query($db, $query);

// Fetch Bug Categories for the dropdown
$category_query = "SELECT * FROM bug_categories";
$categories_result = pg_query($db, $category_query);

// Fetch Users for the assigned_to dropdown
$users_query = "SELECT * FROM users";
$users_result = pg_query($db, $users_query);

// Fetch Dashboard Data (Only for Dashboard Screen)
if (!isset($_GET['action']) || $_GET['action'] == 'dashboard') {
    $total_bugs_query = "SELECT COUNT(*) as total FROM bugs";
    $in_progress_bugs_query = "SELECT COUNT(*) as in_progress FROM bugs WHERE status = 'In Progress'";
    $closed_bugs_query = "SELECT COUNT(*) as closed FROM bugs WHERE status = 'Closed'";
    $open_bugs_query = "SELECT COUNT(*) as open FROM bugs WHERE status = 'Open'";

    $total_bugs = pg_fetch_assoc(pg_query($db, $total_bugs_query));
    $in_progress_bugs = pg_fetch_assoc(pg_query($db, $in_progress_bugs_query));
    $closed_bugs = pg_fetch_assoc(pg_query($db, $closed_bugs_query));
    $open_bugs = pg_fetch_assoc(pg_query($db, $open_bugs_query));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bug Tracking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        // Confirmation Popup for Actions
        function confirmAction(action) {
            return confirm(`Are you sure you want to ${action}?`);
        }
    </script>
</head>
<body class="bg-light">
    <div class="container">
        <?php if (!isset($_SESSION['username'])): ?>
        <!-- Login Form -->
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h1 class="text-center">Login</h1>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" placeholder="Username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Password" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                        </form>
                        <p class="mt-3 text-center"><a href="?action=register">Don't have an account? Register here</a></p>
                        <?php if (isset($error)) echo "<p class='text-danger text-center'>$error</p>"; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registration Form -->
        <?php elseif (isset($_GET['action']) && $_GET['action'] == 'register'): ?>
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h1 class="text-center">Register</h1>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" placeholder="Username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Password" required>
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select name="role" class="form-control" required>
                                    <option value="admin">Admin</option>
                                    <option value="user">User</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="Email" required>
                            </div>
                            <button type="submit" name="register" class="btn btn-success w-100">Register</button>
                        </form>
                        <p class="mt-3 text-center"><a href="?">Already have an account? Login here</a></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard with Navbar -->
        <?php else: ?>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">Bug Tracking System</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="?">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="?action=view_bugs">View Reported Bugs</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="?action=add_bug">Add New Bug</a>
                        </li>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="?action=user_management">User Management</a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="?logout=true">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Dashboard Section -->
        <?php if (!isset($_GET['action']) || $_GET['action'] == 'dashboard'): ?>
        <div class="mt-4">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>

            <!-- Dashboard Stats -->
            <h2>Dashboard</h2>
            <div class="row">
                <div class="col-md-3">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-header">Total Bugs</div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $total_bugs['total']; ?></h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning mb-3">
                        <div class="card-header">In Progress</div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $in_progress_bugs['in_progress']; ?></h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-header">Closed</div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $closed_bugs['closed']; ?></h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-header">Open</div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $open_bugs['open']; ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- View Reported Bugs -->
        <?php if (isset($_GET['action']) && $_GET['action'] == 'view_bugs'): ?>
        <h2>Reported Bugs</h2>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Category</th>
                    <th>Assigned To</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $counter = 1; while ($bug = pg_fetch_assoc($bugs_result)): ?>
                <tr>
                    <td><?php echo $counter++; ?></td>
                    <td><?php echo htmlspecialchars($bug['title']); ?></td>
                    <td><?php echo htmlspecialchars($bug['description']); ?></td>
                    <td><?php echo htmlspecialchars($bug['status']); ?></td>
                    <td><?php echo htmlspecialchars($bug['priority']); ?></td>
                    <td><?php 
                        $category_query = "SELECT name FROM bug_categories WHERE id = " . $bug['category_id'];
                        $category_result = pg_query($db, $category_query);
                        $category = pg_fetch_assoc($category_result);
                        echo htmlspecialchars($category['name']);
                    ?></td>
                    <td><?php 
                        $assigned_query = "SELECT username FROM users WHERE id = " . $bug['assigned_to'];
                        $assigned_result = pg_query($db, $assigned_query);
                        $assigned = pg_fetch_assoc($assigned_result);
                        echo htmlspecialchars($assigned['username']);
                    ?></td>
                    <td><?php echo htmlspecialchars($bug['created_at']); ?></td>
                    <td>
                        <a href="?edit_bug=<?php echo $bug['id']; ?>" class="btn btn-sm btn-warning" onclick="return confirmAction('edit')">Edit</a>
                        <a href="?delete_bug=<?php echo $bug['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirmAction('delete')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- Add New Bug -->
        <?php if (isset($_GET['action']) && $_GET['action'] == 'add_bug'): ?>
        <h2>Add New Bug</h2>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" name="title" class="form-control" placeholder="Title" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" class="form-control" placeholder="Description" required></textarea>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" class="form-control" required>
                    <option value="Open">Open</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Closed">Closed</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="priority" class="form-label">Priority</label>
                <select name="priority" class="form-control" required>
                    <option value="High">High</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="category_id" class="form-label">Category</label>
                <select name="category_id" class="form-control" required>
                    <?php while ($category = pg_fetch_assoc($categories_result)): ?>
                    <option value="<?php echo htmlspecialchars($category['id']); ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="assigned_to" class="form-label">Assigned To</label>
                <select name="assigned_to" class="form-control" required>
                    <?php while ($user = pg_fetch_assoc($users_result)): ?>
                    <option value="<?php echo htmlspecialchars($user['id']); ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" name="add_bug" class="btn btn-primary" onclick="return confirmAction('add')">Add Bug</button>
        </form>
        <?php endif; ?>

        <!-- Edit Bug Section -->
        <?php if (isset($_GET['edit_bug'])): ?>
        <?php
        $bug_id = $_GET['edit_bug'];
        $query = "SELECT * FROM bugs WHERE id = $1";
        $bug_result = pg_query_params($db, $query, array($bug_id));
        $bug = pg_fetch_assoc($bug_result);
        ?>
        <h2>Edit Bug</h2>
        <form method="POST" action="">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($bug['id']); ?>">
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($bug['title']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" class="form-control" required><?php echo htmlspecialchars($bug['description']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" class="form-control" required>
                    <option value="Open" <?php if ($bug['status'] == 'Open') echo 'selected'; ?>>Open</option>
                    <option value="In Progress" <?php if ($bug['status'] == 'In Progress') echo 'selected'; ?>>In Progress</option>
                    <option value="Closed" <?php if ($bug['status'] == 'Closed') echo 'selected'; ?>>Closed</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="priority" class="form-label">Priority</label>
                <select name="priority" class="form-control" required>
                    <option value="High" <?php if ($bug['priority'] == 'High') echo 'selected'; ?>>High</option>
                    <option value="Medium" <?php if ($bug['priority'] == 'Medium') echo 'selected'; ?>>Medium</option>
                    <option value="Low" <?php if ($bug['priority'] == 'Low') echo 'selected'; ?>>Low</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="category_id" class="form-label">Category</label>
                <select name="category_id" class="form-control" required>
                    <?php 
                    $categories_result = pg_query($db, "SELECT * FROM bug_categories");
                    while ($category = pg_fetch_assoc($categories_result)): 
                    ?>
                    <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php echo $category['id'] == $bug['category_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="assigned_to" class="form-label">Assigned To</label>
                <select name="assigned_to" class="form-control" required>
                    <?php 
                    $users_result = pg_query($db, "SELECT * FROM users");
                    while ($user = pg_fetch_assoc($users_result)): 
                    ?>
                    <option value="<?php echo htmlspecialchars($user['id']); ?>" <?php echo $user['id'] == $bug['assigned_to'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['username']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" name="edit_bug" class="btn btn-warning" onclick="return confirmAction('update')">Update Bug</button>
        </form>
        <?php endif; ?>

        <!-- User Management (Admin Only) -->
        <?php if (isset($_GET['action']) && $_GET['action'] == 'user_management' && $_SESSION['role'] === 'admin'): ?>
        <h2>User Management</h2>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select name="role" class="form-control" required>
                    <option value="admin">Admin</option>
                    <option value="user">User</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>
            <button type="submit" name="register" class="btn btn-primary">Add User</button>
        </form>

        <!-- List of Users -->
        <h3>Existing Users</h3>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Role</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $users_query = "SELECT * FROM users";
                $users_result = pg_query($db, $users_query);
                $counter = 1;
                while ($user = pg_fetch_assoc($users_result)):
                ?>
                <tr>
                    <td><?php echo $counter++; ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['password']); ?></td>
                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td>
                        <a href="?edit_user=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning" onclick="return confirmAction('edit')">Edit</a>
                        <a href="?delete_user=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirmAction('delete')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- Edit User Section -->
        <?php if (isset($_GET['edit_user'])): ?>
        <?php
        $user_id = $_GET['edit_user'];
        $query = "SELECT * FROM users WHERE id = $1";
        $user_result = pg_query_params($db, $query, array($user_id));
        $user = pg_fetch_assoc($user_result);
        ?>
        <h2>Edit User</h2>
        <form method="POST" action="">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" class="form-control" value="<?php echo htmlspecialchars($user['password']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select name="role" class="form-control" required>
                    <option value="admin" <?php if ($user['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                    <option value="user" <?php if ($user['role'] == 'user') echo 'selected'; ?>>User</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <button type="submit" name="edit_user" class="btn btn-warning" onclick="return confirmAction('update')">Update User</button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>