# Subscriber Management System

## Overview

The FinAegis platform includes a comprehensive subscriber management system that handles email subscriptions, newsletter delivery, and marketing communications. The system is integrated with Amazon SES for reliable email delivery and includes features for segmentation, bulk sending, and compliance with email regulations.

## Features

### Core Functionality
- **Email Collection**: Capture subscriber emails from multiple touchpoints (blog, CGO page, investment page, footer)
- **Tag Management**: Organize subscribers with tags for targeted campaigns
- **Preference Management**: Store and respect subscriber communication preferences
- **Unsubscribe Handling**: One-click unsubscribe with compliance tracking
- **Bulk Email Sending**: Send newsletters to segmented subscriber lists
- **Queue Support**: Asynchronous email delivery for better performance
- **Amazon SES Integration**: Enterprise-grade email delivery infrastructure

### Admin Features
- **Filament Resource**: Complete admin interface for subscriber management
- **Advanced Filtering**: Filter by status, source, tags, and date ranges
- **Bulk Actions**: Add/remove tags, export, and delete in bulk
- **Dashboard Widgets**: Real-time statistics and growth charts
- **Activity Tracking**: Monitor subscribe/unsubscribe events

## Technical Implementation

### Database Schema

```php
Schema::create('subscribers', function (Blueprint $table) {
    $table->id();
    $table->string('email')->unique();
    $table->string('source')->index(); // blog, cgo, investment, footer
    $table->string('status')->default('active')->index(); // active, unsubscribed, bounced
    $table->json('preferences')->nullable(); // email preferences
    $table->json('tags')->nullable(); // for segmentation
    $table->string('unsubscribe_token')->unique();
    $table->timestamp('unsubscribed_at')->nullable();
    $table->timestamps();
    
    $table->index(['status', 'created_at']);
});
```

### Models

#### Subscriber Model
Located at `app/Models/Subscriber.php`

Key methods:
- `addTags(array $tags)`: Add tags to subscriber
- `removeTags(array $tags)`: Remove tags from subscriber
- `hasTags(array $tags)`: Check if subscriber has specific tags
- `unsubscribe()`: Mark subscriber as unsubscribed
- `resubscribe()`: Reactivate unsubscribed subscriber
- `scopeActive($query)`: Query only active subscribers
- `scopeWithTags($query, array $tags)`: Query subscribers with specific tags

### Services

#### SubscriberEmailService
Located at `app/Services/Email/SubscriberEmailService.php`

Key methods:
- `subscribe(string $email, string $source, array $tags = [])`: Add new subscriber
- `sendWelcomeEmail(Subscriber $subscriber)`: Send welcome email
- `sendNewsletter(string $subject, string $content, array $tags = [], ?string $source = null)`: Send bulk newsletter
- `unsubscribe(string $email)`: Handle unsubscribe request

### API Endpoints

#### Subscribe
```http
POST /api/subscribers/subscribe
Content-Type: application/json

{
    "email": "user@example.com",
    "source": "blog",
    "tags": ["newsletter", "updates"]
}
```

#### Unsubscribe
```http
POST /api/subscribers/unsubscribe
Content-Type: application/json

{
    "email": "user@example.com"
}
```

#### Get Subscriber (Admin)
```http
GET /api/admin/subscribers/{email}
Authorization: Bearer {token}
```

### Web Routes

- `/unsubscribe/{token}` - Unsubscribe page with token verification
- `/subscribe/success` - Success page after subscription
- `/subscribe/already` - Page shown when email already subscribed

### Artisan Commands

#### Send Newsletter
```bash
php artisan subscribers:send-newsletter
```

Options:
- `--subject`: Email subject (required)
- `--content`: Email content or path to blade template (required)
- `--tags`: Comma-separated tags to filter recipients
- `--source`: Filter by subscription source
- `--test`: Send test email to admin only

Example:
```bash
php artisan subscribers:send-newsletter \
    --subject="FinAegis Monthly Update" \
    --content="emails.newsletter.monthly" \
    --tags="active,interested" \
    --source="blog"
```

## Configuration

### Environment Variables

```env
# Amazon SES Configuration
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=eu-west-1
AWS_SES_REGION=eu-west-1

# Email Settings
MAIL_MAILER=ses
MAIL_FROM_ADDRESS=noreply@finaegis.com
MAIL_FROM_NAME="FinAegis Platform"

# Queue Configuration (for async email delivery)
QUEUE_CONNECTION=redis
```

### Config Files

#### config/mail.php
```php
'ses' => [
    'transport' => 'ses',
],
```

#### config/services.php
```php
'ses' => [
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_SES_REGION', 'eu-west-1'),
],
```

## Email Templates

### Welcome Email
Located at `resources/views/emails/subscriber/welcome.blade.php`

Features:
- Personalized greeting
- Platform introduction
- Quick links to key features
- Unsubscribe link

### Newsletter Template
Located at `resources/views/emails/subscriber/newsletter.blade.php`

Features:
- Dynamic content injection
- Responsive design
- Social media links
- Unsubscribe footer

## Integration Points

### Blog Newsletter Form
Located in `resources/views/components/blog/newsletter-form.blade.php`

```blade
<x-blog.newsletter-form 
    source="blog" 
    tags="['blog-subscriber']" 
/>
```

### CGO Early Access Form
Located in `resources/views/livewire/cgo-subscription-form.blade.php`

Features:
- Livewire integration for real-time validation
- Investment amount capture
- Automatic tagging with 'cgo-early-access'

### Footer Newsletter
Located in `resources/views/components/footer-newsletter.blade.php`

Simple email capture with 'footer' source tracking.

## Admin Interface

### Filament Resource
Located at `app/Filament/Admin/Resources/SubscriberResource.php`

Features:
- **List View**: Sortable columns, search, advanced filters
- **Create/Edit**: Full subscriber management
- **Bulk Actions**: 
  - Add tags to multiple subscribers
  - Remove tags from selection
  - Export to CSV
  - Bulk delete
- **Widgets**:
  - Total subscribers count
  - Growth chart (last 30 days)
  - Source distribution
  - Recent activity

### Filters
- Status (Active, Unsubscribed, Bounced)
- Source (Blog, CGO, Investment, Footer)
- Tags (Multi-select)
- Date range (Created, Unsubscribed)

## Security Considerations

### Data Protection
- Email addresses are stored with encryption at rest
- Unsubscribe tokens are cryptographically secure
- No passwords are stored (email-only system)

### Compliance
- GDPR compliant with clear consent capture
- CAN-SPAM compliant with unsubscribe links
- Double opt-in available (configurable)
- Data retention policies implemented

### Rate Limiting
- API endpoints are rate-limited
- Bulk sending respects SES sending limits
- Queue throttling prevents overwhelming

## Testing

### Unit Tests
Located at `tests/Unit/Models/SubscriberTest.php`

Tests model methods and scopes.

### Feature Tests
Located at `tests/Feature/SubscriberManagementTest.php`

Tests:
- Subscription flow
- Unsubscribe process
- Email delivery
- API endpoints
- Admin operations

### Running Tests
```bash
./vendor/bin/pest --filter=Subscriber
```

## Monitoring

### Metrics to Track
- Subscription rate by source
- Unsubscribe rate
- Email open rates (via SES)
- Bounce rates
- Growth trends

### Logs
- All subscription events are logged
- Failed email deliveries are tracked
- Unsubscribe reasons can be captured

## Future Enhancements

### Planned Features
1. **Segmentation UI**: Visual segment builder in admin
2. **A/B Testing**: Test different email content
3. **Automation**: Trigger-based email sequences
4. **Analytics Dashboard**: Detailed email performance metrics
5. **Preference Center**: Allow subscribers to manage preferences
6. **Double Opt-in**: Confirmation email flow
7. **Webhooks**: SES event processing (opens, clicks, bounces)
8. **Templates Library**: Pre-built email templates

### Integration Opportunities
1. **CRM Integration**: Sync with external CRM systems
2. **Marketing Automation**: Connect with tools like HubSpot
3. **Event Tracking**: Integration with analytics platforms
4. **Custom Fields**: Extended subscriber profiles
5. **API Extensions**: Webhook notifications for subscribe/unsubscribe

## Troubleshooting

### Common Issues

#### Emails Not Sending
1. Check SES configuration in `.env`
2. Verify SES is in production mode (not sandbox)
3. Check queue workers are running: `php artisan queue:work`
4. Review failed jobs: `php artisan queue:failed`

#### High Bounce Rate
1. Implement email verification on signup
2. Clean old/inactive subscribers
3. Monitor SES reputation dashboard
4. Implement double opt-in

#### Slow Bulk Sending
1. Increase queue workers
2. Optimize query with proper indexing
3. Use queue priorities for urgent emails
4. Consider SES sending rate limits

### Debug Commands
```bash
# Test email configuration
php artisan tinker
Mail::raw('Test email', function($message) {
    $message->to('test@example.com')->subject('Test');
});

# Process failed jobs
php artisan queue:retry all

# Clear email queues
php artisan queue:clear
```