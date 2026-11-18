# concourse logistics: new hire portal
# database schema (mysql / mariadb)

# based on the employee table in figure 14.6 of fundamentals of
# database systems (elmasri & navathe, 6th ed.), which defines:
# employee (emp_id int, name varchar(15), job varchar(10), dno int)

# changes from the textbook version:
# emp_id uses auto_increment instead of pear db's $d->nextid().
# dno is now a foreign key to a department table, so an employee
# can only be assigned to a department that actually exists.
#  name and job are wider, and email, hire_date and created_at
# were added so the form collects a realistic new-hire record.

# import this file once (phpmyadmin > import, or the mysql command
# line) before opening index.php.


CREATE DATABASE IF NOT EXISTS company_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE company_db;

# drop in child-to-parent order so the script can be re-run cleanly.
DROP TABLE IF EXISTS EMPLOYEE;
DROP TABLE IF EXISTS DEPARTMENT;


# department: the departments a new hire can join.
# department numbers 1, 4 and 5 match the company database used
# throughout the textbook (headquarters, administration, research).

CREATE TABLE DEPARTMENT (
    Dno       INT          NOT NULL,
    Dname     VARCHAR(30)  NOT NULL,
    Location  VARCHAR(30)  NOT NULL,
    PRIMARY KEY (Dno),
    UNIQUE KEY uq_department_name (Dname)
) ENGINE = InnoDB;


# employee: one row per new hire submitted through the form.

CREATE TABLE EMPLOYEE (
    Emp_id      INT           NOT NULL AUTO_INCREMENT,
    Name        VARCHAR(50)   NOT NULL,
    Job         VARCHAR(30)   NOT NULL,
    Dno         INT           NOT NULL,
    Email       VARCHAR(100)  NOT NULL,
    Hire_date   DATE          NOT NULL,
    Created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (Emp_id),
    UNIQUE KEY uq_employee_email (Email),
    CONSTRAINT fk_employee_department
        FOREIGN KEY (Dno) REFERENCES DEPARTMENT (Dno)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE = InnoDB;


# starting data

INSERT INTO DEPARTMENT (Dno, Dname, Location) VALUES
    (1, 'Headquarters',     'Grand Concourse'),
    (4, 'Administration',   'Grand Concourse'),
    (5, 'Research',         'Fordham Rd'),
    (6, 'Logistics',        'Hunts Point'),
    (7, 'Customer Support', 'Yonkers');

# two sample employees so the directory isn't empty on first load.
INSERT INTO EMPLOYEE (Name, Job, Dno, Email, Hire_date) VALUES
    ('Maria Santos',  'Engineer',        5, 'msantos@concourse.example',  '2026-09-08'),
    ('James Okafor',  'Dispatcher',      6, 'jokafor@concourse.example',  '2026-10-13');
