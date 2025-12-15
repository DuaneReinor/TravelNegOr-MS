# CSRF Token Fix for Ngrok Deployment

This guide explains the CSRF token issues when using ngrok and how to fix them in your Travel NegOr application.

## Problem Overview

When deploying your Symfony application via ngrok, you may encounter CSRF token validation failures due to:

1. **Domain Changes**: ngrok creates a new domain (e.g., `abc123.ngrok.io`) which can break session/CSRF token validation
2. **Session Issues**: Session cookies may not be properly shared across the ngrok tunnel
3. **Token Expiration**: Ngrok connections can be unstable, causing token validation to fail unexpectedly
4. **Referrer/Origin Headers**: Security headers may not match expected values

## Solution Components

### 1. Enhanced CSRF Configuration

**File**: `config/packages/csrf.yaml`

```yaml
# CSRF Protection Configuration optimized for ngrok deployment
framework:
    form:
        csrf_protection:
            token_id: submit
            # Increase token lifetime for ngrok sessions
            token_storage: session

    csrf_protection:
        # Use stateless tokens for better ngrok compatibility
        stateless_token_ids:
            - submit
            - authenticate
            - logout
        # Increase token lifetime to handle ngrok connection issues
        token_ttl: 3600  # 1 hour (default is usually 30 minutes)

# Session configuration for ngrok compatibility
session:
    handler_id: null
    cookie_secure: auto
    cookie_samesite: lax
    # Increase session lifetime for ngrok
    cookie_lifetime: 3600  # 1 hour
    gc_maxlifetime: 3600   # 1 hour
```

### 2. Enhanced Session Configuration

**File**: `config/packages/framework.yaml`

Enhanced session settings with longer lifetimes and proper ngrok compatibility:

- Extended cookie lifetime (1 hour instead of default 30 minutes)
- Proper session storage configuration
- Environment-specific settings

### 3. Custom CSRF Token Manager

**File**: `src/Service/CsrfTokenManager.php`

Features:
- Token regeneration capabilities
- Token validation with metadata tracking
- Automatic cleanup of expired tokens
- Ngrok-specific compatibility checks

### 4. Twig Extensions

**File**: `src/Twig/CsrfExtension.php`

New Twig functions:
- `csrf_token_ngrok()` - Generate ngrok-compatible tokens
- `csrf_token_refresh()` - Refresh tokens via AJAX
- `csrf_debug_info()` - Debug information for troubleshooting

### 5. CSRF Controller

**File**: `src/Controller/CsrfTokenController.php`

Endpoints for:
- `/admin/csrf/refresh/{tokenId}` - Refresh CSRF tokens
- `/admin/csrf/validate/{tokenId}` - Validate tokens
- `/admin/csrf/debug/{tokenId}` - Debug information
- `/admin/csrf/cleanup` - Clean expired tokens
- `/admin/csrf/test` - Test CSRF functionality

### 6. Enhanced Form Templates

**File**: `templates/admin/shared/csrf_enhanced_form.html.twig`

Features:
- Automatic token refresh
- Real-time validation
- Error handling and user feedback
- Debug information display
- Mobile-friendly interface

## Usage Instructions

### 1. Using the Enhanced Form Template

Replace your existing form templates with the enhanced version:

```twig
{% import "admin/shared/csrf_enhanced_form.html.twig" as csrf_form %}

{{ csrf_form.render_enhanced_form(form, {
    'csrf_token_id': 'submit',
    'auto_refresh': true,
    'show_debug': app.environment == 'dev',
    'error_handling': true
}) }}
```

### 2. Manual CSRF Token Management

In your controllers, you can use the CSRF service directly:

```php
use App\Service\CsrfTokenManager;

public function __construct(
    private CsrfTokenManager $csrfTokenManager
) {
}

// Refresh token if needed
$token = $this->csrfTokenManager->refreshTokenIfNeeded('submit');

// Validate token
$isValid = $this->csrfTokenManager->checkTokenValidity('submit', $token);
```

### 3. Using Twig Functions

```twig
{# Generate a new CSRF token #}
{{ csrf_token_ngrok('submit') }}

{# Refresh an existing token #}
{{ csrf_token_refresh('submit') }}

{# Show debug information (development only) #}
{{ csrf_debug_info('submit') }}
```

### 4. AJAX Token Refresh

```javascript
// Refresh CSRF token via JavaScript
async function refreshToken() {
    const response = await fetch('/admin/csrf/refresh/submit', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    });
    
    const result = await response.json();
    if (result.success) {
        // Update token in your forms
        updateTokenInForms(result.token);
    }
}
```

## Testing CSRF Functionality

### 1. Access the Test Page

Visit: `https://your-ngrok-domain.ngrok.io/admin/csrf/test`

This page provides:
- Automated CSRF token tests
- Manual testing tools
- Session information display
- Debug output

### 2. Test Results

The test page will show results for:
- Token generation
- Token validation
- Token refresh
- Active token management
- Cleanup operations

### 3. Debug Information

In development mode, you can enable debug information:

```yaml
# In your controller
$formConfig = [
    'show_debug' => $this->getParameter('kernel.environment') === 'dev'
];
```

## Troubleshooting

### Common Issues and Solutions

#### 1. "CSRF token is invalid" Error

**Symptoms**: Form submissions fail with CSRF validation errors

**Solutions**:
- Clear your browser cache and cookies
- Check if ngrok tunnel is stable
- Enable auto-refresh in form configuration
- Check session configuration

#### 2. Session Not Persistent

**Symptoms**: User gets logged out frequently or loses form data

**Solutions**:
- Verify session configuration in `framework.yaml`
- Check ngrok connection stability
- Increase session lifetime
- Clear old session files

#### 3. Token Validation Fails Intermittently

**Symptoms**: Random CSRF failures even with valid tokens

**Solutions**:
- Enable token refresh functionality
- Check for timing issues
- Verify ngrok tunnel stability
- Use stateless tokens

### Debug Commands

#### Clear All Sessions
```bash
php bin/console cache:clear
rm -rf var/sessions/*
```

#### Check Session Configuration
```bash
php bin/console debug:config framework session
```

#### Test CSRF Functionality
```bash
php bin/console app:test-csrf-functionality
```

### Environment-Specific Settings

#### Development (`.env.local`)
```env
SESSION_LIFETIME=3600
CSRF_TOKEN_TTL=3600
ENABLE_CSRF_DEBUG=true
```

#### Production (`.env.prod`)
```env
SESSION_LIFETIME=1800
CSRF_TOKEN_TTL=1800
ENABLE_CSRF_DEBUG=false
```

## Best Practices for Ngrok Deployment

### 1. Session Management
- Use longer session lifetimes (1+ hours)
- Implement proper session cleanup
- Monitor session storage usage

### 2. CSRF Token Handling
- Enable automatic token refresh
- Use stateless tokens when possible
- Implement graceful error handling

### 3. Network Stability
- Use stable ngrok paid plans for production testing
- Implement retry mechanisms for failed requests
- Monitor network connectivity

### 4. Security Considerations
- Don't disable CSRF protection entirely
- Use HTTPS with ngrok when possible
- Monitor for unusual CSRF patterns
- Implement rate limiting if needed

## Migration from Existing Implementation

### Step 1: Update Configuration
1. Replace your current `csrf.yaml` configuration
2. Update `framework.yaml` session settings
3. Clear cache: `php bin/console cache:clear`

### Step 2: Install New Services
1. The new services are auto-registered
2. No additional installation required
3. Test with: `php bin/console debug:container CsrfTokenManager`

### Step 3: Update Templates
1. Replace existing form templates with enhanced versions
2. Add CSRF debug information (development only)
3. Test form submissions

### Step 4: Test Functionality
1. Access `/admin/csrf/test`
2. Run all automated tests
3. Test manual form submissions
4. Verify session persistence

## Monitoring and Maintenance

### 1. Regular Cleanup
- Implement automated token cleanup
- Monitor session storage usage
- Clean expired sessions regularly

### 2. Performance Monitoring
- Track CSRF validation success rates
- Monitor session creation/destruction
- Watch for unusual patterns

### 3. Error Logging
- Log CSRF validation failures
- Monitor ngrok connection issues
- Track session-related errors

## Additional Resources

- [Symfony CSRF Documentation](https://symfony.com/doc/current/security/csrf.html)
- [Ngrok Documentation](https://ngrok.com/docs)
- [Session Management Best Practices](https://symfony.com/doc/current/session.html)

## Support

If you encounter issues:

1. Check the test page at `/admin/csrf/test`
2. Review debug information in development mode
3. Check application logs for CSRF-related errors
4. Verify ngrok tunnel stability
5. Test with different browsers and incognito modes

For additional support, refer to the troubleshooting section above or check the Symfony and ngrok documentation.