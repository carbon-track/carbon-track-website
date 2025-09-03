# CarbonTrack Platform AI Coding Instructions

## Architecture Overview

**CarbonTrack** is a PHP-based carbon accounting web platform with a MySQL backend that helps users track their green actions and convert them into carbon credits. The system follows a traditional LAMP stack architecture with modern frontend enhancements.

### Core Components
- **Backend**: Pure PHP with PDO for database operations
- **Database**: MySQL with structured tables for users, transactions, products, messages
- **Frontend**: Vanilla JavaScript with Bootstrap 4.6.2 for UI components
- **Authentication**: JWT-like token system using AES-256-CBC encryption
- **Error Handling**: Centralized error logging and JSON API responses

## Critical Development Patterns

### Authentication & Security Architecture
All API endpoints use a custom encrypted token system defined in `global_variables.php`:

```php
// Token generation (login.php)
$token = opensslEncrypt($userEmail, $key);

// Token validation pattern used across all endpoints
$email = opensslDecrypt($token);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    handleApiError(401, 'Token不合法');
}
```

**Key Points:**
- Every API endpoint (except login/register) requires token validation
- Admin endpoints use `isAdmin($email)` check after token validation (see `admin_emails.php`)
- All user inputs must pass through `sanitizeInput()` function

### Carbon Calculation Engine
The core business logic resides in `calculate.php` with a comprehensive formula system:

```php
// Example calculation patterns
case '购物时自带袋子 / Bring your own bag when shopping':
    $carbonSavings = $dataInput * 0.0190;
    break;
case '种一棵树 / Plant a tree':
    $carbonSavings = $dataInput * 10.0000;
    break;
```

**Important:** Carbon factors are hardcoded in the switch statement. Any new activities require both backend formula updates and frontend UI changes in `calculate.html`.

### Database Transaction Patterns
Critical operations use explicit transaction management:

```php
$pdo->beginTransaction();
try {
    // Update user points
    $updateSql = "UPDATE users SET points = points + :points WHERE email = :email";
    // Log transaction
    $insertSql = "INSERT INTO points_transactions (...)";
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    logException($e);
}
```

### Error Handling System
**Never use standard PHP error handling.** All endpoints use the centralized system in `global_error_handler.php`:

```php
// For API errors
handleApiError(400, 'Error message');

// For exceptions
logException($e); // Logs to database and exits with JSON response
```

The system automatically logs all errors to the `error_logs` table with full context.

### Frontend-Backend Communication
API calls follow a consistent pattern using jQuery AJAX:

```javascript
// Standard API call pattern (js/utils.js)
$.ajax({
    url: 'endpoint.php',
    type: 'POST',
    data: { token: getToken(), /* other params */ },
    dataType: 'json',
    success: function(response) { /* handle success */ },
    error: function() { /* handle error */ }
});
```

**Critical:** All form submissions require Cloudflare Turnstile verification (`cf-turnstile-response` parameter).

### File Upload Handling
Image uploads follow strict validation in `calculate.php`:

```php
$allowedTypes = ['image/jpeg', 'image/png', 'image/tiff', 'image/heif', 'image/heic'];
$uploadDirectory = "uploads/";
$newFileName = $email . $dateTime . '.' . $fileExtension;
```

Files are renamed with user email + timestamp to prevent conflicts.

## Key Development Workflows

### Adding New API Endpoints
1. Include required headers: `global_variables.php`, `global_error_handler.php`, `db.php`
2. Set JSON content type: `header('Content-Type: application/json');`
3. Validate request method and token
4. Use transaction blocks for database operations
5. Return JSON responses via `echo json_encode()`

### Database Schema Updates
Run updates through `install.php` which contains the complete table structure. Key tables:
- `users`: Core user data with points tracking
- `points_transactions`: All carbon credit activities with approval status
- `products`: Point exchange items
- `messages`: Internal messaging system

### Frontend Theme System
The platform supports automatic dark/light mode detection:

```css
/* CSS Variables in index.html */
--ios-blue: #007AFF; /* Light mode */
body.auto-theme.dark-mode {
    --ios-blue: #0A84FF; /* Dark mode variant */
}
```

Components automatically adapt based on `body.auto-theme` class.

## Integration Points

### External Dependencies
- **Cloudflare Turnstile**: Anti-bot verification required for all forms
- **PHPMailer**: Email sending via Gmail SMTP (configured in `global_variables.php`)
- **Bootstrap 4.6.2**: UI framework loaded via CDN

### API Documentation
The system auto-generates OpenAPI specs via `generate_openapi.php`. When adding endpoints, ensure they follow the established patterns for automatic documentation.

### File Organization
- **Root**: Main API endpoints and HTML pages
- **js/**: Frontend utilities and navigation logic
- **css/**: Styling with iOS-inspired design system
- **src/**: PHPMailer library files
- **uploads/**: User-uploaded evidence images

## Common Pitfalls

1. **Never bypass token validation** - even internal endpoints require authentication
2. **Always use transactions** for multi-table operations
3. **Include Turnstile verification** in all user-facing forms
4. **Use `sanitizeInput()`** for all POST/GET parameters
5. **Follow the error handling pattern** - don't use custom error responses

## Testing Approach

The system logs all requests to `error_logs` table. Monitor this table when developing new features. Use the admin panel (`usermgmt.html`) to verify user data and point calculations.

For new carbon activities, test calculations manually before deploying as the formulas directly impact user point balances.