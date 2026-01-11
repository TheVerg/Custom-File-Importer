# Custom File Importer

A powerful Laravel-based application for importing data from Excel and CSV files into multiple databases with custom column mapping and flexible import settings.

## Features

- **Multi-Database Support**: Connect to and import data into multiple databases simultaneously
- **Flexible File Import**: Support for Excel and CSV file formats
- **Custom Column Mapping**: Map file columns to database table columns with full control
- **Database Connection Management**: Securely store and manage multiple database connections
- **Import Job Tracking**: Monitor import progress with detailed status tracking
- **Error Handling**: Comprehensive error reporting and failed row tracking
- **User Authentication**: Built-in user authentication and authorization
- **Responsive UI**: Modern, responsive web interface for easy data imports

## Tech Stack

- **Backend**: Laravel 12.x
- **Frontend**: Vue.js with Vite
- **Database**: MySQL/PostgreSQL (configurable)
- **File Processing**: Maatwebsite Excel 3.1, Box Spout
- **PHP**: 8.2+

## Requirements

- PHP 8.2 or higher
- Composer
- Node.js 18+ (for frontend development)
- MySQL or PostgreSQL
- XAMPP or similar PHP development environment

## Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd Custom-File-Importer
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Install JavaScript Dependencies
```bash
npm install
```

### 4. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### 5. Configure Your Database
Edit the `.env` file and configure your primary database connection:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=custom_file_importer
DB_USERNAME=root
DB_PASSWORD=
```

### 6. Run Database Migrations
```bash
php artisan migrate
```

### 7. Seed Database (Optional)
```bash
php artisan db:seed
```

## Running the Application

### Development Mode

Open two terminal windows and run:

**Terminal 1: Start Laravel Development Server**
```bash
php artisan serve
```

**Terminal 2: Start Vite Development Server**
```bash
npm run dev
```

The application will be available at `http://localhost:8000`

### Production Build
```bash
npm run build
```

## Project Structure


## Usage

### Creating a Database Connection
1. Navigate to the connections management page
2. Enter connection details (driver, host, port, credentials)
3. Test the connection
4. Save the configuration

### Importing Data
1. Select a file (Excel/CSV)
2. Choose the target database connection
3. Select the target table
4. Map columns from your file to database fields
5. Configure import settings
6. Start the import job
7. Monitor progress on the import tracking page

## API Endpoints

The application includes RESTful API endpoints for:
- Database connection management
- Import job creation and status
- File uploads
- Column mapping validation

## Testing

Run tests with:
```bash
php artisan test
```

Run specific test file:
```bash
php artisan test tests/Feature/ExampleTest.php
```

## Troubleshooting

### Database Connection Issues
- Verify your `.env` database configuration
- Ensure database server is running
- Check database user permissions

### File Upload Issues
- Verify file size limits in `php.ini`
- Check storage directory permissions
- Ensure supported file format (Excel/CSV)

### Import Failures
- Check the error message in the import job details
- Verify column mappings are correct
- Ensure data types match target database columns

## Development

### Code Style
The project uses Laravel Pint for code formatting:
```bash
./vendor/bin/pint
```

### Running Database Migrations
```bash
# Create new migration
php artisan make:migration migration_name

# Rollback migrations
php artisan migrate:rollback

# Reset database
php artisan migrate:reset
```

## License

This project is licensed under the MIT License.

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
