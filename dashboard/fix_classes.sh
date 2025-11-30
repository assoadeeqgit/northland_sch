#!/bin/bash

# Find line numbers for the script section to replace
start_line=$(grep -n '// Enhanced tab functionality' classes.php | head -1 | cut -d: -f1)
start_line=$((start_line - 1))  # Include <script> tag

end_line=$(grep -n 'Include classes management JavaScript' classes.php | head -1 | cut -d: -f1)
end_line=$((end_line + 1))  # Include </script> tag

echo "Start line: $start_line"
echo "End line: $end_line"

# Create the replacement script
cat > replacement.txt << 'REPLACEMENT'
    <script>
        // Global notification function used by classes_management.js
        function showNotification(message, type = 'success') {
            console.log(`[${type.toUpperCase()}] ${message}`);
        }

        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.tab-button').forEach(button => {
                button.addEventListener('click', function () {
                    const tabName = this.getAttribute('data-tab');
                    
                    document.querySelectorAll('.tab-button').forEach(btn => {
                        btn.classList.remove('active');
                        btn.classList.add('border-gray-300', 'text-gray-700');
                        btn.classList.remove('border-nskblue', 'text-nskblue', 'bg-nskblue', 'text-white');
                    });
                    this.classList.add('active');
                    this.classList.remove('border-gray-300', 'text-gray-700');
                    this.classList.add('border-nskblue', 'bg-nskblue', 'text-white');
                    
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.add('hidden');
                    });
                    
                    const targetTab = document.getElementById(tabName + 'Tab');
                    if (targetTab) {
                        targetTab.classList.remove('hidden');
                    }
                });
            });

            // Class card animations
            document.querySelectorAll('.class-card').forEach(card => {
                card.addEventListener('mouseenter', function () {
                    this.style.transform = 'translateY(-5px)';
                });

                card.addEventListener('mouseleave', function () {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Schedule modal functionality
            const modal = document.getElementById('addScheduleModal');
            const addScheduleBtn = document.getElementById('addScheduleBtn');
            const closeModal = document.getElementById('closeModal');
            const cancelBtn = document.getElementById('cancelBtn');
            const scheduleForm = document.getElementById('scheduleForm');

            if (addScheduleBtn) {
                addScheduleBtn.addEventListener('click', function () {
                    if (modal) modal.classList.add('active');
                });
            }

            function closeModalFunc() {
                if (modal) modal.classList.remove('active');
            }

            if (closeModal) closeModal.addEventListener('click', closeModalFunc);
            if (cancelBtn) cancelBtn.addEventListener('click', closeModalFunc);

            if (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) closeModalFunc();
                });
            }

            if (scheduleForm) {
                scheduleForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const subject = document.getElementById('subject').value;
                    const day = document.getElementById('day').value;
                    const period = document.getElementById('period').value;
                    alert(`Schedule added for ${subject} on ${day} during period ${period}`);
                    closeModalFunc();
                    scheduleForm.reset();
                });
            }
        });
    </script>

    <!-- Include classes management JavaScript - handles all buttons and modals -->
    <script src="classes_management.js"></script>
REPLACEMENT

# Use sed to replace the lines
if [ ! -z "$start_line" ] && [ ! -z "$end_line" ]; then
  sed -i "${start_line},${end_line}d" classes.php
  sed -i "${start_line}r replacement.txt" classes.php
  echo "Replacement complete"
else
  echo "Could not find lines to replace"
fi
