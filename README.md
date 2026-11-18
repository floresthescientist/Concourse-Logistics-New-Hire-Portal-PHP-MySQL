
# Concourse Logistics: New Hire Portal

A PHP web app for **Concourse Logistics**, a made-up delivery company in the Bronx. HR staff use a form to add new employees, and each one is saved to a MySQL database. The page also shows an employee directory with a search and a count of employees in each department.

This project is my version of **Figure 14.6** from *Fundamentals of Database Systems* (Elmasri & Navathe, 6th ed., Chapter 14). In that example, PHP connects to a database, creates an EMPLOYEE table, and inserts a new employee from form data.

## Files

| File | What it does |
|---|---|
| `index.php` | Connects to the database, shows the form, saves new employees, and shows the employee directory |
| `style.css` | The page's styling (colors, layout, form and table design) |
| `New_hire_schema.sql` | Creates the database, the `DEPARTMENT` and `EMPLOYEE` tables, and some starting data |
| `README.md` | This documentation |

## Setup

You need **XAMPP** (or any setup with PHP and MySQL). Download it at <https://www.apachefriends.org/>.

1. Put the project folder inside XAMPP's `htdocs` folder, for example `C:\xampp\htdocs\concourse-hire-portal`.
2. Open the XAMPP Control Panel and start **Apache** and **MySQL**.
3. Go to <http://localhost/phpmyadmin>, click the **Import** tab, choose `New_hire_schema.sql`, and click **Import** (or **Go**). This creates the `company_db` database with 5 departments and 2 sample employees.
4. Open <http://localhost/concourse-hire-portal/> in your browser.

The database login is set at the top of `index.php`. It uses XAMPP's default login (user `root`, no password). If your MySQL login is different, change these lines:

```php
$host     = "localhost";
$dbname   = "company_db";
$username = "root";
$password = "";
```

Importing `New_hire_schema.sql` again resets the tables back to the starting data.

## How to use it

1. Fill out the **Add a New Hire** form: full name, job title, department, work email and start date.
2. Click **Save New Hire**. You'll see a green message if it worked, or a red box listing what needs to be fixed.
3. The new employee shows up at the top of the **Employee Directory**, and the department counts update.
4. Use the search bar to find employees by job title, department, or both.

## The form and the database

### Tables

**DEPARTMENT**: Dno (primary key), Dname, Location

**EMPLOYEE**: Emp_id (primary key, auto increment), Name, Job, Dno (foreign key to DEPARTMENT), Email (unique), Hire_date, Created_at

The EMPLOYEE table is based on the one in Figure 14.6 (Emp_id, Name, Job, Dno). I added Email, Hire_date and Created_at, and made Dno a foreign key to a new DEPARTMENT table. Departments 1, 4 and 5 (Headquarters, Administration, Research) are the same as the COMPANY database in the textbook.

### Form fields

| Form field | Saved to column | Check before saving |
|---|---|---|
| Full Name (`emp_name`) | `Name` | Can't be empty |
| Job Title (`emp_job`) | `Job` | Can't be empty |
| Department (`emp_dno`) | `Dno` | Must be a department from the DEPARTMENT table |
| Work Email (`emp_email`) | `Email` | Must be a valid email and not already used |
| Start Date (`emp_hire_date`) | `Hire_date` | Can't be empty |

The names `emp_name`, `emp_job` and `emp_dno` are the same as the ones in Figure 14.6.

### What happens when you click Save

1. PHP connects to the database (Figure 14.6, lines 1–2).
2. It gets the form values from the `$_POST` array (Section 14.2.4).
3. It checks each value. If something is wrong, it shows the errors and doesn't save anything. The user's answers stay in the form so they don't have to retype them.
4. It checks that the email isn't already in the EMPLOYEE table.
5. It saves the employee with an INSERT that uses `?` placeholders (Figure 14.6, lines 13–15):
   ```php
   $q = $db->prepare("INSERT INTO EMPLOYEE (Name, Job, Dno, Email, Hire_date)
                      VALUES (?, ?, ?, ?, ?)");
   $q->execute([$name, $job, $dno, $email, $hire_date]);
   ```
6. The page reloads the directory with a SELECT query (based on Figure 14.7), so the new employee appears right away.

## Security

- **SQL injection:** All queries that use user input have `?` placeholders. Section 14.3.2 explains that putting `$_POST` values straight into the SQL string (lines 11–12 of Figure 14.6) is unsafe, so I only used the placeholder version.
- **HTML injection:** Everything printed on the page goes through `htmlspecialchars()`. If someone types HTML or JavaScript into a field, it shows up as plain text instead of running.
- **Bad data:** The form is checked in PHP before anything is saved, and the database also enforces the foreign key and the unique email.

## Unique features (changes from the textbook)

1. **PDO instead of PEAR DB.** The textbook uses PEAR DB, which doesn't work with newer versions of PHP. PDO follows the same steps:

   | Textbook (PEAR DB) | My version (PDO) |
   |---|---|
   | `DB::connect(...)` | `new PDO(...)` |
   | `$d->setErrorHandling(PEAR_ERROR_DIE)` | `PDO::ERRMODE_EXCEPTION` |
   | `$d->nextID('EMPLOYEE')` | `AUTO_INCREMENT` in the table |
   | `$d->query('... ?', array(...))` | `$db->prepare('... ?')` then `execute([...])` |
   | `$d->getAll(...)` | `$q->fetchAll()` |

2. **DEPARTMENT table and dropdown.** Instead of typing a department number, the user picks from a dropdown filled from the DEPARTMENT table. The foreign key makes sure every employee belongs to a real department.
3. **Duplicate email check.** Two employees can't be saved with the same email.
4. **Employee directory.** The saved employees are shown in a table under the form, using a JOIN so the department name appears instead of just the number.
5. **Search.** Based on the dynamic query in Figure 14.7 (lines 8–9), you can search by job title and department. I used `LIKE` so searching "eng" finds "Engineer".
6. **Department counts.** A `LEFT JOIN` with `GROUP BY` counts employees in each department, including departments with 0 employees.
7. **Error messages.** The user sees clear messages and keeps what they typed instead of getting a PHP error.

## Troubleshooting

- **"Could not connect to the database"**: Make sure MySQL is started in XAMPP, `New_hire_schema.sql` was imported, and the login at the top of `index.php` is correct.
- **Employee IDs skip a number**: This is normal in MySQL. Auto increment numbers aren't reused.

## Reference

Elmasri, R., & Navathe, S. B. (2011). *Fundamentals of Database Systems* (6th ed.), Chapter 14. Addison-Wesley.
