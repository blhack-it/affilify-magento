/**
 * Mock API Server for testing Affilify Tracking Module
 *
 * Run with: node mock-api/server.js
 *
 * Configure Magento module with Tracking Domain: host.docker.internal:3000
 */

const https = require('https');
const fs = require('fs');
const path = require('path');

const PORT = 3000;

// Store received events for inspection
const events = {
  clicks: [],
  conversions: []
};

// SSL options - use self-signed cert
const sslOptions = {
  key: fs.readFileSync(path.join(__dirname, 'certs', 'server.key')),
  cert: fs.readFileSync(path.join(__dirname, 'certs', 'server.crt'))
};

const server = https.createServer(sslOptions, (req, res) => {
  // Handle CORS preflight
  if (req.method === 'OPTIONS') {
    res.writeHead(200, {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'POST, GET, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type, Accept',
      'Access-Control-Max-Age': '86400'
    });
    res.end();
    return;
  }

  let body = '';

  req.on('data', chunk => {
    body += chunk.toString();
  });

  req.on('end', () => {
    const timestamp = new Date().toISOString();

    // Parse JSON body
    let data = {};
    try {
      if (body) {
        data = JSON.parse(body);
      }
    } catch (e) {
      console.log(`[${timestamp}] ⚠️  Invalid JSON: ${body}`);
    }

    // Log the request
    console.log(`\n${'='.repeat(60)}`);
    console.log(`[${timestamp}] ${req.method} ${req.url}`);
    console.log(`Headers: ${JSON.stringify(req.headers, null, 2)}`);

    // Handle different endpoints
    if (req.url === '/click' && req.method === 'POST') {
      console.log(`\n🖱️  CLICK EVENT RECEIVED`);
      console.log(`   Affilify ID: ${data.affilify_id || 'N/A'}`);
      console.log(`   Referer: ${data.referer || 'N/A'}`);
      console.log(`   IP: ${data.ip || 'N/A'}`);
      console.log(`   User Agent: ${data.user_agent || 'N/A'}`);
      console.log(`   Platform: ${data.platform || 'N/A'}`);
      console.log(`   Timestamp: ${data.timestamp || 'N/A'}`)

      events.clicks.push({ ...data, received_at: timestamp });

      res.writeHead(200, {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*'
      });
      res.end(JSON.stringify({
        success: true,
        message: 'Click tracked',
        event_id: events.clicks.length
      }));

    } else if (req.url === '/conversion' && req.method === 'POST') {
      console.log(`\n💰 CONVERSION EVENT RECEIVED`);
      console.log(`   Affilify ID: ${data.affilify_id || 'N/A'}`);
      console.log(`   Order ID: ${data.order_id || 'N/A'}`);
      console.log(`   Amount: €${data.amount || 'N/A'}`)
      console.log(`   Referer: ${data.referer || 'N/A'}`);
      console.log(`   Platform: ${data.platform || 'N/A'}`);
      console.log(`   Timestamp: ${data.timestamp || 'N/A'}`);

      events.conversions.push({ ...data, received_at: timestamp });

      res.writeHead(200, {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*'
      });
      res.end(JSON.stringify({
        success: true,
        message: 'Conversion tracked',
        event_id: events.conversions.length
      }));

    } else if (req.url === '/events' && req.method === 'GET') {
      // Endpoint to view all received events
      console.log(`\n📊 Events requested`);

      res.writeHead(200, {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*'
      });
      res.end(JSON.stringify(events, null, 2));

    } else if (req.url === '/health' && req.method === 'GET') {
      res.writeHead(200, {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*'
      });
      res.end(JSON.stringify({ status: 'ok', timestamp }));

    } else {
      console.log(`\n❓ Unknown endpoint: ${req.method} ${req.url}`);
      console.log(`   Body: ${body}`);

      res.writeHead(404, {
        'Content-Type': 'application/json',
        'Access-Control-Allow-Origin': '*'
      });
      res.end(JSON.stringify({
        error: 'Not found',
        available_endpoints: [
          'POST /click - Track clicks',
          'POST /conversion - Track conversions',
          'GET /events - View all received events',
          'GET /health - Health check'
        ]
      }));
    }

    console.log(`${'='.repeat(60)}\n`);
  });
});

server.listen(PORT, '0.0.0.0', () => {
  console.log(`
╔════════════════════════════════════════════════════════════╗
║         Affilify Mock API Server (HTTPS)                   ║
╠════════════════════════════════════════════════════════════╣
║  Server running on https://0.0.0.0:${PORT}                     ║
╠════════════════════════════════════════════════════════════╣
║  Endpoints:                                                ║
║    POST /click      - Receive click tracking               ║
║    POST /conversion - Receive conversion tracking          ║
║    GET  /events     - View all received events             ║
║    GET  /health     - Health check                         ║
╠════════════════════════════════════════════════════════════╣
║  For Magento module configuration use:                     ║
║    API URL: https://host.docker.internal:${PORT}               ║
║                                                            ║
║  Or add to docker-compose.yml extra_hosts:                 ║
║    - "tracking.local:host-gateway"                         ║
╚════════════════════════════════════════════════════════════╝
  `);
});

// Handle graceful shutdown
process.on('SIGINT', () => {
  console.log('\n\n📊 Session Summary:');
  console.log(`   Total Clicks: ${events.clicks.length}`);
  console.log(`   Total Conversions: ${events.conversions.length}`);
  console.log('\nShutting down...');
  process.exit(0);
});
