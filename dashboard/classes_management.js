// Classes Management JavaScript
// This file handles all class management operations

// API base URL
const API_URL = "../api/classes_api.php";

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  initializeEventListeners();
});

function initializeEventListeners() {
  // Tab functionality
  document.querySelectorAll(".tab-button").forEach((button) => {
    button.addEventListener("click", function () {
      switchTab(this.getAttribute("data-tab"));
    });
  });

  // Filter functionality
  const filterBtn = document.getElementById("filterBtn");
  if (filterBtn) {
    filterBtn.addEventListener("click", applyFilters);
  }

  // Create Class button
  const createClassBtn = document.getElementById("createClassBtn");
  if (createClassBtn) {
    createClassBtn.addEventListener("click", showCreateClassModal);
  }

  // Bulk Actions button
  const bulkActionsBtn = document.getElementById("bulkActionsBtn");
  if (bulkActionsBtn) {
    bulkActionsBtn.addEventListener("click", showBulkActionsMenu);
  }

  // Load timetable for selected class
  const loadTimetableBtn = document.querySelector("#timetableTab button");
  if (loadTimetableBtn) {
    loadTimetableBtn.addEventListener("click", loadTimetable);
  }
}

// Tab switching functionality
function switchTab(tabName) {
  // Update button states
  document.querySelectorAll(".tab-button").forEach((btn) => {
    btn.classList.remove(
      "active",
      "border-nskblue",
      "text-nskblue",
      "bg-nskblue",
      "text-white"
    );
    btn.classList.add("border-gray-300", "text-gray-700");
  });

  // Activate clicked button
  const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
  activeButton.classList.add(
    "active",
    "border-nskblue",
    "bg-nskblue",
    "text-white"
  );
  activeButton.classList.remove("border-gray-300", "text-gray-700");

  // Show/hide tab content
  document.querySelectorAll(".tab-content").forEach((content) => {
    content.classList.add("hidden");
  });

  const targetTab = document.getElementById(tabName + "Tab");
  if (targetTab) {
    targetTab.classList.remove("hidden");
  }
}

// Show notification messages
function showNotification(message, type = "success") {
  // Remove existing notifications
  const existingNotifications = document.querySelectorAll(
    ".custom-notification"
  );
  existingNotifications.forEach((notification) => notification.remove());

  const notificationDiv = document.createElement("div");
  const bgColor =
    type === "success"
      ? "bg-green-100 border-green-400 text-green-700"
      : "bg-red-100 border-red-400 text-red-700";

  notificationDiv.className = `${bgColor} border px-4 py-3 rounded fixed top-4 right-4 z-50 max-w-md shadow-lg custom-notification`;
  notificationDiv.innerHTML = `
        <div class="flex items-center justify-between">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 font-bold">&times;</button>
        </div>
    `;

  document.body.appendChild(notificationDiv);

  setTimeout(() => {
    if (notificationDiv.parentElement) {
      notificationDiv.remove();
    }
  }, 5000);
}

// Filter functionality
function applyFilters() {
  const classFilter = document.getElementById("classFilter").value.toLowerCase();
  const levelFilter = document.getElementById("sectionFilter").value.toLowerCase();

  const classCards = document.querySelectorAll(".class-card");
  let visibleCount = 0;

  classCards.forEach((card) => {
    const className = card.querySelector("h3").textContent.toLowerCase().trim();
    const cardLevel = card.getAttribute("data-level") || '';

    let showCard = true;

    // Apply class filter (specific class name)
    if (classFilter) {
      // Exact match for class name
      if (className !== classFilter) {
        showCard = false;
      }
    }

    // Apply level filter (only if no specific class is selected)
    if (!classFilter && levelFilter) {
      // Check if the level matches the filter
      if (cardLevel !== levelFilter) {
        showCard = false;
      }
    }

    if (showCard) {
      card.style.display = "block";
      visibleCount++;
    } else {
      card.style.display = "none";
    }
  });

  // Show message if no results
  const existingMsg = document.querySelector(".filter-no-results");
  if (existingMsg) existingMsg.remove();

  if (visibleCount === 0) {
    const container = document.querySelector("#classesTab .grid");
    const msg = document.createElement("div");
    msg.className = "filter-no-results col-span-full text-center py-12";
    msg.innerHTML = `
            <i class="fas fa-search text-gray-300 text-6xl mb-4"></i>
            <p class="text-gray-500 text-lg">No classes match your filters</p>
            <button onclick="clearFilters()" class="mt-4 bg-nskblue text-white px-4 py-2 rounded-lg hover:bg-nsknavy transition">
                Clear Filters
            </button>
        `;
    container.appendChild(msg);
  }

  showNotification(`Found ${visibleCount} classes matching your filters`);
}

// Clear filters function (globally accessible)
window.clearFilters = function clearFilters() {
  document.getElementById("classFilter").value = "";
  document.getElementById("sectionFilter").value = "";
  document.querySelectorAll(".class-card").forEach((card) => {
    card.style.display = "block";
  });
  const msg = document.querySelector(".filter-no-results");
  if (msg) msg.remove();
};

// Create Class Modal functionality
function showCreateClassModal() {
  const modal = document.getElementById("createClassModal");
  if (modal) {
    modal.classList.add("active");
    modal.style.display = "flex";
  }
}

function closeCreateClassModal() {
  const modal = document.getElementById("createClassModal");
  if (modal) {
    modal.classList.remove("active");
    setTimeout(() => {
      modal.style.display = "none";
    }, 300);
  }
}

// Bulk Actions functionality
function showBulkActionsMenu() {
  const menu = document.createElement("div");
  menu.className =
    "fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50";
  menu.innerHTML = `
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 m-4">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-nsknavy">Bulk Actions</h3>
                <button onclick="this.closest('div.fixed').remove()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="space-y-3">
                <button onclick="bulkExport()" class="w-full bg-nskblue text-white px-4 py-3 rounded-lg hover:bg-nsknavy transition flex items-center justify-center">
                    <i class="fas fa-download mr-2"></i> Export All Classes
                </button>
                <button onclick="bulkPrint()" class="w-full bg-nskgreen text-white px-4 py-3 rounded-lg hover:bg-green-600 transition flex items-center justify-center">
                    <i class="fas fa-print mr-2"></i> Print Class List
                </button>
                <button onclick="bulkEmail()" class="w-full bg-nskgold text-white px-4 py-3 rounded-lg hover:bg-amber-600 transition flex items-center justify-center">
                    <i class="fas fa-envelope mr-2"></i> Email Reports
                </button>
            </div>
        </div>
    `;
  document.body.appendChild(menu);
}

// Bulk action functions (globally accessible)
window.bulkExport = function bulkExport() {
  showNotification("Preparing export...");

  // Generate CSV from all class data on the page
  const cards = document.querySelectorAll(".class-card");
  if (cards.length === 0) {
    showNotification("No classes to export", "error");
    return;
  }

  let csvContent = "data:text/csv;charset=utf-8,";
  csvContent += "Class Name,Class Code,Level,Capacity,Students,Subjects\n";

  cards.forEach((card) => {
    const title = card.querySelector("h3")?.textContent || "";
    const details = card.querySelectorAll("p");
    const code =
      card
        .querySelector(".text-sm.text-gray-600")
        ?.textContent?.split(": ")[1] || "";
    const level = details[0]?.textContent?.split(": ")[1] || "";
    const capacity = details[1]?.textContent?.split(": ")[1] || "";
    const students = details[2]?.textContent?.split(": ")[1] || "";

    csvContent += `"${title}","${code}","${level}","${capacity}","${students}","0"\n`;
  });

  const encodedUri = encodeURI(csvContent);
  const link = document.createElement("a");
  link.setAttribute("href", encodedUri);
  link.setAttribute("download", `classes-export-${new Date().getTime()}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  showNotification("Classes exported successfully!");
  closeBulkActionsMenu();
}

window.bulkPrint = function bulkPrint() {
  window.print();
  closeBulkActionsMenu();
}

window.bulkEmail = function bulkEmail() {
  showNotification("Email functionality is under development", "info");
  closeBulkActionsMenu();
}

function closeBulkActionsMenu() {
  const menu = document.querySelector(".fixed.inset-0.bg-black");
  if (menu) {
    menu.remove();
  }
}

// View class details (globally accessible)
window.viewClassDetails = async function viewClassDetails(classId) {
  showNotification("Loading class details...");

  try {
    const response = await fetch(
      `../api/classes_api.php?action=get_class_details&class_id=${classId}`
    );
    const result = await response.json();

    if (result.success) {
      showClassDetailsModal(result.data);
    } else {
      showNotification(
        "Error loading class details: " + result.message,
        "error"
      );
    }
  } catch (error) {
    showNotification("Error: " + error.message, "error");
  }
}

// Show class details modal
function showClassDetailsModal(classData) {
  const teacherName = classData.teacher_first_name
    ? `${classData.teacher_first_name} ${classData.teacher_last_name}`
    : "Not Assigned";

  const modalHTML = `
        <div id="classDetailsModal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 active" style="display: flex;">
            <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full p-6 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-nsknavy">${classData.class_name
    }</h3>
                    <button onclick="closeClassDetailsModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-bold text-nsknavy mb-3"><i class="fas fa-info-circle mr-2"></i>Class Information</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Class Code:</span>
                                <span class="font-semibold">${classData.class_code
    }</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Level:</span>
                                <span class="font-semibold">${classData.class_level
    }</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Capacity:</span>
                                <span class="font-semibold">${classData.student_count
    }/${classData.capacity}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-bold text-nsknavy mb-3"><i class="fas fa-user-tie mr-2"></i>Class Teacher</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Name:</span>
                                <span class="font-semibold">${teacherName}</span>
                            </div>
                            ${classData.teacher_email
      ? `
                            <div class="flex justify-between">
                                <span class="text-gray-600">Email:</span>
                                <span class="font-semibold text-sm">${classData.teacher_email}</span>
                            </div>
                            `
      : ""
    }
                        </div>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h4 class="font-bold text-nsknavy mb-3"><i class="fas fa-book mr-2"></i>Subjects (${classData.subject_count
    })</h4>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        ${classData.subjects && classData.subjects.length > 0
      ? `
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                ${classData.subjects
        .map(
          (subject) => `
                                    <div class="flex items-center justify-between bg-white p-2 rounded">
                                        <span class="font-semibold">${subject.subject_name
            }</span>
                                        <span class="text-sm text-gray-600">${subject.teacher_first_name
              ? subject.teacher_first_name +
              " " +
              subject.teacher_last_name
              : "No teacher"
            }</span>
                                    </div>
                                `
        )
        .join("")}
                            </div>
                        `
      : '<p class="text-gray-500">No subjects assigned yet</p>'
    }
                    </div>
                </div>
                
                <div>
                    <h4 class="font-bold text-nsknavy mb-3"><i class="fas fa-users mr-2"></i>Students (${classData.student_count
    })</h4>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        ${classData.students && classData.students.length > 0
      ? `
                            <div class="space-y-2">
                                ${classData.students
        .map(
          (student) => `
                                    <div class="flex items-center justify-between bg-white p-2 rounded">
                                        <span class="font-semibold">${student.first_name} ${student.last_name}</span>
                                        <span class="text-sm text-gray-600">${student.student_id}</span>
                                    </div>
                                `
        )
        .join("")}
                                ${classData.student_count > 3
        ? `<p class="text-center text-gray-500 text-sm mt-2">Showing 3 of ${classData.student_count} students</p>`
        : ""
      }
                            </div>
                        `
      : '<p class="text-gray-500">No students enrolled yet</p>'
    }
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button onclick="editClass(${classData.id
    })" class="bg-nskblue text-white px-6 py-2 rounded-lg hover:bg-nsknavy transition">
                        <i class="fas fa-edit mr-2"></i>Edit Class
                    </button>
                    <button onclick="closeClassDetailsModal()" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition">
                        Close
                    </button>
                </div>
            </div>
        </div>
    `;

  document.body.insertAdjacentHTML("beforeend", modalHTML);
}

window.closeClassDetailsModal = function closeClassDetailsModal() {
  const modal = document.getElementById("classDetailsModal");
  if (modal) {
    modal.remove();
  }
}

// Assign teacher to class (globally accessible)
window.assignTeacher = async function assignTeacher(classId) {
  showNotification("Loading teachers list...");

  try {
    // Fetch both class details and teachers list in parallel
    const [classResponse, teachersResponse] = await Promise.all([
      fetch(`../api/classes_api.php?action=get_class_details&class_id=${classId}`),
      fetch("../api/classes_api.php?action=get_teachers")
    ]);

    const classResult = await classResponse.json();
    const teachersResult = await teachersResponse.json();

    if (!classResult.success) {
      showNotification("Error loading class details: " + classResult.message, "error");
      return;
    }

    if (!teachersResult.success) {
      showNotification("Error loading teachers: " + teachersResult.message, "error");
      return;
    }

    // Pass class data and teachers to modal
    showAssignTeacherModal(classId, teachersResult.data, classResult.data);
  } catch (error) {
    showNotification("Error: " + error.message, "error");
  }
}

function showAssignTeacherModal(classId, teachers, classData) {
  // Get current teacher info
  const currentTeacherId = classData.class_teacher_id || null;
  const currentTeacherName = classData.teacher_first_name
    ? `${classData.teacher_first_name} ${classData.teacher_last_name}`
    : null;

  // Determine modal title
  const modalTitle = currentTeacherName
    ? `Reassign Teacher for ${classData.class_name}`
    : `Assign Teacher to ${classData.class_name}`;

  const currentTeacherNote = currentTeacherName
    ? `<p class="text-sm text-gray-600 mb-4">Current teacher: <strong class="text-nskblue">${currentTeacherName}</strong></p>`
    : '';

  const modalHTML = `
        <div id="assignTeacherModal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 active" style="display: flex;">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-nsknavy">${modalTitle}</h3>
                    <button onclick="closeAssignTeacherModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                ${currentTeacherNote}
                
                <form id="assignTeacherForm" class="space-y-4">
                    <input type="hidden" name="class_id" value="${classId}">
                    
                    <div>
                        <label class="block text-gray-700 mb-2">Select Teacher</label>
                        <select name="teacher_id" class="w-full px-4 py-2 border rounded-lg focus:border-nskblue" required>
                            <option value="">Choose a teacher...</option>
                            ${teachers
      .map(
        (teacher) => `
                                <option value="${teacher.id}" ${parseInt(teacher.id) === parseInt(currentTeacherId) ? 'selected' : ''}>
                                    ${teacher.first_name} ${teacher.last_name
          } ${teacher.subject_specialization
            ? "(" + teacher.subject_specialization + ")"
            : ""
          }
                                </option>
                            `
      )
      .join("")}
                        </select>
                        ${currentTeacherId
      ? '<p class="text-xs text-gray-500 mt-1">Select a new teacher to reassign or keep the current selection</p>'
      : '<p class="text-xs text-gray-500 mt-1">Select a teacher to assign to this class</p>'
    }
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeAssignTeacherModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-nskblue text-white rounded-lg hover:bg-nsknavy transition">
                            ${currentTeacherName ? 'Reassign Teacher' : 'Assign Teacher'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

  document.body.insertAdjacentHTML("beforeend", modalHTML);

  // Handle form submission
  document
    .getElementById("assignTeacherForm")
    .addEventListener("submit", async function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      formData.append("action", "assign_teacher");

      showNotification("Assigning teacher...");

      try {
        const response = await fetch("../api/classes_api.php", {
          method: "POST",
          body: formData,
        });
        const result = await response.json();

        if (result.success) {
          showNotification("Teacher assigned successfully!");
          closeAssignTeacherModal();
          // Refresh the page after a short delay to show updated data
          setTimeout(() => {
            window.location.reload();
          }, 1500);
        } else {
          showNotification("Error: " + result.message, "error");
        }
      } catch (error) {
        showNotification("Network error: " + error.message, "error");
      }
    });
}

window.closeAssignTeacherModal = function closeAssignTeacherModal() {
  const modal = document.getElementById("assignTeacherModal");
  if (modal) {
    modal.remove();
  }
}

// Edit class function (globally accessible)
window.editClass = async function editClass(classId) {
  showNotification("Loading class details...");

  try {
    // Fetch class details and teachers in parallel
    const [classResponse, teachersResponse] = await Promise.all([
      fetch(`../api/classes_api.php?action=get_class_details&class_id=${classId}`),
      fetch(`../api/classes_api.php?action=get_teachers`)
    ]);

    const classResult = await classResponse.json();
    const teachersResult = await teachersResponse.json();

    if (!classResult.success) throw new Error(classResult.message);
    if (!teachersResult.success) throw new Error(teachersResult.message);

    const classData = classResult.data;
    const teachers = teachersResult.data;

    // Remove existing modal if any
    const existingModal = document.getElementById("editClassModal");
    if (existingModal) existingModal.remove();

    // Create modal HTML
    const modalHTML = `
        <div id="editClassModal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 active">
            <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-nsknavy">Edit Class: ${classData.class_name}</h3>
                    <button onclick="closeEditClassModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="editClassForm" class="space-y-4">
                    <input type="hidden" name="action" value="update_class">
                    <input type="hidden" name="class_id" value="${classData.id}">

                    <div>
                        <label class="block text-gray-700 mb-2" for="editClassName">Class Name</label>
                        <input type="text" id="editClassName" name="class_name"
                            class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue"
                            value="${classData.class_name}" required>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2" for="editClassCode">Class Code</label>
                        <input type="text" id="editClassCode" name="class_code"
                            class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue bg-gray-100"
                            value="${classData.class_code || ''}" readonly>
                        <p class="text-xs text-gray-500 mt-1">Class code cannot be changed</p>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2" for="editClassLevel">Class Level</label>
                        <select id="editClassLevel" name="class_level"
                            class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" required>
                            <option value="Early Childhood" ${classData.class_level === 'Early Childhood' ? 'selected' : ''}>Early Childhood</option>
                            <option value="Primary" ${classData.class_level === 'Primary' ? 'selected' : ''}>Primary</option>
                            <option value="Secondary" ${classData.class_level === 'Secondary' ? 'selected' : ''}>Secondary</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2" for="editClassTeacher">Class Teacher</label>
                        <select id="editClassTeacher" name="class_teacher_id"
                            class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue">
                            <option value="">Select Teacher</option>
                            ${teachers.map(teacher => `
                                <option value="${teacher.id}" ${parseInt(classData.class_teacher_id) === parseInt(teacher.id) ? 'selected' : ''}>
                                    ${teacher.first_name} ${teacher.last_name} ${teacher.subject_specialization ? `(${teacher.subject_specialization})` : ''}
                                </option>
                            `).join('')}
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-2" for="editMaxStudents">Maximum Students</label>
                        <input type="number" id="editMaxStudents" name="capacity"
                            class="w-full px-4 py-2 border rounded-lg form-input focus:border-nskblue" min="10" max="100"
                            value="${classData.capacity}" required>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeEditClassModal()"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-nskblue text-white rounded-lg font-semibold hover:bg-nsknavy transition">
                            Update Class
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML("beforeend", modalHTML);

    // Handle form submission
    document.getElementById("editClassForm").addEventListener("submit", async function (e) {
      e.preventDefault();
      const formData = new FormData(this);

      showNotification("Updating class...");

      try {
        const response = await fetch("../api/classes_api.php", {
          method: "POST",
          body: formData
        });
        const result = await response.json();

        if (result.success) {
          showNotification("Class updated successfully!");
          closeEditClassModal();
          setTimeout(() => window.location.reload(), 1500);
        } else {
          showNotification("Error: " + result.message, "error");
        }
      } catch (error) {
        showNotification("Network error: " + error.message, "error");
      }
    });

  } catch (error) {
    showNotification("Error loading class details: " + error.message, "error");
  }
}

window.closeEditClassModal = function () {
  const modal = document.getElementById("editClassModal");
  if (modal) modal.remove();
}

// Load timetable function (globally accessible)
window.loadTimetable = async function loadTimetable() {
  const classSelect = document.getElementById("classSelect");
  const selectedClassId = classSelect.value;
  const selectedClassName = classSelect.options[classSelect.selectedIndex].text;

  if (!selectedClassId) {
    showNotification("Please select a class first", "error");
    return;
  }

  showNotification(`Loading timetable for ${selectedClassName}...`);

  try {
    const response = await fetch(`../api/classes_api.php?action=get_timetable&class_id=${selectedClassId}`);
    const result = await response.json();

    if (result.success) {
      document.getElementById("timetablePlaceholder").classList.add("hidden");
      document.getElementById("timetableContent").classList.remove("hidden");
      renderTimetable(result.data);
    } else {
      showNotification("Error loading timetable: " + result.message, "error");
    }
  } catch (error) {
    showNotification("Network error: " + error.message, "error");
  }
};

function renderTimetable(timetableData) {
  const tbody = document.querySelector("#timetableTab tbody");
  tbody.innerHTML = "";

  if (!timetableData || timetableData.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="text-center py-8 text-gray-500">
          <i class="fas fa-calendar-times text-4xl mb-4"></i>
          <p>No schedule found for this class.</p>
        </td>
      </tr>
    `;
    return;
  }

  // Define standard periods (you might want to fetch this from DB in a real app)
  const periods = [
    { time: "08:00 - 08:45", start: "08:00:00" },
    { time: "08:45 - 09:30", start: "08:45:00" },
    { time: "09:30 - 10:00", start: "09:30:00", label: "Break" }, // Break
    { time: "10:00 - 10:45", start: "10:00:00" },
    { time: "10:45 - 11:30", start: "10:45:00" },
    { time: "11:30 - 12:15", start: "11:30:00" },
    { time: "12:15 - 13:00", start: "12:15:00" }
  ];

  const days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];

  periods.forEach(period => {
    const row = document.createElement("tr");

    // Time column
    const timeCell = document.createElement("td");
    timeCell.className = "bg-nsklight p-3 font-semibold text-center";
    timeCell.textContent = period.time;
    row.appendChild(timeCell);

    // Day columns
    days.forEach(day => {
      const cell = document.createElement("td");
      cell.className = "p-2";

      // Find entry for this day and time
      // Note: This is a simple match. In production, you'd handle time ranges more robustly.
      const entry = timetableData.find(t =>
        t.day_of_week === day &&
        t.start_time.startsWith(period.start.substring(0, 5))
      );

      if (entry) {
        // Determine color based on subject (simple hash or predefined)
        const subjectColors = {
          'Mathematics': 'blue',
          'English': 'yellow',
          'Science': 'green',
          'History': 'purple',
          'Art': 'red',
          'PE': 'indigo'
        };
        const color = subjectColors[entry.subject_name] || 'blue';
        const teacherName = entry.teacher_first_name ? `${entry.teacher_first_name} ${entry.teacher_last_name}` : 'No Teacher';

        cell.innerHTML = `
          <div class="timetable-cell bg-${color}-100 border-l-4 border-${color}-500 p-3 rounded-lg">
            <p class="font-semibold text-sm">${entry.subject_name || 'Subject'}</p>
            <p class="text-xs text-gray-600">${teacherName}</p>
            <p class="text-xs text-gray-500">${entry.room || ''}</p>
          </div>
        `;
      } else if (period.label === "Break") {
        cell.innerHTML = `
          <div class="bg-gray-100 p-2 rounded text-center text-gray-500 text-sm">
            Break
          </div>
        `;
      }

      row.appendChild(cell);
    });

    tbody.appendChild(row);
  });
}

// Close create class modal handlers
document.addEventListener("DOMContentLoaded", function () {
  const closeCreateModal = document.getElementById("closeCreateModal");
  const cancelCreateBtn = document.getElementById("cancelCreateBtn");
  const createClassModal = document.getElementById("createClassModal");

  if (closeCreateModal) {
    closeCreateModal.addEventListener("click", closeCreateClassModal);
  }

  if (cancelCreateBtn) {
    cancelCreateBtn.addEventListener("click", closeCreateClassModal);
  }

  // Create class form submission
  const createClassForm = document.getElementById("createClassForm");
  if (createClassForm) {
    createClassForm.addEventListener("submit", async function (e) {
      e.preventDefault();
      const formData = new FormData(this);
      formData.append("action", "add_class");

      showNotification("Creating new class...");

      try {
        const response = await fetch("../api/classes_api.php", {
          method: "POST",
          body: formData,
        });
        const result = await response.json();

        if (result.success) {
          showNotification("Class created successfully!");
          closeCreateClassModal();
          createClassForm.reset();
          // Refresh the page after a short delay to show new class
          setTimeout(() => {
            window.location.reload();
          }, 1500);
        } else {
          showNotification("Error: " + result.message, "error");
        }
      } catch (error) {
        showNotification("Network error: " + error.message, "error");
      }
    });
  }

  // Delete Class Modal Handlers
  const deleteModal = document.getElementById('deleteClassModal');
  const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
  const deleteClassForm = document.getElementById('deleteClassForm');

  if (cancelDeleteBtn) {
    cancelDeleteBtn.addEventListener('click', function () {
      if (deleteModal) deleteModal.style.display = 'none';
    });
  }

  if (deleteModal) {
    deleteModal.addEventListener('click', function (e) {
      if (e.target === deleteModal) {
        deleteModal.style.display = 'none';
      }
    });
  }

  // Log form submission for debugging
  if (deleteClassForm) {
    deleteClassForm.addEventListener('submit', function () {
      console.log('Submitting delete form...');
    });
  }
});

// Delete class function (globally accessible)
window.confirmDeleteClass = function confirmDeleteClass(classId, className) {
  console.log('Delete requested for:', classId, className);

  const deleteModal = document.getElementById('deleteClassModal');
  const deleteClassNameSpan = document.getElementById('deleteClassName');
  const deleteClassIdInput = document.getElementById('deleteClassId');

  if (!deleteModal) {
    console.error('Delete modal not found!');
    alert('Error: Delete modal not found. Please refresh the page.');
    return;
  }

  if (deleteClassNameSpan) deleteClassNameSpan.textContent = className;
  if (deleteClassIdInput) deleteClassIdInput.value = classId;

  deleteModal.style.display = 'flex';
}
