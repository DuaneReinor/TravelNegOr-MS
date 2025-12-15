#!/bin/bash

# CSRF Ngrok Deployment Testing Script
# This script helps test CSRF functionality with ngrok deployment

echo "============================================"
echo "CSRF Ngrok Deployment Testing"
echo "============================================"

# Check if ngrok is running
if ! command -v ngrok &> /dev/null; then
    echo "❌ ngrok is not installed or not in PATH"
    echo "   Install ngrok from: https://ngrok.com/download"
    exit 1
fi

echo "✅ ngrok found"

# Check if Symfony server is running
echo ""
echo "1. Starting Symfony development server..."
symfony serve -d --no-tls > /dev/null 2>&1 &
SYMFONY_PID=$!
sleep 3

echo "✅ Symfony server started (PID: $SYMFONY_PID)"

# Start ngrok tunnel
echo ""
echo "2. Starting ngrok tunnel..."
ngrok http 8000 > /dev/null 2>&1 &
NGROK_PID=$!
sleep 5

# Get ngrok URL
NGROK_URL=$(curl -s http://localhost:4040/api/tunnels | grep -o '"public_url":"[^"]*' | cut -d'"' -f4)

if [ -z "$NGROK_URL" ]; then
    echo "❌ Failed to get ngrok URL"
    kill $SYMFONY_PID $NGROK_PID 2>/dev/null
    exit 1
fi

echo "✅ ngrok tunnel started: $NGROK_URL"

# Clear cache
echo ""
echo "3. Clearing Symfony cache..."
php bin/console cache:clear --no-warmup

echo "✅ Cache cleared"

# Test CSRF functionality
echo ""
echo "4. Testing CSRF functionality..."
php bin/console app:debug-csrf-ngrok

echo ""
echo "5. Running comprehensive CSRF tests..."
php bin/console app:test-csrf-ngrok

echo ""
echo "============================================"
echo "Deployment URLs:"
echo "============================================"
echo "Local: http://localhost:8000"
echo "Ngrok: $NGROK_URL"
echo ""
echo "Test the following pages:"
echo "1. Login: $NGROK_URL/login"
echo "2. CSRF Test: $NGROK_URL/admin/csrf/test"
echo ""
echo "To stop the servers:"
echo "kill $SYMFONY_PID $NGROK_PID"
echo ""
echo "Or press Ctrl+C to stop now..."

# Wait for user input
read -p "Press Enter to stop the servers and continue..."

# Cleanup
echo ""
echo "Stopping servers..."
kill $SYMFONY_PID $NGROK_PID 2>/dev/null
echo "✅ Servers stopped"