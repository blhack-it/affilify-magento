# Affilify Tracking Module for Magento 2

A Magento 2 module for affiliate conversion tracking that integrates with the Affilify platform.

## Features

- **Click Tracking**: Captures affiliate IDs from URL parameters and stores them in cookies
- **Conversion Tracking**: Automatically tracks conversions on checkout success
- **Async Processing**: Uses MySQL message queues for non-blocking API calls
- **Multi-Store Support**: Configurable per store view
- **GDPR Compliant**: IP addresses are masked before sending to tracking API
- **Retry Logic**: Automatic retry with exponential backoff for failed API calls
- **Debug Mode**: Verbose logging for troubleshooting

## Requirements

- Magento 2.4.7 or higher
- PHP 8.1 or higher

## Installation

### Via Composer (Recommended)

```bash
composer require affilify/module-tracking
bin/magento module:enable Affilify_Tracking
bin/magento setup:upgrade
bin/magento cache:clean
```

### Manual Installation

1. Create directory: `app/code/Affilify/Tracking`
2. Copy module files to the directory
3. Run:
```bash
bin/magento module:enable Affilify_Tracking
bin/magento setup:upgrade
bin/magento cache:clean
```

## Configuration

Navigate to **Stores > Configuration > Affilify > Conversion Tracking**

### General Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Enable Tracking | Enable/disable the module | Yes |
| Tracking Domain | Your custom tracking domain (e.g., t.yourdomain.com) | - |
| URL Parameter Name | The URL parameter to capture | affilify_id |
| Cookie Duration | Days to keep the tracking cookie | 30 |
| Debug Mode | Enable verbose logging | No |

## How It Works

### Click Tracking

1. A visitor arrives with an affiliate link: `https://yourstore.com/?affilify_id=abc123`
2. The module captures the `affilify_id` parameter
3. The value is validated (alphanumeric, dash, underscore only, max 100 chars)
4. A cookie is set with the affiliate ID
5. Click data is queued for async processing
6. The queue consumer sends the click to your tracking API

### Conversion Tracking

1. Customer completes checkout
2. The `checkout_onepage_controller_success_action` event fires
3. If a tracking cookie exists, conversion data is captured
4. Conversion is queued for async processing
5. The queue consumer sends the conversion to your tracking API
6. The tracking cookie is deleted (single conversion per click)

## Message Queue

The module uses MySQL message queues for async processing:

- **Topic**: `affilify.tracking.click` - Click events
- **Topic**: `affilify.tracking.conversion` - Conversion events

### Running Queue Consumers

```bash
# Run click consumer
bin/magento queue:consumers:start affilify.tracking.click

# Run conversion consumer
bin/magento queue:consumers:start affilify.tracking.conversion

# Run all consumers (production)
bin/magento cron:run
```

## API Endpoints

The module sends data to your Affilify API:

### Click Endpoint: `POST /api/track/click`

```json
{
  "affilify_id": "abc123",
  "referer": "https://google.com",
  "ip": "192.168.x.x",
  "user_agent": "Mozilla/5.0...",
  "timestamp": "2024-01-15T10:30:00+00:00",
  "platform": "magento"
}
```

### Conversion Endpoint: `POST /api/track/conversion`

```json
{
  "affilify_id": "abc123",
  "order_id": "000000123",
  "amount": "199.99",
  "currency": "USD",
  "platform": "magento"
}
```

## Logging

Logs are written to: `var/log/affilify_tracking.log`

Enable Debug Mode in configuration for verbose logging.

## Testing

### Unit Tests

```bash
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/Affilify/Tracking/Test/Unit
```

### Manual Testing

1. Visit your store with an affiliate parameter: `?affilify_id=test123`
2. Check that the `affilify_tracking` cookie is set
3. Complete a checkout
4. Check the logs and/or your tracking API

## Security

- **Input Validation**: Affiliate IDs are validated with strict regex pattern
- **IP Masking**: IP addresses are masked (192.168.x.x format) for GDPR compliance
- **XSS Prevention**: All output is properly escaped
- **SQL Injection Prevention**: Uses Magento's parameter binding

## Troubleshooting

### Cookie Not Being Set

1. Check that the module is enabled in configuration
2. Verify the URL parameter name matches your link
3. Check `var/log/affilify_tracking.log` for errors

### Conversion Not Tracking

1. Verify the tracking cookie exists before checkout
2. Check that the tracking domain is configured
3. Enable debug mode and check logs
4. Run queue consumers: `bin/magento queue:consumers:start affilify.tracking.conversion`

### API Calls Failing

1. Verify your tracking domain is accessible
2. Check for network/firewall issues
3. The module retries failed calls up to 3 times with exponential backoff

## License

MIT License

## Support

For support, please contact support@affilify.it
