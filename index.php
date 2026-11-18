<?php
// concourse logistics new hire portal
// based on figure 14.6 (fundamentals of database systems, 6th ed., chapter 14)
//
// the textbook uses the pear db library. pear db doesn't work with newer
// versions of php, so i used pdo instead. it follows the same steps as
// the book: connect, use ? placeholders for the insert, then fetch rows.

// database settings (xampp defaults)
$host     = "localhost";
$dbname   = "company_db";
$username = "root";
$password = "";

// connect to the database (figure 14.6, lines 1-2)
try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // stop and throw an error if a query fails (like pear_error_die in line 9)
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<h2>Could not connect to the database.</h2>
         <p>Make sure MySQL is running and New_hire_schema.sql has been imported.</p>");
}

// get the list of departments for the dropdown menu
$departments = $db->query("SELECT Dno, Dname FROM DEPARTMENT ORDER BY Dname")->fetchAll();

$errors  = [];
$success = "";

// these keep the user's answers in the form if there is an error
$name = $job = $dno = $email = $hire_date = "";

// handle the form when it is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // get the values from the $_post array (section 14.2.4)
    $name      = trim($_POST["emp_name"] ?? "");
    $job       = trim($_POST["emp_job"] ?? "");
    $dno       = $_POST["emp_dno"] ?? "";
    $email     = trim($_POST["emp_email"] ?? "");
    $hire_date = $_POST["emp_hire_date"] ?? "";

    // check that the input is valid before saving it
    if ($name == "") {
        $errors[] = "Name is required.";
    }
    if ($job == "") {
        $errors[] = "Job title is required.";
    }
    // make sure the department number is one that is actually in the department table
    if (!in_array($dno, array_column($departments, "Dno"))) {
        $errors[] = "Please choose a department.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    if ($hire_date == "") {
        $errors[] = "Start date is required.";
    }

    // make sure the email isn't already in the database
    if (empty($errors)) {
        $check = $db->prepare("SELECT COUNT(*) FROM EMPLOYEE WHERE Email = ?");
        $check->execute([$email]);
        if ($check->fetchColumn() > 0) {
            $errors[] = "An employee with that email already exists.";
        }
    }

    // insert the new employee (figure 14.6, lines 13-15)
    // using ? placeholders so the user's input can't change the sql command
    // (this prevents sql injection, as explained in section 14.3.2).
    if (empty($errors)) {
        $q = $db->prepare("INSERT INTO EMPLOYEE (Name, Job, Dno, Email, Hire_date)
                           VALUES (?, ?, ?, ?, ?)");
        $q->execute([$name, $job, $dno, $email, $hire_date]);

        $success = "$name was added successfully!";

        // clear the form after saving
        $name = $job = $dno = $email = $hire_date = "";
    }
}

// count employees in each department
$counts = $db->query("SELECT D.Dname, COUNT(E.Emp_id) AS Total
                      FROM DEPARTMENT D
                      LEFT JOIN EMPLOYEE E ON D.Dno = E.Dno
                      GROUP BY D.Dno, D.Dname
                      ORDER BY D.Dname")->fetchAll();

// search employees by job and department (figure 14.7, lines 8-9)
$search_job = isset($_GET["job"]) ? trim($_GET["job"]) : "";
$search_dno = isset($_GET["dno"]) ? $_GET["dno"] : "";

$sql = "SELECT E.Emp_id, E.Name, E.Job, D.Dname, E.Email, E.Hire_date
        FROM EMPLOYEE E JOIN DEPARTMENT D ON E.Dno = D.Dno
        WHERE 1=1";
$params = [];

if ($search_job != "") {
    $sql .= " AND E.Job LIKE ?";
    $params[] = "%" . $search_job . "%";
}
if ($search_dno != "") {
    $sql .= " AND E.Dno = ?";
    $params[] = $search_dno;
}
$sql .= " ORDER BY E.Emp_id DESC";

$q = $db->prepare($sql);
$q->execute($params);
$employees = $q->fetchAll();   // similar to $d->getall() in figure 14.7, line 14
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Hire Portal - Concourse Logistics</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h1>Concourse Logistics - New Hire Portal</h1>
    <p>Add new employees to the company database.</p>
</header>

<div class="container">

    <!-- new hire form -->
    <div class="box form-box">
        <h2>Add a New Hire</h2>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success != ""): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <label>Full Name</label>
            <input type="text" name="emp_name" maxlength="50" required
                   value="<?php echo htmlspecialchars($name); ?>">

            <label>Job Title</label>
            <input type="text" name="emp_job" maxlength="30" required
                   value="<?php echo htmlspecialchars($job); ?>">

            <label>Department</label>
            <select name="emp_dno" required>
                <option value="">-- Select a department --</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?php echo $d["Dno"]; ?>"
                        <?php if ($dno == $d["Dno"]) echo "selected"; ?>>
                        <?php echo htmlspecialchars($d["Dname"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Work Email</label>
            <input type="email" name="emp_email" maxlength="100" required
                   value="<?php echo htmlspecialchars($email); ?>">

            <label>Start Date</label>
            <input type="date" name="emp_hire_date" required
                   value="<?php echo htmlspecialchars($hire_date); ?>">

            <button type="submit">Save New Hire</button>
        </form>
    </div>

    <!-- employee list -->
    <div class="box list-box">
        <h2>Employee Directory</h2>

        <div class="counts">
            <?php foreach ($counts as $c): ?>
                <span><?php echo htmlspecialchars($c["Dname"]); ?>: <b><?php echo $c["Total"]; ?></b></span>
            <?php endforeach; ?>
        </div>

        <form method="get" class="search">
            <input type="text" name="job" placeholder="Search by job title"
                   value="<?php echo htmlspecialchars($search_job); ?>">
            <select name="dno">
                <option value="">All departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?php echo $d["Dno"]; ?>"
                        <?php if ($search_dno == $d["Dno"]) echo "selected"; ?>>
                        <?php echo htmlspecialchars($d["Dname"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Search</button>
        </form>

        <?php if (count($employees) > 0): ?>
            <table>
                <tr>
                    <th>ID</th><th>Name</th><th>Job</th><th>Department</th><th>Email</th><th>Start Date</th>
                </tr>
                <?php foreach ($employees as $e): ?>
                    <tr>
                        <td><?php echo $e["Emp_id"]; ?></td>
                        <td><?php echo htmlspecialchars($e["Name"]); ?></td>
                        <td><?php echo htmlspecialchars($e["Job"]); ?></td>
                        <td><?php echo htmlspecialchars($e["Dname"]); ?></td>
                        <td><?php echo htmlspecialchars($e["Email"]); ?></td>
                        <td><?php echo $e["Hire_date"]; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No employees found.</p>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
