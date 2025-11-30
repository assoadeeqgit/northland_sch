<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Northland Schools Kano - Authentication</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        background: 'hsl(222 47% 11%)',
                        foreground: 'hsl(210 40% 98%)',
                        primary: {
                            DEFAULT: 'hsl(217 91% 60%)',
                            foreground: 'hsl(0 0% 100%)'
                        },
                        secondary: {
                            DEFAULT: 'hsl(217 33% 17%)',
                            foreground: 'hsl(210 40% 98%)'
                        },
                        muted: {
                            DEFAULT: 'hsl(217 33% 17%)',
                            foreground: 'hsl(215 20% 65%)'
                        },
                        destructive: {
                            DEFAULT: 'hsl(0 63% 31%)',
                            foreground: 'hsl(210 40% 98%)'
                        },
                        success: 'hsl(142 76% 36%)',
                        warning: 'hsl(38 92% 50%)',
                        info: 'hsl(190 90% 40%)',
                        border: 'hsl(217 33% 17%)',
                        input: 'hsl(217 33% 17%)',
                        form: {
                            background: 'hsl(222 47% 7%)',
                            card: 'hsl(222 47% 9%)',
                            border: 'hsl(217 33% 17%)'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Base styles */
        body {
            background: linear-gradient(135deg, hsl(222 47% 11%) 0%, hsl(222 47% 7%) 100%);
            color: hsl(210 40% 98%);
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        /* Card flip animation */
        .flip-card {
            background-color: transparent;
            perspective: 1000px;
        }

        .flip-card-inner {
            position: relative;
            width: 100%;
            height: 100%;
            text-align: center;
            transition: transform 0.8s;
            transform-style: preserve-3d;
        }

        .flip-card.flipped .flip-card-inner {
            transform: rotateY(180deg);
        }

        .flip-card-front,
        .flip-card-back {
            position: absolute;
            width: 100%;
            height: 100%;
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            border-radius: 1rem;
            background: hsl(222 47% 9%);
            border: 1px solid hsl(217 33% 17%);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .flip-card-back {
            transform: rotateY(180deg);
        }

        /* Form elements */
        .input-field {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-field input,
        .input-field select,
        .input-field textarea {
            width: 100%;
            padding: 1rem;
            background: hsl(217 33% 17%);
            border: 1px solid hsl(217 33% 17%);
            border-radius: 0.5rem;
            color: hsl(210 40% 98%);
            transition: all 0.3s ease;
        }

        .input-field input:focus,
        .input-field select:focus,
        .input-field textarea:focus {
            outline: none;
            border-color: hsl(217 91% 60%);
            box-shadow: 0 0 0 2px hsl(217 91% 60% / 0.2);
        }

        .input-field input.error,
        .input-field select.error,
        .input-field textarea.error {
            border-color: hsl(0 63% 31%);
            box-shadow: 0 0 0 2px hsl(0 63% 31% / 0.2);
        }

        .input-field label {
            position: absolute;
            left: 1rem;
            top: 1rem;
            color: hsl(215 20% 65%);
            pointer-events: none;
            transition: all 0.3s ease;
            background: hsl(217 33% 17%);
            padding: 0 0.25rem;
        }

        .input-field input:focus+label,
        .input-field input:not(:placeholder-shown)+label,
        .input-field select:focus+label,
        .input-field select:not([value=""])+label,
        .input-field textarea:focus+label,
        .input-field textarea:not(:placeholder-shown)+label {
            top: -0.5rem;
            font-size: 0.75rem;
            color: hsl(217 91% 60%);
        }

        .input-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: hsl(215 20% 65%);
            cursor: pointer;
        }

        /* Role selection cards */
        .role-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid hsl(217 33% 17%);
            border-radius: 0.5rem;
            padding: 1.5rem 1rem;
            text-align: center;
        }

        .role-card:hover {
            transform: translateY(-5px);
            border-color: hsl(217 91% 60% / 0.5);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .role-card.selected {
            border-color: hsl(217 91% 60%);
            background: hsl(217 91% 60% / 0.1);
            box-shadow: 0 5px 15px hsl(217 91% 60% / 0.2);
        }

        /* Step indicator */
        .step-indicator {
            transition: all 0.3s ease;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            background: hsl(217 33% 17%);
            color: hsl(215 20% 65%);
        }

        .step-indicator.active {
            background: hsl(217 91% 60%);
            color: hsl(0 0% 100%);
        }

        .step-indicator.completed {
            background: hsl(142 76% 36%);
            color: white;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: hsl(217 91% 60%);
            color: hsl(0 0% 100%);
        }

        .btn-primary:hover:not(:disabled) {
            background: hsl(217 91% 60% / 0.9);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px hsl(217 91% 60% / 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: hsl(217 33% 17%);
            color: hsl(210 40% 98%);
        }

        .btn-secondary:hover {
            background: hsl(217 33% 17% / 0.8);
        }

        .btn-success {
            background: hsl(142 76% 36%);
            color: white;
        }

        .btn-success:hover {
            background: hsl(142 76% 36% / 0.9);
        }

        /* Notifications */
        .notification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1000;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            color: white;
            display: flex;
            align-items: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            animation: slideIn 0.3s ease;
            max-width: 400px;
        }

        .notification-success {
            background: hsl(142 76% 36%);
        }

        .notification-error {
            background: hsl(0 63% 31%);
        }

        .notification-warning {
            background: hsl(38 92% 50%);
            color: black;
        }

        .notification-info {
            background: hsl(190 90% 40%);
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: hsl(217 91% 60%);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {

            .flip-card-front,
            .flip-card-back {
                padding: 1.5rem;
            }

            .role-card {
                padding: 1rem 0.5rem;
            }

            .btn {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }
        }

        /* Background pattern */
        .bg-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.03;
            background-image:
                radial-gradient(circle at 25% 25%, rgba(255, 255, 255, 0.2) 2px, transparent 0),
                radial-gradient(circle at 75% 75%, rgba(255, 255, 255, 0.15) 1px, transparent 0);
            background-size: 50px 50px, 30px 30px;
            z-index: -1;
        }

        /* Floating label fix */
        .input-field input::placeholder,
        .input-field textarea::placeholder {
            color: transparent;
        }

        .input-field input:placeholder-shown+label,
        .input-field textarea:placeholder-shown+label {
            top: 1rem;
            font-size: 1rem;
            color: hsl(215 20% 65%);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">
    <!-- Background Pattern -->
    <div class="bg-pattern"></div>

    <!-- Main Container -->
    <div class="w-full max-w-6xl mx-auto relative z-10">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="flex items-center justify-center mb-4">
                <div class="w-12 h-12 bg-primary rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-graduation-cap text-white text-xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-foreground">Northland Schools Kano</h1>
            </div>
            <p class="text-muted-foreground text-lg">School Management System</p>
        </div>

        <!-- Authentication Card -->
        <div class="flip-card w-full max-w-4xl mx-auto h-[600px]" id="authCard">
            <div class="flip-card-inner">
                <!-- Sign In Side (Front) -->
                <div class="flip-card-front p-8">
                    <div class="h-full flex flex-col justify-center">
                        <div class="text-center mb-8">
                            <h2 class="text-2xl font-bold text-foreground mb-2">Welcome Back</h2>
                            <p class="text-muted-foreground">Sign in to your account</p>
                        </div>

                        <form id="signInForm" class="space-y-6">
                            <!-- Email Field -->
                            <div class="input-field">
                                <input
                                    type="email"
                                    id="signInEmail"
                                    placeholder=" "
                                    required>
                                <label for="signInEmail">Email Address</label>
                                <i class="input-icon fas fa-envelope"></i>
                            </div>

                            <!-- Password Field -->
                            <div class="input-field">
                                <input
                                    type="password"
                                    id="signInPassword"
                                    placeholder=" "
                                    required>
                                <label for="signInPassword">Password</label>
                                <i class="input-icon fas fa-lock" id="toggleSignInPassword"></i>
                            </div>

                            <!-- Remember Me & Forgot Password -->
                            <div class="flex items-center justify-between">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" class="w-4 h-4 text-primary bg-input border-border rounded focus:ring-primary mr-2">
                                    <span class="text-sm text-muted-foreground">Remember me</span>
                                </label>
                                <a href="#" class="text-sm text-primary hover:underline">Forgot password?</a>
                            </div>

                            <!-- Sign In Button -->
                            <button type="submit" class="btn btn-primary w-full">
                                <i class="fas fa-sign-in-alt mr-2"></i>
                                Sign In
                            </button>
                        </form>

                        <!-- Switch to Sign Up -->
                        <div class="text-center mt-6">
                            <p class="text-muted-foreground">
                                Don't have an account?
                                <button onclick="flipCard()" class="text-primary hover:underline font-semibold ml-1">
                                    Sign Up
                                </button>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Sign Up Side (Back) -->
                <div class="flip-card-back p-8">
                    <div class="h-full flex flex-col">
                        <div class="text-center mb-6">
                            <h2 class="text-2xl font-bold text-foreground mb-2">Create Account</h2>
                            <p class="text-muted-foreground">Join Northland Schools Kano</p>
                        </div>

                        <!-- Step Indicator -->
                        <div class="flex justify-center mb-6">
                            <div class="flex items-center space-x-2">
                                <div class="step-indicator active" id="step1">1</div>
                                <div class="w-8 h-0.5 bg-border"></div>
                                <div class="step-indicator" id="step2">2</div>
                                <div class="w-8 h-0.5 bg-border"></div>
                                <div class="step-indicator" id="step3">3</div>
                            </div>
                        </div>

                        <form id="signUpForm" class="space-y-2 flex-grow">
                            <!-- Step 1: Role Selection -->
                            <div id="roleStep" class="step-content">
                                <h3 class="text-lg font-semibold text-foreground text-center mb-4">Select Your Role</h3>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                    <div class="role-card" data-role="student">
                                        <i class="fas fa-user-graduate text-2xl text-primary mb-2"></i>
                                        <p class="text-sm font-medium text-foreground">Student</p>
                                    </div>
                                    <div class="role-card" data-role="teacher">
                                        <i class="fas fa-chalkboard-teacher text-2xl text-primary mb-2"></i>
                                        <p class="text-sm font-medium text-foreground">Teacher</p>
                                    </div>
                                    <!-- <div class="role-card" data-role="parent">
                                        <i class="fas fa-users text-2xl text-primary mb-2"></i>
                                        <p class="text-sm font-medium text-foreground">Parent</p>
                                    </div> -->
                                    <div class="role-card" data-role="staff">
                                        <i class="fas fa-user-tie text-2xl text-primary mb-2"></i>
                                        <p class="text-sm font-medium text-foreground">Staff</p>
                                    </div>
                                    <div class="role-card" data-role="admin">
                                        <i class="fas fa-user-shield text-2xl text-primary mb-2"></i>
                                        <p class="text-sm font-medium text-foreground">Admin</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Basic Information -->
                            <div id="basicStep" class="step-content hidden">
                                <h3 class="text-lg font-semibold text-foreground text-center mb-4">Basic Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="input-field">
                                        <input type="text" id="firstName" placeholder=" " required>
                                        <label for="firstName">First Name</label>
                                    </div>
                                    <div class="input-field">
                                        <input type="text" id="lastName" placeholder=" " required>
                                        <label for="lastName">Last Name</label>
                                    </div>
                                    <div class="input-field">
                                        <input type="email" id="signUpEmail" placeholder=" " required>
                                        <label for="signUpEmail">Email Address</label>
                                    </div>
                                    <div class="input-field">
                                        <input type="tel" id="phone" placeholder=" " required>
                                        <label for="phone">Phone Number</label>
                                    </div>
                                    <div class="input-field">
                                        <input type="password" id="signUpPassword" placeholder=" " required>
                                        <label for="signUpPassword">Password</label>
                                        <i class="input-icon fas fa-lock" id="toggleSignUpPassword"></i>
                                    </div>
                                    <div class="input-field">
                                        <input type="password" id="confirmPassword" placeholder=" " required>
                                        <label for="confirmPassword">Confirm Password</label>
                                        <i class="input-icon fas fa-lock" id="toggleConfirmPassword"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: Role-Specific Information -->
                            <div id="specificStep" class="step-content hidden">
                                <h3 class="text-lg font-semibold text-foreground text-center mb-4">Additional Information</h3>
                                <div id="roleSpecificFields" class="space-y-4">
                                    <!-- Dynamic content will be inserted here -->
                                </div>
                            </div>

                            <!-- Navigation Buttons -->
                            <div class="flex justify-between pt-4 mt-auto">
                                <button type="button" id="prevBtn" class="btn btn-secondary hidden" onclick="AuthManager.previousStep()">
                                    <i class="fas fa-arrow-left mr-2"></i>Previous
                                </button>
                                <button type="button" id="nextBtn" class="btn btn-primary ml-auto" onclick="AuthManager.nextStep()" disabled>
                                    Next<i class="fas fa-arrow-right ml-2"></i>
                                </button>
                                <button type="submit" id="submitBtn" class="btn btn-success ml-auto hidden">
                                    <i class="fas fa-user-plus mr-2"></i>Create Account
                                </button>
                            </div>
                        </form>

                        <!-- Switch to Sign In -->
                        <div class="text-center mt-4">
                            <p class="text-muted-foreground text-sm">
                                Already have an account?
                                <button onclick="flipCard()" class="text-primary hover:underline font-semibold ml-1">
                                    Sign In
                                </button>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay hidden">
        <div class="text-center">
            <div class="spinner"></div>
            <p class="mt-4 text-foreground">Processing...</p>
        </div>
    </div>
    <script>
        // Enhanced Authentication Manager with Backend Integration
        const AuthManager = {
            currentStep: 1,
            selectedRole: '',
            apiBase: 'auth.php',

            // Role-specific field configurations
            roleFields: {
                student: [{
                        type: 'date',
                        id: 'dateOfBirth',
                        label: 'Date of Birth',
                        required: true
                    },
                    {
                        type: 'select',
                        id: 'gender',
                        label: 'Gender',
                        options: ['Male', 'Female', 'Other'],
                        required: true
                    },
                    {
                        type: 'select',
                        id: 'classLevel',
                        label: 'Class Level',
                        options: ['JSS 1', 'JSS 2', 'JSS 3', 'SS 1', 'SS 2', 'SS 3'],
                        required: true
                    },
                    {
                        type: 'text',
                        id: 'parentName',
                        label: 'Parent/Guardian Name',
                        required: true
                    },
                    {
                        type: 'tel',
                        id: 'parentPhone',
                        label: 'Parent/Guardian Phone',
                        required: true
                    },
                    {
                        type: 'text',
                        id: 'previousSchool',
                        label: 'Previous School',
                        required: false
                    }
                ],
                teacher: [{
                        type: 'text',
                        id: 'qualification',
                        label: 'Highest Qualification',
                        required: true
                    },
                    {
                        type: 'text',
                        id: 'subjects',
                        label: 'Subject Specialization',
                        required: true
                    },
                    {
                        type: 'select',
                        id: 'experience',
                        label: 'Years of Experience',
                        options: ['0-2 years', '3-5 years', '6-10 years', '10+ years'],
                        required: true
                    },
                    {
                        type: 'select',
                        id: 'department',
                        label: 'Department',
                        options: ['Science', 'Arts', 'Commercial', 'Technical'],
                        required: true
                    },
                    {
                        type: 'select',
                        id: 'employmentType',
                        label: 'Employment Type',
                        options: ['Full-time', 'Part-time', 'Contract'],
                        required: true
                    }
                ],
                staff: [{
                        type: 'select',
                        id: 'department',
                        label: 'Department',
                        options: ['Administration', 'Maintenance', 'Security', 'Kitchen', 'Library'],
                        required: true
                    },
                    {
                        type: 'text',
                        id: 'position',
                        label: 'Job Title/Position',
                        required: true
                    },
                    {
                        type: 'select',
                        id: 'employmentType',
                        label: 'Employment Type',
                        options: ['Full-time', 'Part-time', 'Contract'],
                        required: true
                    },
                    {
                        type: 'text',
                        id: 'supervisor',
                        label: 'Supervisor Name',
                        required: false
                    }
                ],
                admin: [{
                        type: 'select',
                        id: 'adminLevel',
                        label: 'Admin Level',
                        options: ['Super Admin', 'Admin', 'Sub-Admin'],
                        required: true
                    },
                    {
                        type: 'text',
                        id: 'department',
                        label: 'Department Access',
                        required: true
                    },
                    {
                        type: 'textarea',
                        id: 'permissions',
                        label: 'Special Permissions',
                        required: false
                    }
                ]
            },

            // Initialize the authentication system
            init() {
                this.setupRoleSelection();
                this.setupFormHandlers();
                this.setupPasswordToggles();
                this.setupFloatingLabels();
                this.updateStepDisplay();

                // Check if user is already logged in
                this.checkAuthentication();

                // Add periodic session checking (every 5 minutes)
                setInterval(() => {
                    this.checkAndRefreshSession();
                }, 300000); // 5 minutes
            },

            // Setup role selection
            setupRoleSelection() {
                const roleCards = document.querySelectorAll('.role-card');
                roleCards.forEach(card => {
                    card.addEventListener('click', () => {
                        // Remove previous selection
                        roleCards.forEach(c => c.classList.remove('selected'));
                        // Add selection to clicked card
                        card.classList.add('selected');
                        this.selectedRole = card.dataset.role;

                        // Enable next button
                        document.getElementById('nextBtn').disabled = false;
                    });
                });
            },

            // Setup form submission handlers
            setupFormHandlers() {
                document.getElementById('signInForm').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.handleSignIn();
                });

                document.getElementById('signUpForm').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.handleSignUp();
                });
            },

            // Setup password toggle functionality
            setupPasswordToggles() {
                document.getElementById('toggleSignInPassword').addEventListener('click', () => {
                    this.togglePassword('signInPassword', 'toggleSignInPassword');
                });

                document.getElementById('toggleSignUpPassword').addEventListener('click', () => {
                    this.togglePassword('signUpPassword', 'toggleSignUpPassword');
                });

                document.getElementById('toggleConfirmPassword').addEventListener('click', () => {
                    this.togglePassword('confirmPassword', 'toggleConfirmPassword');
                });
            },

            // Setup floating labels
            setupFloatingLabels() {
                // Initialize labels based on current values
                document.querySelectorAll('.input-field input, .input-field textarea').forEach(field => {
                    if (field.value) {
                        field.nextElementSibling.classList.add('active');
                    }

                    field.addEventListener('input', () => {
                        if (field.value) {
                            field.nextElementSibling.classList.add('active');
                        } else {
                            field.nextElementSibling.classList.remove('active');
                        }
                    });
                });
            },

            // Toggle password visibility
            togglePassword(fieldId, iconId) {
                const field = document.getElementById(fieldId);
                const icon = document.getElementById(iconId);

                if (field.type === 'password') {
                    field.type = 'text';
                    icon.classList.replace('fa-lock', 'fa-unlock');
                } else {
                    field.type = 'password';
                    icon.classList.replace('fa-unlock', 'fa-lock');
                }
            },

            // Navigate to next step
            nextStep() {
                if (this.currentStep === 1 && !this.selectedRole) {
                    this.showNotification('Please select your role', 'warning');
                    return;
                }

                if (this.currentStep === 2 && !this.validateBasicInfo()) {
                    return;
                }

                if (this.currentStep < 3) {
                    this.currentStep++;
                    this.updateStepDisplay();

                    if (this.currentStep === 3) {
                        this.generateRoleSpecificFields();
                    }
                }
            },

            // Navigate to previous step
            previousStep() {
                if (this.currentStep > 1) {
                    this.currentStep--;
                    this.updateStepDisplay();
                }
            },

            // Update step display
            updateStepDisplay() {
                // Hide all steps
                document.querySelectorAll('.step-content').forEach(step => {
                    step.classList.add('hidden');
                });

                // Show current step
                const steps = ['roleStep', 'basicStep', 'specificStep'];
                document.getElementById(steps[this.currentStep - 1]).classList.remove('hidden');

                // Update step indicators
                for (let i = 1; i <= 3; i++) {
                    const indicator = document.getElementById(`step${i}`);
                    indicator.classList.remove('active', 'completed');

                    if (i < this.currentStep) {
                        indicator.classList.add('completed');
                    } else if (i === this.currentStep) {
                        indicator.classList.add('active');
                    }
                }

                // Update navigation buttons
                const prevBtn = document.getElementById('prevBtn');
                const nextBtn = document.getElementById('nextBtn');
                const submitBtn = document.getElementById('submitBtn');

                prevBtn.classList.toggle('hidden', this.currentStep === 1);
                nextBtn.classList.toggle('hidden', this.currentStep === 3);
                submitBtn.classList.toggle('hidden', this.currentStep !== 3);
            },

            // Generate role-specific fields
            generateRoleSpecificFields() {
                const container = document.getElementById('roleSpecificFields');
                container.innerHTML = '';

                if (!this.selectedRole || !this.roleFields[this.selectedRole]) return;

                const fields = this.roleFields[this.selectedRole];
                const gridClass = fields.length > 2 ? 'grid grid-cols-1 md:grid-cols-2 gap-4' : 'space-y-4';
                container.className = gridClass;

                fields.forEach(field => {
                    const fieldHtml = this.createFieldHtml(field);
                    container.insertAdjacentHTML('beforeend', fieldHtml);
                });

                // Re-setup floating labels for new fields
                setTimeout(() => this.setupFloatingLabels(), 100);
            },

            // Create field HTML
            createFieldHtml(field) {
                const {
                    type,
                    id,
                    label,
                    options,
                    required
                } = field;

                if (type === 'select') {
                    return `
                    <div class="input-field">
                        <select id="${id}" ${required ? 'required' : ''}>
                            <option value="" selected disabled>Select ${label}</option>
                            ${options.map(option => `<option value="${option}">${option}</option>`).join('')}
                        </select>
                        <label for="${id}">${label}</label>
                    </div>
                `;
                } else if (type === 'textarea') {
                    return `
                    <div class="input-field">
                        <textarea id="${id}" rows="3" placeholder=" " ${required ? 'required' : ''}></textarea>
                        <label for="${id}">${label}</label>
                    </div>
                `;
                } else {
                    return `
                    <div class="input-field">
                        <input type="${type}" id="${id}" placeholder=" " ${required ? 'required' : ''}>
                        <label for="${id}">${label}</label>
                    </div>
                `;
                }
            },

            // Validate basic information
            validateBasicInfo() {
                const requiredFields = ['firstName', 'lastName', 'signUpEmail', 'phone', 'signUpPassword', 'confirmPassword'];
                let isValid = true;

                // Clear previous errors
                document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));

                requiredFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (!field.value.trim()) {
                        field.classList.add('error');
                        isValid = false;
                    }
                });

                // Email validation
                const email = document.getElementById('signUpEmail').value;
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (email && !emailRegex.test(email)) {
                    document.getElementById('signUpEmail').classList.add('error');
                    this.showNotification('Please enter a valid email address', 'warning');
                    isValid = false;
                }

                // Password strength validation
                const password = document.getElementById('signUpPassword').value;
                if (password && password.length < 8) {
                    document.getElementById('signUpPassword').classList.add('error');
                    this.showNotification('Password must be at least 8 characters long', 'warning');
                    isValid = false;
                }

                // Check password match
                const confirmPassword = document.getElementById('confirmPassword').value;
                if (password !== confirmPassword) {
                    document.getElementById('confirmPassword').classList.add('error');
                    this.showNotification('Passwords do not match', 'error');
                    isValid = false;
                }

                if (!isValid) {
                    this.showNotification('Please fill in all required fields correctly', 'warning');
                }

                return isValid;
            },

            // API call helper
            async apiCall(action, data = {}) {
                try {
                    const response = await fetch(this.apiBase, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: action,
                            ...data
                        })
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    return await response.json();
                } catch (error) {
                    console.error('API Call Error:', error);
                    throw error;
                }
            },

            // Handle sign in with backend
            async handleSignIn() {
                const email = document.getElementById('signInEmail').value;
                const password = document.getElementById('signInPassword').value;

                if (!email || !password) {
                    this.showNotification('Please fill in all fields', 'warning');
                    return;
                }

                this.showLoading(true);

                try {
                    const response = await this.apiCall('login', {
                        email: email,
                        password: password
                    });

                    this.showLoading(false);

                    if (response.success) {
                        this.showNotification('Sign in successful! Redirecting...', 'success');

                        // Store user session
                        this.storeUserSession(response.data.user, response.data.session_token);

                        // Redirect to dashboard
                        setTimeout(() => {
                            this.redirectToDashboard(response.data.user.user_type);
                        }, 1500);
                    } else {
                        this.showNotification(response.message, 'error');
                    }
                } catch (error) {
                    this.showLoading(false);
                    this.showNotification('Login failed. Please check your connection and try again.', 'error');
                    console.error('Login error:', error);
                }
            },

            // Handle sign up with backend
            async handleSignUp() {
                if (!this.validateBasicInfo()) return;

                this.showLoading(true);

                // Collect form data
                const formData = {
                    user_type: this.selectedRole,
                    first_name: document.getElementById('firstName').value,
                    last_name: document.getElementById('lastName').value,
                    email: document.getElementById('signUpEmail').value,
                    phone: document.getElementById('phone').value,
                    password: document.getElementById('signUpPassword').value
                };

                // Add role-specific data
                if (this.roleFields[this.selectedRole]) {
                    this.roleFields[this.selectedRole].forEach(field => {
                        const element = document.getElementById(field.id);
                        if (element) {
                            // Map field IDs to backend expected names
                            const fieldName = this.mapFieldName(field.id);
                            formData[fieldName] = element.value;
                        }
                    });
                }

                try {
                    const response = await this.apiCall('register', {
                        data: formData
                    });

                    this.showLoading(false);

                    if (response.success) {
                        this.showNotification('Account created successfully! Please check your email for verification.', 'success');

                        // Reset form and flip back to sign in
                        setTimeout(() => {
                            this.resetRegistrationForm();
                            flipCard();
                        }, 2000);
                    } else {
                        this.showNotification(response.message, 'error');
                    }
                } catch (error) {
                    this.showLoading(false);
                    this.showNotification('Registration failed. Please check your connection and try again.', 'error');
                    console.error('Registration error:', error);
                }
            },



            // Map frontend field names to backend expected names
            mapFieldName(fieldId) {
                const fieldMap = {
                    'dateOfBirth': 'dateOfBirth',
                    'gender': 'gender',
                    'classLevel': 'classLevel',
                    'parentName': 'parentName',
                    'parentPhone': 'parentPhone',
                    'previousSchool': 'previousSchool',
                    'qualification': 'qualification',
                    'subjects': 'subjects',
                    'experience': 'experience',
                    'department': 'department',
                    'employmentType': 'employmentType',
                    'position': 'position',
                    'supervisor': 'supervisor',
                    'adminLevel': 'adminLevel',
                    'permissions': 'permissions'
                };

                return fieldMap[fieldId] || fieldId;
            },

            // Store user session
            storeUserSession(user, token) {
                localStorage.setItem('user', JSON.stringify(user));
                localStorage.setItem('session_token', token);
                localStorage.setItem('login_time', new Date().toISOString());
            },

            // Check if user is already logged in
            async checkAuthentication() {
                // First check if there's a token in the URL (from redirect)
                const urlParams = new URLSearchParams(window.location.search);
                const urlToken = urlParams.get('token');

                if (urlToken) {
                    localStorage.setItem('session_token', urlToken);
                    // Clean URL
                    window.history.replaceState({}, document.title, window.location.pathname);
                }

                const token = localStorage.getItem('session_token');
                if (!token) return false;

                try {
                    const response = await this.apiCall('check_auth', {
                        token: token
                    });

                    if (response.success) {
                        // User is authenticated, redirect to dashboard
                        const user = JSON.parse(localStorage.getItem('user') || '{}');
                        if (!window.location.href.includes('login-form.php')) {
                            this.redirectToDashboard(user.user_type);
                        }
                        return true;
                    }
                } catch (error) {
                    console.error('Auth check failed:', error);
                }

                // Clear invalid session
                this.clearUserSession();
                return false;
            },

            // Clear user session
            clearUserSession() {
                localStorage.removeItem('user');
                localStorage.removeItem('session_token');
                localStorage.removeItem('login_time');
            },

            // Redirect based on user role
            redirectToDashboard(userType) {
                // Store session data for immediate access
                sessionStorage.setItem('user_authenticated', 'true');
                sessionStorage.setItem('user_type', userType);

                const dashboards = {
                    'admin': 'dashboard/admin-dashboard.php',
                    'teacher': 'dashboard/teacher-dashboard.php',
                    'student': 'dashboard/student-dashboard.php',
                    'staff': 'dashboard/staff-dashboard.php',
                    'principal': 'dashboard/admin-dashboard.php'
                };

                const dashboard = dashboards[userType] || 'dashboard/default-dashboard.html';

                // Add token to URL for session verification
                const token = localStorage.getItem('session_token');
                const redirectUrl = token ? `${dashboard}?token=${encodeURIComponent(token)}` : dashboard;

                window.location.href = redirectUrl;
            },

            // Add this method to check and maintain session
            checkAndRefreshSession() {
                const token = localStorage.getItem('session_token');
                if (!token) return false;

                // Verify token with server periodically
                this.apiCall('check_auth', {
                        token: token
                    })
                    .then(response => {
                        if (!response.success) {
                            this.clearUserSession();
                            if (!window.location.href.includes('login-form.php')) {
                                window.location.href = '../login-form.php';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Session check failed:', error);
                    });
            },

            // Show loading overlay
            showLoading(show) {
                const overlay = document.getElementById('loadingOverlay');
                overlay.classList.toggle('hidden', !show);
            },

            // Show notification
            showNotification(message, type = 'info') {
                // Remove existing notifications
                document.querySelectorAll('.notification').forEach(n => n.remove());

                // Create notification element
                const notification = document.createElement('div');
                notification.className = `notification notification-${type}`;

                notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${
                        type === 'success' ? 'fa-check-circle' :
                        type === 'error' ? 'fa-exclamation-circle' :
                        type === 'warning' ? 'fa-exclamation-triangle' :
                        'fa-info-circle'
                    } mr-2"></i>
                    <span>${message}</span>
                    <button class="ml-4 hover:opacity-70" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

                document.body.appendChild(notification);

                // Auto remove after 5 seconds
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 5000);
            },

            // Reset registration form
            resetRegistrationForm() {
                this.currentStep = 1;
                this.selectedRole = '';
                document.getElementById('signUpForm').reset();
                document.querySelectorAll('.role-card').forEach(card => {
                    card.classList.remove('selected');
                });
                document.getElementById('nextBtn').disabled = true;
                this.updateStepDisplay();

                // Reset floating labels
                document.querySelectorAll('.input-field label').forEach(label => {
                    label.classList.remove('active');
                });
            }
        };

        // Flip card animation
        function flipCard() {
            const card = document.getElementById('authCard');
            card.classList.toggle('flipped');

            // Reset registration form when switching to sign up
            if (card.classList.contains('flipped')) {
                AuthManager.resetRegistrationForm();
            }
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            AuthManager.init();
        });
    </script>
</body>

</html>