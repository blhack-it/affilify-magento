# Affilify Tracking Module for Magento 2

A Magento 2 module for tracking affiliate conversions via the Affilify platform. This module captures URL tracking parameters, stores them in cookies, and sends click and conversion events to your custom Affilify tracking domain.

## Features

- **URL Parameter Capture**: Automatically captures affiliate tracking parameters from URLs
- **Cookie-Based Tracking**: Stores tracking data in cookies with configurable duration (last-touch attribution)
- **Async Queue Processing**: Uses Magento's message queue system for non-blocking API calls
- **Automatic Retry**: Failed API calls are retried up to 3 times
- **Custom Logging**: All tracking events are logged to `var/log/affilify_tracking.log`
- **Silent Fail**: Tracking errors don't affect checkout flow
- **Admin Configuration**: Easy setup via Magento admin panel

## Requirements

- Magento 2.4.x
- PHP 8.1 or higher
- MySQL (for message queue)

## Quick Start with Docker (Development)

The easiest way to set up a development environment is using the included Docker configuration.

### Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/)

### Start the Environment

```bash
# Start all services
./bin/start

# Or manually
docker-compose up -d
```

### First Time Setup

```bash
# Run the setup script (downloads Magento, installs dependencies, configures everything)
./bin/setup
```

This will:
- Download Magento 2.4.8-p3
- Install all dependencies
- Configure the database, Redis, OpenSearch, RabbitMQ
- Enable the Affilify_Tracking module
- Create admin user: `admin` / `Admin123!`

### Access Points

| Service | URL |
|---------|-----|
| **Magento Store** | http://localhost |
| **Admin Panel** | http://localhost/admin_lz8kcyq |
| **Mailhog** | http://localhost:8025 |
| **RabbitMQ** | http://localhost:15672 |

### Useful Commands

```bash
# Start environment
./bin/start

# Stop environment
./bin/stop

# Run Magento CLI commands
./bin/cli cache:flush
./bin/cli indexer:reindex

# Access PHP container shell
./bin/bash
```

## Installation (Production)

### Via Composer (Recommended)

```bash
composer require affilify/module-tracking
bin/magento module:enable Affilify_Tracking
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:clean
```

### Manual Installation

1. Create the directory structure:
   ```bash
   mkdir -p app/code/Affilify/Tracking
   ```

2. Copy the module files to `app/code/Affilify/Tracking/`

3. Enable the module and run setup:
   ```bash
   bin/magento module:enable Affilify_Tracking
   bin/magento setup:upgrade
   bin/magento setup:di:compile
   bin/magento cache:clean
   ```

## Configuration

### Admin Panel Configuration

1. Navigate to **Stores** > **Configuration** > **Affilify** > **Conversion Tracking**

2. Configure the following settings:

   | Setting | Description | Default |
   |---------|-------------|---------|
   | **Enable Tracking** | Enable/disable the tracking module | Yes |
   | **Tracking Domain** | Your custom CNAME tracking domain (e.g., `https://t.yourdomain.com`) | - |
   | **URL Parameter Name** | The URL parameter to capture (e.g., `affilify_id`, `ref`) | `affilify_id` |
   | **Cookie Duration (Days)** | How long to keep the tracking cookie | 30 |

### CLI Configuration (Development)

```bash
# Enable tracking
bin/magento config:set affilify_tracking/general/enabled 1

# Set tracking domain
bin/magento config:set affilify_tracking/general/tracking_domain "https://t.yourdomain.com"

# Set parameter name
bin/magento config:set affilify_tracking/general/parameter_name "affilify_id"

# Set cookie duration (days)
bin/magento config:set affilify_tracking/general/cookie_duration 30

# Flush cache
bin/magento cache:flush
```

### Tracking Domain Setup

Your tracking domain must be configured as a CNAME pointing to `t.affilify.it`:

```
t.yourdomain.com CNAME t.affilify.it
```

Contact Affilify support to register your tracking domain in the system.

## Queue Consumer Setup

The module uses Magento's message queue system for async processing. You need to run the queue consumers for tracking to work.

### Option 1: Cron (Recommended for Production)

Add the following to your crontab:

```bash
* * * * * cd /path/to/magento && bin/magento cron:run
```

Magento's cron will automatically process queue messages via the `cron_consumers_runner`.

### Option 2: Manual Consumer Start

For development or debugging, you can start the consumers manually:

```bash
# Start click tracking consumer
bin/magento queue:consumers:start affilify.tracking.click.consumer

# Start conversion tracking consumer
bin/magento queue:consumers:start affilify.tracking.conversion.consumer
```

### Option 3: Supervisor (Recommended for High Volume)

Create a supervisor configuration for each consumer:

```ini
[program:affilify_click_consumer]
command=/usr/bin/php /path/to/magento/bin/magento queue:consumers:start affilify.tracking.click.consumer
directory=/path/to/magento
autostart=true
autorestart=true
user=www-data
numprocs=1
stdout_logfile=/var/log/supervisor/affilify_click.log
stderr_logfile=/var/log/supervisor/affilify_click_error.log

[program:affilify_conversion_consumer]
command=/usr/bin/php /path/to/magento/bin/magento queue:consumers:start affilify.tracking.conversion.consumer
directory=/path/to/magento
autostart=true
autorestart=true
user=www-data
numprocs=1
stdout_logfile=/var/log/supervisor/affilify_conversion.log
stderr_logfile=/var/log/supervisor/affilify_conversion_error.log
```

## How It Works

### Click Tracking Flow

1. Visitor lands on your site with a tracking parameter (e.g., `?affilify_id=ABC123`)
2. The module captures the parameter and stores it in a cookie
3. A click event is published to the message queue
4. The queue consumer sends the click data to `https://your-tracking-domain.com/m/click`

### Conversion Tracking Flow

1. Visitor completes checkout
2. The module reads the tracking cookie
3. A conversion event is published to the queue with order details
4. The queue consumer sends the conversion data to `https://your-tracking-domain.com/m/conv`
5. The tracking cookie is deleted

### API Payload Format

**Click Event:**
```json
{
  "affilify_id": "ABC123",
  "referer": "https://referrer-site.com/page",
  "ip": "192.168.1.1",
  "user_agent": "Mozilla/5.0...",
  "timestamp": "2024-01-15 10:30:00",
  "platform": "magento"
}
```

**Conversion Event:**
```json
{
  "affilify_id": "ABC123",
  "order_id": "100000123",
  "checkout_total": "149.99",
  "referer": "-",
  "timestamp": "2024-01-15 10:35:00",
  "platform": "magento"
}
```

## Troubleshooting

### Check Logs

All tracking events and errors are logged to:
```
var/log/affilify_tracking.log
```

### Common Issues

1. **Tracking not working**
   - Verify the module is enabled in admin
   - Check that the tracking domain is configured
   - Ensure queue consumers are running

2. **Clicks tracked but no conversions**
   - Verify the cookie is being set (check browser dev tools)
   - Check that the checkout success event is firing
   - Review the log file for errors

3. **Queue messages not processing**
   - Run `bin/magento queue:consumers:list` to verify consumers are registered
   - Start consumers manually to check for errors
   - Verify cron is running if using cron-based processing

4. **API errors in logs**
   - Verify your tracking domain is properly configured
   - Check DNS CNAME record is pointing to `t.affilify.it`
   - Contact Affilify support to verify your domain is registered

### Debug Mode

To enable more verbose logging, you can modify the logger level in `etc/di.xml`:

```xml
<virtualType name="AffilfyTrackingLoggerHandler" type="Affilify\Tracking\Logger\Handler">
    <arguments>
        <argument name="loggerType" xsi:type="number">100</argument> <!-- DEBUG level -->
    </arguments>
</virtualType>
```

## Uninstallation

```bash
bin/magento module:disable Affilify_Tracking
bin/magento setup:upgrade
composer remove affilify/module-tracking
```

## Support

For support, please contact:
- Email: support@affilify.it
- Website: https://affilify.it

## License

This module is licensed under the MIT License. See [LICENSE](LICENSE) for details.

## Changelog

### 1.0.0
- Initial release
- URL parameter capture with configurable parameter name
- Cookie-based tracking with configurable duration
- Async click and conversion tracking via message queue
- 3 retry attempts for failed API calls
- Custom logging to `affilify_tracking.log`
- Admin configuration panel
- Docker development environment included
