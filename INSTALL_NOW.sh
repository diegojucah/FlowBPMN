#!/bin/bash

# -------------------------------------------------------------------------
# flowBPMN Plugin - One-Click Docker Installation
# -------------------------------------------------------------------------
# Run this script from the host machine to install in Docker container
# Usage: bash INSTALL_NOW.sh
# -------------------------------------------------------------------------

set -e

CONTAINER_ID="4a5500931c39"
PLUGIN_NAME="flowBPMN"
PLUGIN_KEY="flowbpmn"

echo "=========================================="
echo "  flowBPMN - Docker Installation"
echo "=========================================="
echo ""

# Check if container is running
echo "→ Checking Docker container..."
if sudo docker ps | grep -q $CONTAINER_ID; then
    echo "✓ Container $CONTAINER_ID is running"
else
    echo "✗ Container $CONTAINER_ID not found or not running"
    echo "  Please start your GLPI container first"
    exit 1
fi

# Copy plugin to container
echo ""
echo "→ Copying plugin files to container..."
sudo docker cp /home/diego/glpi11/$PLUGIN_NAME $CONTAINER_ID:/var/www/glpi/plugins/
echo "✓ Files copied"

# Set permissions
echo ""
echo "→ Setting permissions..."
sudo docker exec $CONTAINER_ID chown -R www-data:www-data /var/www/glpi/plugins/$PLUGIN_NAME
sudo docker exec $CONTAINER_ID chmod -R 755 /var/www/glpi/plugins/$PLUGIN_NAME
echo "✓ Permissions set"

# Run tests
echo ""
echo "→ Running pre-installation tests..."
sudo docker exec $CONTAINER_ID bash -c "cd /var/www/glpi && php bin/console glpi:plugin:list | head -5"

# Install plugin
echo ""
echo "→ Installing plugin..."
sudo docker exec $CONTAINER_ID bash -c "cd /var/www/glpi && php bin/console glpi:plugin:install $PLUGIN_KEY" || {
    echo "✗ Installation failed!"
    echo ""
    echo "Check logs:"
    echo "  sudo docker exec $CONTAINER_ID tail -50 /var/www/glpi/files/_log/php-errors.log"
    exit 1
}
echo "✓ Plugin installed"

# Activate plugin
echo ""
echo "→ Activating plugin..."
sudo docker exec $CONTAINER_ID bash -c "cd /var/www/glpi && php bin/console glpi:plugin:activate $PLUGIN_KEY" || {
    echo "✗ Activation failed!"
    exit 1
}
echo "✓ Plugin activated"

# Verify installation
echo ""
echo "→ Verifying installation..."
sudo docker exec $CONTAINER_ID bash -c "cd /var/www/glpi && php bin/console glpi:plugin:list | grep $PLUGIN_KEY"

# Check database tables
echo ""
echo "→ Checking database tables..."
sudo docker exec $CONTAINER_ID mysql -u root -proot glpi -e "SHOW TABLES LIKE 'glpi_plugin_flowbpmn%';" || {
    echo "Note: Could not verify tables (this is OK if MySQL access differs)"
}

echo ""
echo "=========================================="
echo "  ✓ Installation Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Access GLPI: http://localhost:8080"
echo "2. Login: glpi / glpi"
echo "3. Go to: Setup → Plugins"
echo "4. Verify 'BPMN Flow' is active"
echo "5. Create a ticket and test the BPMN tab"
echo ""
echo "Configuration:"
echo "→ Setup → Profiles → Super-Admin → BPMN Flow"
echo "   (Set all permissions)"
echo ""
echo "→ Setup → General → BPMN Flow"
echo "   (Configure options)"
echo ""
echo "Enjoy! 🎉"
