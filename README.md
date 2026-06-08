# pawHouse Adoption Center

A complete PHP, HTML, CSS, and JavaScript web application for an animal adoption center. The platform provides separate portals for clients, employees, and administrators, fully integrated with a MySQL database.

## Features

**For Clients:**
- Browse available animal categories, breeds, and individual pets.
- Submit adoption requests for specific animals.
- Request to surrender a pet, including uploading photos and providing health details.
- Track adoption request statuses and upcoming welfare meetings.

**For Employees:**
- View and manage weekly welfare meetings with new adopters.
- Submit welfare reports and trigger pet return demands in cases of mistreatment.
- Review shelter inventory and monitor adoption request statuses.

**For Administrators:**
- Comprehensive dashboard to manage adoption demands (accept/decline and set delivery dates).
- Manage client surrender requests (accept/reject).
- Schedule mandatory post-adoption welfare meetings.
- Edit center inventory (animals, breeds, categories).
- Manage employee and client accounts.
- Export center records (Employees, Clients, Animals) to CSV.
- View detailed adoption statistics and center activity.

## Project Structure

- `index.php`: Public home page with center information, location, employees, and animal categories.
- `login.php`: Role-based login entry (client, employee, admin).
- `register.php`: Client account creation page.
- `client/`: Client portal pages (dashboard, animal browsing, surrender form).
- `employee/`: Employee portal pages (welfare meetings, adoption tracking).
- `admin/`: Administrator portal pages (dashboard, editing entities, statistics, CSV export).
- `includes/`: Shared logic (`db.php` for database connection, `data.php` for data fetching, headers, footers).
- `database/adoption_center.sql`: MySQL/MariaDB schema and initial starter data.
- `images/`: Directory for uploaded images (e.g., surrender photos).
- `assets/`: CSS styles and JavaScript logic.

## Run Locally

1. Place this project folder inside your local server root (e.g., `htdocs/projetWeb` for XAMPP).
2. Start the Apache and MySQL modules.
3. Import the database schema and starter data:
   - Open phpMyAdmin (or your preferred MySQL client).
   - Create a database named `pawhouse_adoption` if it does not exist.
   - Import `database/adoption_center.sql`.
4. Open `http://localhost/projetWeb/index.php` in your browser.

## Default Credentials

All seeded accounts in the SQL file use the password `1234`. 

- **Admin:** `admin@pawhouse.test`
- **Employee:** `salma@pawhouse.test`
- **Client:** `nadia@example.test`

*Note: The stored hash value is a SHA-512 crypt hash. This prototype also retains `password_visible_to_admin` to allow administrators to view client passwords on their dashboard for demonstration purposes. In a real production application, passwords must only be stored as irreversible hashes.*
