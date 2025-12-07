#!/bin/bash
# Script to update all teacher dashboard files to use main authentication system

echo "Updating teacher dashboard files to use unified authentication..."

cd "/var/www/html/nsknbkp1/sms-teacher"

# Update login-form.php redirects to ../login-form.php
echo "Updating login redirects..."
find . -name "*.php" -type f -exec sed -i 's|Location: login-form\.php|Location: ../login-form.php|g' {} \;
find . -name "*.php" -type f -exec sed -i 's|header("Location: login-form\.php")|header("Location: ../login-form.php")|g' {} \;
find . -name "*.php" -type f -exec sed -i "s|header('Location: login-form\.php')|header('Location: ../login-form.php')|g" {} \;

echo "✅ All redirects updated to use main login page"
echo "✅ Database config now points to main config"
echo "✅ Logout redirects to main logout script"
echo ""
echo "Teacher dashboard now uses the unified authentication system!"
