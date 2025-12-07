#!/bin/bash
# Script to update all references from "sms-teacher" to "sms-teacher"

echo "Updating all references from 'sms-teacher' to 'sms-teacher'..."

cd /var/www/html/nsknbkp1

# Update all markdown documentation files
echo "Updating documentation files..."
find . -name "*.md" -type f -exec sed -i 's|sms-teacher|sms-teacher|g' {} \;

# Update shell scripts
echo "Updating shell scripts..."
find . -name "*.sh" -type f -exec sed -i 's|sms-teacher|sms-teacher|g' {} \;

# Update PHP files (but be careful not to break strings)
echo "Updating PHP files..."
find . -name "*.php" -type f ! -path "*/vendor/*" ! -path "*/node_modules/*" -exec sed -i 's|sms-teacher|sms-teacher|g' {} \;

# Update JavaScript/HTML files
echo "Updating HTML/JS files..."
find . -name "*.html" -type f -exec sed -i 's|sms-teacher|sms-teacher|g' {} \;
find . -name "*.js" -type f -exec sed -i 's|sms-teacher|sms-teacher|g' {} \;

echo ""
echo "✅ All references updated from 'sms-teacher' to 'sms-teacher'"
echo "✅ Files affected:"
echo "   - Documentation (.md)"
echo "   - Shell scripts (.sh)"
echo "   - PHP files (.php)"
echo "   - HTML/JS files"
echo ""
echo "Updated folder path: sms-teacher/"
