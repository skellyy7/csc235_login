<?php
/**
 * Customers Page
 *
 * @author    Brendan Palazzo
 * @copyright 2026
 * @date      2026-05-02
 * @version   1.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 0); // set to 1 to display errors, 0 to hide them

/* Quick Paths */
/* note the 2 after __FILE__, because it's 2 directories deep */
include_once (realpath(dirname(__FILE__, 2).'/php/session.php'));
include_once (realpath(dirname(__FILE__, 2).'/php/path.php'));

/* Check Role */
if ($user_role != 1 && $user_role != 2) {
    header("location: " . BASE_URL . "/denied.html");
    exit();
} // redirect if not logged in

/* Page Name */
$page_name = "user";

/* Database Connection */
include_once (ROOT_PATH . '/php/config.php');

// Handle Form Submission
$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Grab and clean each field from the form
    $FirstName = trim($_POST['FirstName'] ?? '');
    $LastName  = trim($_POST['LastName']  ?? '');
    $Email     = trim($_POST['Email']     ?? '');
    $Phone     = trim($_POST['Phone']     ?? '');
    $Address   = trim($_POST['Address']   ?? '');
    $City      = trim($_POST['City']      ?? '');
    $Country   = trim($_POST['Country']   ?? 'USA');

    // Validate required fields
    if ($FirstName === '') { $errors[] = 'First name is required.'; }
    if ($LastName  === '') { $errors[] = 'Last name is required.'; }
    if ($Email     === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($Email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($Phone !== '' && !preg_match('/^[\d\s\(\)\-\+]+$/', $Phone)) {
      $errors[] = 'Phone must contain numbers only. Dashes and parentheses are ok.';
    }
    if ($Country === '') { $Country = 'USA'; }

    // If no errors, insert into the database
    if (empty($errors)) {
        $stmt = $db_connection->prepare(
            "INSERT INTO Customers (FirstName, LastName, Email, Phone, Address, City, Country)
             VALUES (?, ?, ?, ?, ?, ?, ?)");

        // Use null for optional blank fields
        $phone   = $Phone   !== '' ? $Phone   : null;
        $address = $Address !== '' ? $Address : null;
        $city    = $City    !== '' ? $City    : null;

        $stmt->bind_param('sssssss', $FirstName, $LastName, $Email, $phone, $address, $city, $Country);

        try {
          $stmt->execute();
          header("Location: customers.php?success=1");
          exit();
        } catch (Exception $e) {
            $errors[] = 'Error adding customer. Email may already exist.';
        }
        $stmt->close();
    }
}

// Fetch All Customers for the Table
$result = $db_connection->query("SELECT * FROM Customers ORDER BY CreatedAt DESC");

?>
<!doctype html>
<html lang="en">
  <head>
    <?php include_once (ROOT_PATH . '/include/head.php'); ?>
  </head>
  <body class="<?php echo $page_name; ?>">

  <?php include_once (ROOT_PATH . '/include/header.php'); ?>

    <main role="main" class="container">
      <div class="row">
        <div class="col">

          <h1>Customers</h1>
          <hr class="mb-4">

          <!-- ADD CUSTOMER FORM -->
          <section class="mb-5">
            <h2>Add New Customer</h2>

            <!-- Success message -->
            <?php if (isset($_GET['success'])): ?>
              <div class="alert alert-success">Customer added successfully!</div>
            <?php endif; ?>

            <!-- Error messages -->
            <?php if (!empty($errors)): ?>
              <div class="alert alert-danger">
                <ul class="mb-0">
                  <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <form action="" method="POST">

              <div class="row mb-3">
                <div class="col-sm-6">
                  <label for="FirstName" class="form-label">First Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="FirstName" name="FirstName" maxlength="50" required
                         value="<?php echo htmlspecialchars($_POST['FirstName'] ?? ''); ?>">
                </div>
                <div class="col-sm-6">
                  <label for="LastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="LastName" name="LastName" maxlength="50" required
                         value="<?php echo htmlspecialchars($_POST['LastName'] ?? ''); ?>">
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-sm-6">
                  <label for="Email" class="form-label">Email <span class="text-danger">*</span></label>
                  <input type="email" class="form-control" id="Email" name="Email" maxlength="100" required
                         value="<?php echo htmlspecialchars($_POST['Email'] ?? ''); ?>">
                </div>
                <div class="col-sm-6">
                  <label for="Phone" class="form-label">Phone</label>
                  <input type="text" class="form-control" id="Phone" name="Phone" maxlength="20"
                         value="<?php echo htmlspecialchars($_POST['Phone'] ?? ''); ?>">
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-sm-6">
                  <label for="Address" class="form-label">Address</label>
                  <input type="text" class="form-control" id="Address" name="Address" maxlength="255"
                         value="<?php echo htmlspecialchars($_POST['Address'] ?? ''); ?>">
                </div>
                <div class="col-sm-6">
                  <label for="City" class="form-label">City</label>
                  <input type="text" class="form-control" id="City" name="City" maxlength="100"
                         value="<?php echo htmlspecialchars($_POST['City'] ?? ''); ?>">
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-sm-6">
                  <label for="Country" class="form-label">Country <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="Country" name="Country" maxlength="100"
                         value="<?php echo htmlspecialchars($_POST['Country'] ?? 'USA'); ?>">
                  <div class="form-text">Defaults to USA if left blank.</div>
                </div>
              </div>

              <button type="submit" class="btn btn-primary">Add Customer</button>
              <small class="text-muted ms-2"><span class="text-danger">*</span> Required</small>

            </form>
          </section>


          <!-- CUSTOMERS TABLE -->
          <section class="mb-4">
            <h2>All Customers</h2>

            <div style="max-height: 400px; overflow-y: auto;">
              <table class="table table-striped table-hover">
                <thead class="table-dark sticky-top">
                  <tr>
                    <th scope="col">#</th>
                    <th scope="col">First Name</th>
                    <th scope="col">Last Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Phone</th>
                    <th scope="col">Address</th>
                    <th scope="col">City</th>
                    <th scope="col">Country</th>
                    <th scope="col">Member Since</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    if ($result->num_rows > 0) {
                      while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<th scope='row'>" . $row['CustomerID'] . "</th>";
                        echo "<td>" . htmlspecialchars($row['FirstName']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['LastName'])  . "</td>";
                        echo "<td>" . htmlspecialchars($row['Email'])     . "</td>";
                        echo "<td>" . ($row['Phone']   ? htmlspecialchars($row['Phone'])   : '—') . "</td>";
                        echo "<td>" . ($row['Address'] ? htmlspecialchars($row['Address']) : '—') . "</td>";
                        echo "<td>" . ($row['City']    ? htmlspecialchars($row['City'])    : '—') . "</td>";
                        echo "<td>" . htmlspecialchars($row['Country'])   . "</td>";
                        echo "<td>" . htmlspecialchars(date('M j, Y', strtotime($row['CreatedAt']))) . "</td>";
                        echo "</tr>";
                      }
                    } else {
                      echo "<tr><td colspan='9'>No customers found.</td></tr>";
                    }
                  ?>
                </tbody>
              </table>
            </div>

          </section>

        </div>
      </div>
    </main>

  <?php include_once (ROOT_PATH . '/include/footer.php'); ?>
  </body>
</html>
<?php $db_connection->close(); ?>
