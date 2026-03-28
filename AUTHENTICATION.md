# Authentication System Setup

## Overview
The Cloudrobe admin panel now has a complete authentication system with role-based access control.

## User Roles

### ROLE_ADMIN (Full Access)
- Access to Dashboard
- Full access to Products (Add, Edit, Delete, View)
- Full access to Categories (Add, Edit, Delete, View)
- Full access to Inventory (Add, Edit, Delete, View)
- User Management (Add, Edit, Delete users)

### ROLE_STAFF (Limited Access)
- Access to Dashboard
- Limited access to Products (Edit and View only - cannot Add or Delete)
- No access to Categories
- No access to Inventory
- No access to User Management

## Default User Accounts

### Admin Accounts
1. **Username:** `admin` | **Password:** `admin123`
   - Email: admin@cloudrobe.com
   - Full Name: Admin User

2. **Username:** `yannie` | **Password:** `yannie123`
   - Email: yannie@cloudrobe.com
   - Full Name: Yannie Administrator

### Staff Accounts
1. **Username:** `staff` | **Password:** `staff123`
   - Email: staff@cloudrobe.com
   - Full Name: Staff User

2. **Username:** `john` | **Password:** `john123`
   - Email: john@cloudrobe.com
   - Full Name: John Staff Member

## Login

Navigate to: `/login`

The system will automatically redirect authenticated users to the admin dashboard.

## Features

### Security Features
- Password hashing using Symfony's password hasher
- CSRF protection on login and all forms
- Session-based authentication
- Role hierarchy (ROLE_ADMIN includes ROLE_STAFF permissions)

### UI Features
- User info displayed in sidebar (avatar with initials, name, and role)
- Conditional menu items based on user role
- Logout button in sidebar
- Styled login page matching the Cloudrobe color palette

### Access Control
- Controllers protected with `#[IsGranted()]` attributes
- Template-level access control using `is_granted()` Twig function
- Automatic redirect to login for unauthenticated users
- 403 Forbidden for unauthorized access attempts

## User Management (Admin Only)

Admins can manage users through the Users section in the sidebar:
- View all users with their roles
- Add new users (Staff or Admin)
- Edit existing users
- Delete users
- Change user passwords

## Color Palette

The authentication system uses the existing Cloudrobe color scheme:
- Primary: #ecc9c9
- Secondary: #df9152
- Accent: #e5a3a3
- Dark: #5c3a3a / #2c2c2c
- Light: #fdf6f6 / #f8f9fa

## Technical Details

### Entities
- `User` entity with username, email, fullName, password, and roles

### Security Configuration
- Form login with CSRF protection
- Logout functionality
- Role hierarchy defined in `security.yaml`
- Access control rules for routes

### Database Migration
User table created with migration: `Version20251210070800.php`

## Important Notes

⚠️ **Security Recommendations:**
1. Change all default passwords after first login
2. Use strong passwords in production
3. Consider implementing password reset functionality
4. Add email verification for new accounts
5. Implement account lockout after failed login attempts

## Future Enhancements

Suggested improvements:
- Password reset via email
- Two-factor authentication
- User activity logs
- Account lockout after failed attempts
- Email verification for new users
- Profile management page
- Remember me functionality (already implemented in login form)
