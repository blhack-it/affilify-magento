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

- Magento 2.4.0 or higher
- PHP 7.4 or higher

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
2. Download and extract the module files to the directory
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
| API Key | Your Affilify API Key (from Advertiser > API tab) | - |
| API URL | Tracking API URL | https://dashboard.affilify.it/api/track |
| URL Parameter Name | The URL parameter to capture | affilify_id |
| Cookie Duration | Days to keep the tracking cookie | 30 |
| Debug Mode | Enable verbose logging | No |

## How It Works

### Click Tracking

1. A visitor arrives with an affiliate link: `https://yourstore.com/?affilify_id=abc123`
2. The module captures the `affilify_id` parameter
3. The value is validated (alphanumeric, dash, underscore only, max 100 chars)
4. A cookie is set with the affiliate ID
5. Click data is sent to the Affilify API

### Conversion Tracking

1. Customer completes checkout
2. The `checkout_onepage_controller_success_action` event fires
3. If a tracking cookie exists, conversion data is captured
4. Conversion is sent to the Affilify API with order details
5. The tracking cookie is deleted (single conversion per click)

## Message Queue

The module uses MySQL message queues for async processing:

- **Topic**: `affilify.tracking.click` - Click events
- **Topic**: `affilify.tracking.conversion` - Conversion events

### Running Queue Consumers

Magento's cron automatically processes queue consumers. Make sure cron is running:

```bash
# Verify cron is configured
bin/magento cron:run
```

For manual testing or dedicated consumer processes:

```bash
# Run click consumer manually
bin/magento queue:consumers:start affilify.tracking.click

# Run conversion consumer manually
bin/magento queue:consumers:start affilify.tracking.conversion
```

## Logs

Debug logs are written to: `var/log/affilify_tracking.log`

## Support

- Issues: https://github.com/blhack-it/affilify-magento/issues
- Documentation: https://affilify.it/docs

## License

MIT License - see [LICENSE](LICENSE) for details.
