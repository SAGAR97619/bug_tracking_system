# Bug Tracking System

A simple Bug Tracking System built with PHP and PostgreSQL. This project helps teams track and manage software bugs efficiently. It includes features like user authentication, bug management, and a dashboard to track bug status.

---

## Features

- **User Authentication:** Login and logout functionality with role-based access (Admin and User).
- **Bug Management:** Add, edit, and delete bugs. Assign bugs to users and track their status (Open, In Progress, Closed).
- **User Management (Admin Only):** Add, edit, and delete users. Assign roles (Admin or User).
- **Dashboard:** View total bugs, open bugs, in-progress bugs, and closed bugs.
- **Comments:** Add comments to bugs for better collaboration.

---

## Technologies Used

- **Frontend:** HTML, CSS, Bootstrap
- **Backend:** PHP
- **Database:** PostgreSQL
- **Deployment:** Render

---

## How to Run the Project

### Step 1: Clone the Repository
```bash
git clone https://github.com/SAGAR97619/bug_tracking_system.git
cd bug_tracking_system
Step 2: Set Up the Database
Create a PostgreSQL database named bug_tracking_system.

Import the SQL schema from database/schema.sql (if provided).

Step 3: Configure Environment Variables
Create a .env file in the root directory and add the following:

env
Copy
DB_HOST=your_database_host
DB_PORT=5432
DB_NAME=bug_tracking_system
DB_USER=your_database_user
DB_PASSWORD=your_database_password
SSL_MODE=require
Step 4: Deploy on Render
Push the code to your GitHub repository.

Create a new Web Service on Render and connect your GitHub repository.

Set the environment variables in the Render dashboard.

Deploy the project.

How to Use
Login:

Use the following credentials to log in:

Admin: admin / admin123

User: user / user123

Dashboard:

View the total number of bugs, open bugs, in-progress bugs, and closed bugs.

Report a Bug:

Go to the "Add New Bug" section and fill in the bug details.

Manage Users (Admin Only):

Add, edit, or delete users from the "User Management" section.

View Bugs:

View all reported bugs and their details in the "View Reported Bugs" section.

Screenshots
Login Page
![image](https://github.com/user-attachments/assets/7cea4dec-14a6-4629-91b7-bec059abf43c)


Dashboard
![image](https://github.com/user-attachments/assets/57f52f40-ca16-4d04-a79f-4221224fb018)


Add New Bug
![image](https://github.com/user-attachments/assets/aa6d0fa0-b66c-4344-a431-84c4c14b2e1e)

User Management
![image](https://github.com/user-attachments/assets/6b6f8756-1cc3-47b9-9ea6-681061ede403)


Contact
For any questions or feedback, feel free to reach out:

Name: Sagar Saini
Email: sainisagar2506@gmail.com
GitHub: SAGAR97619

