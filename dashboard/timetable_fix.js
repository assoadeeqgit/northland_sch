// Quick fix for timetable JavaScript
document.addEventListener('DOMContentLoaded', function () {
    console.log('Timetable fix loaded');

    // Fix level buttons
    document.querySelectorAll('.level-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const level = this.dataset.level;
            console.log('Level clicked:', level);

            // Update button states
            document.querySelectorAll('.level-btn').forEach(b => {
                b.classList.remove('active', 'bg-nskblue', 'text-white');
                b.classList.add('border', 'border-nskblue', 'text-nskblue');
            });

            this.classList.add('active', 'bg-nskblue', 'text-white');
            this.classList.remove('border', 'border-nskblue', 'text-nskblue');

            // Show/hide class selections
            document.getElementById('earlyChildhoodClasses').classList.add('hidden');
            document.getElementById('primaryClasses').classList.add('hidden');
            document.getElementById('secondaryClasses').classList.add('hidden');

            if (level === 'early-childhood') {
                document.getElementById('earlyChildhoodClasses').classList.remove('hidden');
            } else if (level === 'primary') {
                document.getElementById('primaryClasses').classList.remove('hidden');
            } else if (level === 'secondary') {
                document.getElementById('secondaryClasses').classList.remove('hidden');
            }

            // Reset timetable display
            document.getElementById('timetableDisplay').innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-calendar-alt text-4xl mb-4"></i>
                    <p>Select a class to view the timetable</p>
                </div>
            `;
        });
    });

    // Fix class selection
    document.querySelectorAll('.class-selector').forEach(select => {
        select.addEventListener('change', function () {
            const classId = this.value;
            console.log('Class selected:', classId);

            if (!classId) {
                document.getElementById('timetableDisplay').innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-calendar-alt text-4xl mb-4"></i>
                        <p>Select a class to view the timetable</p>
                    </div>
                `;
                return;
            }

            // Show loading
            document.getElementById('timetableDisplay').innerHTML = `
                <div class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-nskblue"></div>
                    <p class="mt-4 text-gray-600">Loading timetable...</p>
                </div>
            `;

            // Load timetable
            fetch(`simple_timetable_api.php?class_id=${classId}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('API response:', data);

                    if (data.success && data.data.length > 0) {
                        displayTimetable(data);
                    } else {
                        document.getElementById('timetableDisplay').innerHTML = `
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-calendar-alt text-4xl mb-4"></i>
                                <p>No timetable data found for this class</p>
                                <p class="text-sm mt-2">Classes with timetable data: Primary 2, Primary 3, SS 1</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('timetableDisplay').innerHTML = `
                        <div class="text-center py-8 text-red-500">
                            <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                            <p>Error loading timetable: ${error.message}</p>
                        </div>
                    `;
                });
        });
    });

    // Fix auto-generate button
    const generateBtn = document.getElementById('generateTimetableBtn');
    if (generateBtn) {
        generateBtn.addEventListener('click', function () {
            // Get currently selected class from all possible selectors
            let classId = null;
            const selectors = ['#classSelectPrimary', '#classSelectSecondary', '#classSelectEarly'];

            for (const selector of selectors) {
                const select = document.querySelector(selector);
                if (select && !select.closest('.hidden') && select.value) {
                    classId = select.value;
                    break;
                }
            }

            console.log('Selected class ID:', classId);

            if (!classId) {
                Swal.fire('Warning', 'Please select a class first', 'warning');
                return;
            }

            Swal.fire({
                title: 'Auto Generate?',
                text: 'This will automatically fill all empty slots with dummy subjects. Continue?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, generate',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const originalText = generateBtn.innerHTML;
                    generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Generating...';
                    generateBtn.disabled = true;

                    fetch('timetable_generate.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ class_id: classId })
                    })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                Swal.fire('Success', result.message, 'success');
                                // Reload the timetable by triggering change event
                                const activeSelect = document.querySelector(`#classSelectPrimary, #classSelectSecondary, #classSelectEarly`);
                                if (activeSelect && activeSelect.value === classId) {
                                    activeSelect.dispatchEvent(new Event('change'));
                                }
                            } else {
                                Swal.fire('Error', result.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire('Error', 'An error occurred while generating the timetable.', 'error');
                        })
                        .finally(() => {
                            generateBtn.innerHTML = originalText;
                            generateBtn.disabled = false;
                        });
                }
            });
        });
    }
});

function displayTimetable(data) {
    const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    const entries = data.data;

    // Use the same time periods as classes.php
    const timeSlots = [
        '08:00 - 08:45',
        '08:45 - 09:30',
        '09:30 - 10:15',
        '10:15 - 11:00', // Break time
        '11:00 - 11:45',
        '11:45 - 12:30',
        '12:30 - 13:15',
        '13:15 - 14:00'
    ];

    const breakTime = '10:15 - 11:00';

    let html = `
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-nsknavy">Weekly Timetable</h3>
        </div>
        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="bg-nsknavy text-white p-3 border-r border-blue-800">Time</th>
                        ${days.map(day => `<th class="bg-nsknavy text-white p-3 border-r border-blue-800">${day}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
    `;

    timeSlots.forEach(timeSlot => {
        if (timeSlot === breakTime) {
            // Break row
            html += `
                <tr class="bg-amber-50">
                    <td class="p-3 font-bold text-center text-amber-800 border-r border-amber-200">${timeSlot}</td>
                    <td colspan="5" class="p-3 text-center font-bold tracking-widest text-amber-800 uppercase bg-amber-100">
                        --- BREAK TIME ---
                    </td>
                </tr>
            `;
        } else {
            html += `<tr class="hover:bg-gray-50">`;
            html += `<td class="bg-gray-50 p-3 font-semibold text-center border-r border-gray-200">${timeSlot}</td>`;

            days.forEach(day => {
                const entry = entries.find(e => {
                    const entryTime = `${e.start_time.substring(0, 5)} - ${e.end_time.substring(0, 5)}`;
                    return e.day_of_week === day && entryTime === timeSlot;
                });

                if (entry) {
                    html += `
                        <td class="p-2 border-r border-gray-100">
                            <div class="bg-blue-50 border-l-4 border-blue-300 p-3 rounded shadow-sm">
                                <p class="font-bold text-nsknavy leading-tight">${entry.subject_name}</p>
                                <p class="text-xs mt-1 text-gray-600">${entry.teacher_name || 'No Teacher'}</p>
                            </div>
                        </td>
                    `;
                } else {
                    html += `
                        <td class="p-2 border-r border-gray-100">
                            <div class="bg-gray-50 p-3 rounded border border-dashed border-gray-300 text-center">
                                <p class="text-xs font-bold text-gray-400">FREE</p>
                            </div>
                        </td>
                    `;
                }
            });

            html += `</tr>`;
        }
    });

    html += `
                </tbody>
            </table>
        </div>
    `;

    document.getElementById('timetableDisplay').innerHTML = html;
}
