# Testing Guide for Affilify Tracking Module

This guide explains how to set up and test the Affilify Tracking module in a local Docker environment.

## Prerequisites

- Docker Desktop installed and running
- Magento Marketplace credentials (get from https://marketplace.magento.com/customer/accessKeys/)
- At least 8GB RAM available for Docker

## Quick Start

### 1. Start the Environment

```bash
# Make scripts executable
chmod +x bin/*

# Run the setup script (first time only)
./bin/setup
```

This will:
- Start all Docker containers (nginx, php, mariadb, redis, opensearch, rabbitmq, mailhog)
- Download Magento 2.4.7
- Install Magento with Italian locale
- Enable the Affilify_Tracking module
- Disable 2FA for development

### 2. Access Points

| Service | URL | Credentials |
|---------|-----|-------------|
| Frontend | http://localhost | - |
| Admin Panel | http://localhost/admin | admin / Admin123! |
| Mailhog | http://localhost:8025 | - |
| RabbitMQ | http://localhost:15672 | magento / magento |
| OpenSearch | http://localhost:9200 | - |

## Testing the Module

### Step 1: Configure the Module

1. Go to **Stores** > **Configuration** > **Affilify** > **Conversion Tracking**
2. Set the following:
   - **Enable Tracking**: Yes
   - **Tracking Domain**: `localhost:3000` (or your test API endpoint)
   - **URL Parameter Name**: `affilify_id`
   - **Cookie Duration**: 30 days
3. Save configuration
4. Flush cache: `./bin/cli cache:flush`

### Step 2: Create Test Data

```bash
# Enter the PHP container
./bin/bash

# Create a test product
php create_test_product.php

# Create a test customer
php create_customer.php

# Exit the container
exit
```

### Step 3: Start Queue Consumers

The module uses Magento's message queue for async processing. Start the consumers:

```bash
# In one terminal - Click consumer
docker-compose exec php bin/magento queue:consumers:start affilify.tracking.click.consumer &

# In another terminal - Conversion consumer  
docker-compose exec php bin/magento queue:consumers:start affilify.tracking.conversion.consumer &
```

Or run both in background:

```bash
docker-compose exec -d php bin/magento queue:consumers:start affilify.tracking.click.consumer
docker-compose exec -d php bin/magento queue:consumers:start affilify.tracking.conversion.consumer
```

### Step 4: Test Click Tracking

1. Open your browser and visit:
   ```
   http://localhost/?affilify_id=TEST123
   ```

2. Check that the cookie was set:
   - Open Developer Tools (F12)
   - Go to Application > Cookies > localhost
   - Look for `affilify_tracking` cookie with value `TEST123`

3. Check the log file:
   ```bash
   docker-compose exec php tail -f var/log/affilify_tracking.log
   ```

### Step 5: Test Conversion Tracking

1. Ensure you have the tracking cookie set (visit with `?affilify_id=TEST123`)

2. Add the test product to cart:
   ```
   http://localhost/catalog/product/view/id/1
   ```

3. Proceed to checkout with test customer:
   - Email: test@affilify.io
   - Password: Test123!

4. Complete the order

5. Check the logs:
   ```bash
   docker-compose exec php tail -f var/log/affilify_tracking.log
   ```

You should see entries like:
```
[2024-01-15 10:30:00] affilify_tracking.INFO: Conversion tracked successfully {"affilify_id":"TEST123","order_id":"100000001","checkout_total":"49.99"} []
```

## Mock API Server (Optional)

To test without a real Affilify backend, you can create a simple mock server:

### Using Node.js

Create `mock-api/server.js`:

```javascript
const http = require('http');

const server = http.createServer((req, res) => {
  let body = '';
  
  req.on('data', chunk => {
    body += chunk.toString();
  });
  
  req.on('end', () => {
    console.log(`${new Date().toISOString()} - ${req.method} ${req.url}`);
    console.log('Body:', body);
    console.log('---');
    
    res.writeHead(200, { 
      'Content-Type': 'application/json',
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'POST, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type'
    });
    
    if (req.method === 'OPTIONS') {
      res.end();
      return;
    }
    
    res.end(JSON.stringify({ success: true }));
  });
});

server.listen(3000, () => {
  console.log('Mock API server running on http://localhost:3000');
  console.log('Endpoints:');
  console.log('  POST /m/click  - Click tracking');
  console.log('  POST /m/conv   - Conversion tracking');
});
```

Run it:
```bash
node mock-api/server.js
```

Then configure the module with Tracking Domain: `host.docker.internal:3000`

## Debugging

### Check if Module is Enabled

```bash
./bin/cli module:status Affilify_Tracking
```

### Check Queue Status

```bash
# List all consumers
./bin/cli queue:consumers:list

# Check queue messages (MySQL)
docker-compose exec db mysql -u magento -pmagento magento -e "SELECT * FROM queue_message ORDER BY id DESC LIMIT 10;"
```

### View Logs

```bash
# Affilify tracking log
docker-compose exec php tail -f var/log/affilify_tracking.log

# Magento system log
docker-compose exec php tail -f var/log/system.log

# Magento exception log
docker-compose exec php tail -f var/log/exception.log
```

### Clear Cache

```bash
./bin/cli cache:flush
./bin/cli cache:clean
```

### Recompile DI

After modifying PHP files:
```bash
./bin/cli setup:di:compile
```

## Troubleshooting

### "Tracking domain not configured"

The module requires a tracking domain to send API requests. Configure it in:
**Stores** > **Configuration** > **Affilify** > **Conversion Tracking** > **Tracking Domain**

### Cookie not being set

1. Check that the module is enabled in admin
2. Verify the URL parameter name matches your configuration
3. Check browser console for errors
4. Ensure cookies are not blocked by browser

### Queue messages not processing

1. Verify consumers are running:
   ```bash
   ps aux | grep queue:consumers
   ```

2. Start consumers manually:
   ```bash
   ./bin/cli queue:consumers:start affilify.tracking.click.consumer
   ```

3. Check for errors in logs

### API calls failing

1. Check the tracking domain is accessible from the PHP container:
   ```bash
   docker-compose exec php curl -X POST https://your-tracking-domain.com/m/click -H "Content-Type: application/json" -d '{"test":true}'
   ```

2. Check SSL certificates if using HTTPS

3. Review the log file for detailed error messages

## Useful Commands

```bash
# Start environment
./bin/start

# Stop environment
./bin/stop

# Enter PHP container
./bin/bash

# Run Magento CLI
./bin/cli <command>

# View logs
docker-compose logs -f php

# Restart all services
docker-compose restart

# Rebuild PHP container (after Dockerfile changes)
docker-compose build php
docker-compose up -d
```

## Clean Up

To completely remove the environment:

```bash
# Stop and remove containers, networks, volumes
docker-compose down -v

# Remove Magento source (if you want to start fresh)
rm -rf src/*
```
