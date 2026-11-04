# tinyPHP

<p align="center">
  <strong>A tiny PHP toolkit for everyday development.</strong>
  <br>
  Simple APIs. Fluent chaining. Less boilerplate. More PHP.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Database-MySQL-4479A1?logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/License-MIT-green.svg" alt="MIT License">
  <img src="https://img.shields.io/badge/Dependencies-Minimal-brightgreen" alt="Minimal dependencies">
</p>

---

## What is tinyPHP?

**tinyPHP** is a lightweight, modular PHP toolkit designed to make everyday PHP development cleaner, faster, and more enjoyable.

PHP already provides powerful functionality for handling HTTP requests, processing forms, managing files, working with databases, and much more. However, using these features repeatedly can introduce unnecessary boilerplate into everyday projects.

tinyPHP provides a clean, consistent, and expressive interface around these common operations.

Instead of repeatedly working with raw PHP superglobals and writing repetitive database code, you get simple, reusable, chainable methods.

### Our philosophy

- **Keep it tiny:** No unnecessary dependencies or bloated architecture.
- **Keep it modular:** Use only the components you need.
- **Keep it familiar:** Work with PHP, not against it.
- **Keep it expressive:** Write readable code using fluent method chaining.
- **Keep it practical:** Focus on everyday development problems.

tinyPHP is not intended to replace PHP or become another full-stack framework. It is a small developer toolkit that makes working with PHP more convenient.

## Features

- Fluent GET and POST input handling.
- Request method and HTTP information.
- Input casting, trimming, defaults, and array extraction.
- Form submission handling.
- Secure file upload handling.
- File size, MIME type, and extension validation.
- Randomized upload filenames.
- CSRF token generation and verification.
- Lightweight `.env` configuration.
- PDO-based MySQL connections.
- Chainable MySQL query builder.
- Prepared statements for database values.
- PSR-4 autoloading through Composer.

## Requirements

- PHP 8.2 or newer.
- MySQL 5.7+ or MariaDB.
- Composer.
- PHP extensions:
  - `PDO`
  - `pdo_mysql`
  - `fileinfo`

## Installation

Clone the repository:

```bash
git clone https://github.com/jetcodeferrel/tinyPHP.git
```

Enter the project directory:

```bash
cd tinyPHP
```

Install Composer dependencies and generate the autoloader:

```bash
composer install
```

Create your environment configuration:

```bash
cp .env.example .env
```

On Windows Command Prompt:

```cmd
copy .env.example .env
```

## Configuration

Create a MySQL database:

```sql
CREATE DATABASE tinyphp
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Configure your `.env` file:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tinyphp
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

Import the example database schema:

```bash
mysql -u root -p tinyphp < database/schema.sql
```

Update the database credentials to match your local environment.

## Quick Start

Start the local development server:

```bash
php -S localhost:8000 -t public
```

Visit:

```text
http://localhost:8000
```

The included demo demonstrates form submission, input processing, CSRF protection, and MySQL insertion.

## Usage

### 1. Handling HTTP Requests

Capture the current request:

```php
use TinyPHP\Http\Request;

$request = Request::capture();
```

Read GET parameters:

```php
$page = $request->query('page')->int(1);

$search = $request->query('search')
    ->trim()
    ->string();
```

Read POST parameters:

```php
$name = $request->post('name')
    ->trim()
    ->string();

$email = $request->post('email')
    ->trim()
    ->lower()
    ->string();

$age = $request->post('age')->int();
```

Check request methods:

```php
if ($request->isPost()) {
    // Handle form submission.
}

if ($request->isGet()) {
    // Handle page request.
}
```

Other request information:

```php
$method = $request->method();
$path = $request->path();
$ip = $request->ip();
$agent = $request->userAgent();
```

### 2. Input Handling

tinyPHP provides convenient methods for working with input values.

```php
$value = $request->post('username');
```

Available methods:

| Method | Description |
|---|---|
| `string()` | Retrieve a string value. |
| `int()` | Convert to integer. |
| `float()` | Convert to float. |
| `bool()` | Convert to boolean. |
| `array()` | Retrieve an array. |
| `value()` | Retrieve the original value. |
| `exists()` | Check whether the input exists. |
| `isEmpty()` | Check whether the input is empty. |
| `trim()` | Remove surrounding whitespace. |
| `lower()` | Convert strings to lowercase. |
| `default($value)` | Supply a default value. |
| `only($keys)` | Extract selected array keys. |

Example:

```php
$username = $request->post('username')
    ->trim()
    ->lower()
    ->default('anonymous')
    ->string();
```

### 3. File Uploads

Check whether an uploaded file exists:

```php
if ($request->hasFile('avatar')) {
    $file = $request->file('avatar');
}
```

Validate the upload:

```php
$file->validate([
    'max_size' => 2 * 1024 * 1024,
    'extensions' => ['jpg', 'jpeg', 'png'],
    'mime_types' => [
        'image/jpeg',
        'image/png'
    ]
]);
```

Store the file:

```php
$filename = $file->store(
    __DIR__ . '/../storage/uploads'
);
```

Additional methods:

```php
$file->isValid();
$file->size();
$file->originalName();
$file->mimeType();
$file->extension();
$file->error();
```

Uploads are assigned randomized filenames and should be stored outside the public web directory.

### 4. CSRF Protection

Generate a CSRF token:

```php
use TinyPHP\Security\Csrf;

$token = Csrf::token();
```

Include it in your form:

```html
<form method="POST">

    <input
        type="hidden"
        name="_token"
        value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>"
    >

    <button type="submit">Submit</button>

</form>
```

Verify the submitted token:

```php
if (!Csrf::verify($request->post('_token')->string())) {
    http_response_code(419);
    exit('Invalid CSRF token');
}
```

Start your PHP session before using CSRF functionality.

### 5. MySQL Database

Connect to your database:

```php
use TinyPHP\Database\Database;

$db = Database::connect();
```

Fetch records:

```php
$users = $db->table('users')->get();
```

Fetch a single record:

```php
$user = $db->table('users')
    ->where('id', '=', 10)
    ->first();
```

Insert records:

```php
$db->table('users')->insert([
    'name' => 'John',
    'email' => 'john@example.com'
]);
```

Update records:

```php
$db->table('users')
    ->where('id', '=', 10)
    ->update([
        'name' => 'John Doe'
    ]);
```

Delete records:

```php
$db->table('users')
    ->where('id', '=', 10)
    ->delete();
```

Ordering and pagination:

```php
$users = $db->table('users')
    ->where('status', '=', 'active')
    ->orderBy('created_at', 'DESC')
    ->limit(20)
    ->offset(0)
    ->get();
```

For custom SQL:

```php
$users = $db->query(
    'SELECT * FROM users WHERE status = :status',
    ['status' => 'active']
)->fetchAll();
```

All query values are parameterized through PDO. SQL identifiers are validated by the query builder.

## Project Structure

```text
tinyPHP/
│
├── config/
│   └── bootstrap.php
│
├── database/
│   └── schema.sql
│
├── public/
│   └── index.php
│
├── src/
│   ├── Config/
│   │   └── Env.php
│   │
│   ├── Database/
│   │   ├── Database.php
│   │   └── Query.php
│   │
│   ├── Http/
│   │   ├── Input.php
│   │   ├── Request.php
│   │   └── UploadedFile.php
│   │
│   └── Security/
│       └── Csrf.php
│
├── storage/
│   └── uploads/
│
├── .env.example
├── .gitignore
├── composer.json
├── LICENSE
└── README.md
```

## Security Notes

tinyPHP provides convenience and basic security primitives, but it does not automatically secure an entire application.

Developers should:

- Validate all incoming user input.
- Escape untrusted output before rendering HTML.
- Use prepared statements for database queries.
- Validate uploaded file sizes and actual MIME types.
- Store uploads outside publicly accessible directories.
- Keep `.env` files and credentials out of version control.
- Use HTTPS in production.
- Configure secure session cookies.
- Implement appropriate authentication and authorization.
- Never trust client-provided filenames or request headers.

## Development

Contributions, suggestions, bug reports, and feature requests are welcome.

To contribute:

1. Fork this repository.
2. Create a feature branch.
3. Make your changes.
4. Test your changes.
5. Submit a pull request.

Example:

```bash
git checkout -b feature/my-new-feature
```

Commit your changes:

```bash
git add .
git commit -m "Add my new feature"
```

Push your branch:

```bash
git push origin feature/my-new-feature
```

## Roadmap

Potential future modules include:

- [ ] Fluent validation engine.
- [ ] HTTP response handler.
- [ ] Lightweight routing.
- [ ] Session management.
- [ ] Cookie management.
- [ ] JSON response utilities.
- [ ] File and directory utilities.
- [ ] Pagination helpers.
- [ ] Mail handling.
- [ ] Additional database operations.

The goal is to keep tinyPHP small while gradually introducing useful features that PHP developers can adopt without changing how they build applications.

## License

tinyPHP is open-source software licensed under the [MIT License](LICENSE).

You are free to use, modify, distribute, and integrate it into personal and commercial projects under the terms of the license.

---

<p align="center">
  <strong>tinyPHP</strong>
  <br>
  Less boilerplate. More PHP.
</p>