# PAW - University Attendance Management System
## Université Alger 1 - Benyoucef Benkhedda

## 📋 Project Overview
A comprehensive attendance management system for Université Alger 1, designed to streamline student attendance tracking, course management, and absence justifications.

## 🎨 UI/UX Design
**Figma Design Mockups:**
🔗 [Figma Design Project - ATTENDANCE PROJECT PAW](https://www.figma.com/design/fJSj26vxGdPhOe8iJ36KtP/ATTENDANCE-PROJECT-PAW?m=auto&t=jtjz1SHyG2XiANLp-6)


### 👨‍🎓 Student Features
- View attendance records
- Submit absence justifications
- Track course schedules
- Monitor attendance statistics

### 👨‍🏫 Teacher Features
- Take attendance for sessions
- Manage course groups
- Review student justifications
- Generate attendance reports

### ⚙️ Admin Features
- User management (students, teachers)
- Course and group management
- System oversight
- Comprehensive reporting

## 🗄️ Database Structure

### Core Tables
- **Users** - All system users (admin, teachers, students)
- **Courses** - Course information and details
- **Course Groups** - TD/TP groups with capacity limits
- **Sessions** - Class sessions with date/time
- **Attendance** - Student presence records
- **Justifications** - Absence justification system

## 🚀 Installation

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Web server (Apache/Nginx)

### Database Setup
1. Create database: `university_attendance`
2. Import schema: `database/schema.sql`
3. Load sample data: `database/sample_data.sql`

### Configuration
Update `config.php` with your database credentials:
```php
public static $host = 'localhost';
public static $username = 'your_username';
public static $password = 'your_password';
public static $database = 'university_attendance';
