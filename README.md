# 🏨 HostelHub – Hostel Management System

HostelHub is a web-based Hostel Management System developed to simplify and organize daily hostel operations through a centralized platform.

## 🚀 Features

- **Role-Based Login:** Separate access for Admin, Staff, and Students.
- **Dashboard:** Displays important hostel statistics.
- **Student Management:** Manage student records.
- **Hostel Management:** Manage hostel information.
- **Room Management:** Add, update, delete, and view rooms.
- **Room Allocations:** Manage student room assignments.
- **Payment Management:** Maintain payment records.
- **Complaint Management:** Manage student complaints.
- **Visitor Management:** Maintain visitor records.
- **Employee Management:** Manage hostel employees.
- **Attendance Management:** Maintain student attendance.

## 🛠️ Technologies Used

- HTML5
- CSS3
- JavaScript
- Bootstrap 5
- PHP
- MySQL
- XAMPP

## 👥 User Roles

| Role | Access |
|---|---|
| Admin | All modules |
| Staff | Students, Rooms, Room Allocations, Complaints, Visitors, Attendance |
| Student | Student Dashboard only |

## 🗄️ Database Design

**Database Name:** `hostelmanagementsystem`

The database consists of 10 tables:

1. Students
2. Hostels
3. Rooms
4. Room_Allocations
5. Payments
6. Complaints
7. Visitors
8. Employees
9. Attendance
10. Users

## 🔐 Security Features

- User Authentication
- Password Verification
- Role-Based Access Control
- Restricted Page Access
- Unauthorized Access Protection

## 💻 How to Run the Project

1. Install XAMPP on your computer.
2. Copy the project folder into the `htdocs` directory.
3. Start Apache and MySQL from XAMPP Control Panel.
4. Open phpMyAdmin.
5. Create a database named `hostelmanagementsystem`.
6. Import the project database SQL file, if available.
7. Configure the database connection in `db.php`.
8. Open the following URL in your browser:

`http://localhost/HostelManagementSystem/`

## 📁 Project Documentation

The `documentation` folder contains:

- Project Overview
- Database Design
- Testing Documentation
- Entity Relationship Diagram (ERD)

## 🎯 Project Objective

The main objective of HostelHub is to make hostel administration more organized, efficient, and easier through a digital management system.

The system provides a centralized platform to manage hostel activities, reduce manual work, and maintain organized records.