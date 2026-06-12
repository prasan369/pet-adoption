# 🐾 Pet Adoption — A Marketplace for Dogs

A Facebook Marketplace–style web app for posting and finding dogs for adoption, built with PHP, MySQL, and a custom dark-mode UI.

## Features

### User Side
- Browse dogs in a Facebook Marketplace–style photo grid
- Search and filter by name, breed, age, gender, and area
- Location-based posting with interactive map (Leaflet.js + OpenStreetMap)
- Location autocomplete search powered by Nominatim
- Post a dog for adoption with up to 5 photos
- Pet detail page with full photo gallery, owner info, and location map
- Real-time-style messaging between adopters and owners
- Report a listing for review
- Manage your own listings (edit, delete, mark as adopted)
- User profile management

### Admin Side
- Dashboard with live stats (total users, total pets, adoptions, pending reports)
- Manage all pet listings (add, view, delete)
- Manage all users
- Review reported listings with full pet and owner details before taking action
- Remove flagged listings or dismiss reports

## Tech Stack

- **Backend:** PHP (mysqli + PDO)
- **Database:** MySQL
- **Frontend:** HTML, CSS (custom dark theme), JavaScript
- **Maps & Location:** Leaflet.js, OpenStreetMap, Nominatim Geocoding API
- **Environment:** XAMPP (Apache + MySQL)

## Database Structure

| Table | Description |
|---|---|
| `users` | User accounts (regular users and admins) |
| `pets` | Pet listings with location, status, and details |
| `pet_photos` | Multiple photos per pet listing |
| `messages` | Chat messages between users about a specific pet |
| `reports` | User-submitted reports on listings |

## Security

- PDO prepared statements / mysqli prepared statements (no raw SQL)
- CSRF tokens on all forms
- Password hashing with bcrypt (`password_hash` / `password_verify`)
- File upload validation via MIME type checking (`finfo`)
- Output escaping with `htmlspecialchars()` throughout
- Session-based authentication with role-based access control (user / admin)

## Setup

1. Clone or copy the project into your XAMPP `htdocs` folder:
   ```
   C:\xampp\htdocs\pet-adoption
   ```

2. Start Apache and MySQL via XAMPP Control Panel

3. Create the database and import the schema:
   - Open phpMyAdmin
   - Create a database named `pet_adoption`
   - Import `includes/db_setup.sql`

4. Configure database credentials in `includes/config.php`

5. Visit the site:
   ```
   http://localhost/pet-adoption
   ```

6. Register a user account, or promote an existing user to admin:
   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'your@email.com';
   ```

## Project Structure

```
pet-adoption/
├── admin/              # Admin panel (dashboard, manage pets/users, reports)
├── css/                # Stylesheets (marketplace.css)
├── includes/           # Config, auth helpers, DB setup
├── js/                 # Client-side scripts (marketplace.js)
├── uploads/            # User-uploaded pet photos
├── user/               # User-facing pages (dashboard, post listing, messages, etc.)
├── index.php
├── login.php
├── register.php
└── logout.php
```

## Future Improvements

- Push notifications for new messages
- Email notifications for adoption inquiries
- Admin analytics dashboard
- Pagination for large listing sets