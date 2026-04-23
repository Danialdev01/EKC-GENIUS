<?php
session_start();

require_once __DIR__ . '/config/connect.php';

// Load teachers for select
$stmtT = $pdo->prepare("SELECT teacher_id, teacher_name FROM teachers WHERE teacher_status = 1 ORDER BY teacher_name");
$stmtT->execute();
$teacherRows = $stmtT->fetchAll(PDO::FETCH_ASSOC);

// Load students for select
$stmtS = $pdo->prepare("SELECT student_id, student_name, student_parent_name FROM students WHERE student_status = 1 ORDER BY student_name");
$stmtS->execute();
$studentRows = $stmtS->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include 'components/header.php'; ?>

    <!-- Navbar -->
    <nav class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <a href="/" class="flex items-center gap-2 font-poppins text-xl font-bold select-none flex-shrink-0">
                <span class="text-3xl">🎓</span>
                <span class="text-slate-800">EKC Genius</span>
                <span class="text-trust-600 text-xs font-normal text-slate-500">Sdn. Bhd.</span>
            </a>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center gap-6">
                <a href="#about" class="font-inter text-sm font-medium text-slate-500 hover:text-trust-600 transition-colors">About</a>
                <a href="#contact" class="font-inter text-sm font-medium text-slate-500 hover:text-trust-600 transition-colors">Contact</a>
            </div>

            <!-- Mobile Menu Button -->
            <button id="mobileMenuBtn" class="md:hidden p-2 text-slate-500 hover:text-trust-600 transition-colors" aria-label="Toggle menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path id="menuIcon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
    </nav>

    <!-- Mobile Menu Dropdown -->
    <div id="mobileMenu" class="hidden md:hidden bg-white border-b border-slate-200 shadow-lg">
        <div class="px-4 py-3 space-y-2">
            <a href="#about" class="mobile-menu-link block py-2 font-inter text-sm font-medium text-slate-600 hover:text-trust-600 transition-colors">About</a>
            <a href="#contact" class="mobile-menu-link block py-2 font-inter text-sm font-medium text-slate-600 hover:text-trust-600 transition-colors">Contact</a>
        </div>
    </div>

    <!-- Hero Section -->
    <section id="login" class="bg-gradient-to-br from-slate-50 via-white to-edu-50 py-16 md:py-20 lg:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row items-center gap-12 lg:gap-16">
                <div class="flex-1 text-center lg:text-left max-w-xl mx-auto lg:mx-0">
                    <div class="inline-flex items-center gap-2 bg-growth-50 border border-growth-200 text-growth-700 font-inter text-xs font-medium px-4 py-2 rounded-full mb-6">
                        <span class="w-1.5 h-1.5 rounded-full bg-growth-500"></span>
                        Perak • Private Kindergarten & Childcare
                    </div>

                    <h1 class="font-poppins text-4xl md:text-5xl font-bold text-slate-800 leading-tight mb-5">
                        Empowering Early<br>
                        <span class="text-trust-600">Childhood Education</span>
                    </h1>

                    <p class="font-inter text-lg text-slate-500 leading-relaxed mb-8 max-w-md mx-auto lg:mx-0">
                        Specialized kindergarten and childcare services in Perak, leveraging IR4.0 technology for modern early childhood education management.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start">
                        <a href="#features" class="border-2 border-slate-200 hover:border-trust-300 text-slate-700 hover:text-trust-600 font-inter font-medium px-8 py-3.5 rounded-full hover:-translate-y-0.5 transition-all duration-300 text-center">
                            Learn More
                        </a>
                    </div>

                </div>

                <div class="w-full max-w-md mx-auto lg:mx-0 lg:w-96">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-login p-8">
                        <div class="text-center mb-6">
                            <div class="w-12 h-12 rounded-full bg-trust-50 flex items-center justify-center text-2xl mb-3 mx-auto">
                                🎓
                            </div>
                            <h3 class="font-poppins text-xl font-semibold text-slate-800 mb-1">Welcome Back</h3>
                            <p class="font-inter text-sm text-slate-500">Login to your account</p>
                        </div>

                        <form id="loginForm" class="space-y-4">
                            <div>
                                <label class="block font-inter text-sm font-medium text-slate-700 mb-2">I am a</label>
                                <select id="roleSelect" name="role" class="w-full bg-slate-50 text-slate-800 font-inter text-base px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:border-trust-500 focus:ring-4 focus:ring-trust-500/20 transition-all cursor-pointer">
                                    <option value="">Select your role</option>
                                    <option value="admin">Administrator</option>
                                    <option value="teacher">Teacher</option>
                                    <option value="parent">Parent</option>
                                </select>
                            </div>

                            <div id="adminFields" class="hidden space-y-3">
                                <div>
                                    <label class="block font-inter text-sm font-medium text-slate-700 mb-2">Username</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">👤</span>
                                        <input type="text" name="username" class="w-full bg-slate-50 text-slate-800 font-inter text-base pl-11 pr-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:border-trust-500 focus:ring-4 focus:ring-trust-500/20 transition-all" placeholder="Enter username">
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-inter text-sm font-medium text-slate-700 mb-2">Password</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">🔒</span>
                                        <input type="password" name="password" class="w-full bg-slate-50 text-slate-800 font-inter text-base pl-11 pr-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:border-trust-500 focus:ring-4 focus:ring-trust-500/20 transition-all" placeholder="Enter password">
                                    </div>
                                </div>
                            </div>

                            <div id="teacherFields" class="hidden space-y-3">
                                <div>
                                    <label class="block font-inter text-sm font-medium text-slate-700 mb-2">Teacher Name</label>
                                    <input
                                        id="teacherSearch"
                                        type="text"
                                        placeholder="🔍 Search teacher..."
                                        class="w-full bg-slate-50 text-slate-800 font-inter text-sm px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all mb-2"
                                        oninput="filterSelect('teacherSelect', this.value)"
                                        autocomplete="off"
                                    >
                                    <select
                                        id="teacherSelect"
                                        name="teacher_id"
                                        size="5"
                                        class="w-full bg-white text-slate-800 font-inter text-sm px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all"
                                        onchange="onTeacherSelect(this)"
                                    >
                                        <?php foreach ($teacherRows as $t): ?>
                                        <option value="<?= (int)$t['teacher_id'] ?>"><?= htmlspecialchars($t['teacher_name']) ?></option>
                                        <?php endforeach; ?>
                                        <?php if (empty($teacherRows)): ?>
                                        <option disabled>— No teachers found —</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-inter text-sm font-medium text-slate-700 mb-2">Passkey</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">🔑</span>
                                        <input type="password" name="passkey" class="w-full bg-slate-50 text-slate-800 font-inter text-base pl-11 pr-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:border-trust-500 focus:ring-4 focus:ring-trust-500/20 transition-all" placeholder="Enter passkey">
                                    </div>
                                </div>
                            </div>

                            <div id="parentFields" class="hidden space-y-3">
                                <div>
                                    <label class="block font-inter text-sm font-medium text-slate-700 mb-2">Child's IC Number</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">🪪</span>
                                        <input 
                                            type="text" 
                                            name="student_ic" 
                                            id="studentIcInput"
                                            class="w-full bg-slate-50 text-slate-800 font-inter text-base pl-11 pr-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:border-trust-500 focus:ring-4 focus:ring-trust-500/20 transition-all" 
                                            placeholder="Enter child's IC (e.g. 060802030010)"
                                            maxlength="12"
                                            autocomplete="off"
                                            onkeydown="if(event.key!=='Backspace' && event.key!=='Delete' && event.key!=='ArrowLeft' && event.key!=='ArrowRight' && event.key!=='Tab' && isNaN(event.key)) event.preventDefault();"
                                            oninput="this.value=this.value.replace(/[^0-9]/g,'');if(this.value.length>12)this.value=this.value.slice(0,12);document.getElementById('submitBtn').disabled=this.value.length<12;"
                                            onpaste="event.preventDefault();this.value=event.clipboardData.getData('text').replace(/[^0-9]/g,'').slice(0,12);document.getElementById('submitBtn').disabled=this.value.length<12;"
                                        >
                                    </div>
                                    <p class="text-xs text-slate-400 mt-1.5">Enter IC number without dashes (-)</p>
                                </div>
                            </div>

                            <div id="message" class="hidden font-inter text-sm text-red-500 text-center"></div>

                            <button type="submit" id="submitBtn" class="w-full bg-trust-600 hover:bg-trust-700 text-white font-inter text-base font-medium py-3.5 px-6 rounded-xl shadow-button hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                Sign In
                            </button>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-16 md:py-20 bg-gradient-to-br from-slate-50 via-white to-edu-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <div class="order-2 lg:order-1">
                    <img src="https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?w=800&h=600&fit=crop" alt="Kindergarten classroom with children learning" class="w-full rounded-2xl shadow-lg object-cover">
                </div>
                <div class="order-1 lg:order-2">
                    <h2 class="font-poppins text-2xl md:text-3xl font-semibold text-slate-800 mb-4">About EKC Genius</h2>
                    <p class="font-inter text-base text-slate-500 leading-relaxed mb-4">
                        EKC Genius Sdn. Bhd. is a registered private kindergarten and childcare center located in Perak, Malaysia. Established in October 2023, we are committed to providing quality early childhood education in a nurturing and stimulating environment.
                    </p>
                    <p class="font-inter text-base text-slate-500 leading-relaxed mb-6">
                        Our programs focus on holistic development, preparing children for primary school through interactive learning approaches and modern educational technology.
                    </p>
                    <div class="flex flex-wrap gap-4">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-trust-100 flex items-center justify-center text-trust-600">✓</span>
                            <span class="font-inter text-sm text-slate-600">Qualified Educators</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-trust-100 flex items-center justify-center text-trust-600">✓</span>
                            <span class="font-inter text-sm text-slate-600">Safe Environment</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-full bg-trust-100 flex items-center justify-center text-trust-600">✓</span>
                            <span class="font-inter text-sm text-slate-600">Modern Curriculum</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
        <!-- Contact Section -->
    <section id="contact" class="py-16 md:py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="font-poppins text-2xl md:text-3xl font-semibold text-slate-800 mb-4">Contact Us</h2>
                <p class="font-inter text-base text-slate-500 max-w-xl mx-auto">Get in touch with us for enquiries about our kindergarten programs.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-card">
                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-trust-100 flex items-center justify-center text-xl flex-shrink-0">📍</div>
                            <div>
                                <h3 class="font-poppins text-base font-semibold text-slate-800 mb-1">Location</h3>
                                <p class="font-inter text-sm text-slate-500">Perak, Malaysia</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-trust-100 flex items-center justify-center text-xl flex-shrink-0">📞</div>
                            <div>
                                <h3 class="font-poppins text-base font-semibold text-slate-800 mb-1">Phone</h3>
                                <a href="tel:+60123456789" class="font-inter text-sm text-trust-600 hover:text-trust-700">+60 12 345 6789</a>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-full bg-trust-100 flex items-center justify-center text-xl flex-shrink-0">✉️</div>
                            <div>
                                <h3 class="font-poppins text-base font-semibold text-slate-800 mb-1">Email</h3>
                                <a href="mailto:info@ekcgenius.com" class="font-inter text-sm text-trust-600 hover:text-trust-700">info@ekcgenius.com</a>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8">
                        <a href="tel:+60123456789" class="inline-flex items-center gap-2 bg-trust-600 hover:bg-trust-700 text-white font-inter font-medium px-6 py-3 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            Call Now
                        </a>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 shadow-card overflow-hidden">
                    <div class="h-64 lg:h-full min-h-[300px]">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d63750.2032177098!2d101.5444289486328!3d2.989751!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31cdb4794c2a79ff%3A0x2ff788d0a1a8f214!2sPower%20Genius%20Sdn%20Bhd%20(ezAuto%20Inspection%20Centre)!5e0!3m2!1sen!2smy!4v1775898592579!5m2!1sen!2smy" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <style>
        /* Select list sizing */
        select[size] {
            height: auto;
            min-height: 80px;
        }
        select[size] option {
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
        }
        select[size] option:hover,
        select[size] option:checked {
            background-color: #EEF2FF;
            color: #4338CA;
        }
        select[size] option[data-hidden="true"] {
            display: none;
        }
    </style>
    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu    = document.getElementById('mobileMenu');
        const menuIcon      = document.getElementById('menuIcon');

        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            menuIcon.setAttribute('d', mobileMenu.classList.contains('hidden')
                ? 'M4 6h16M4 12h16M4 18h16'
                : 'M6 18L18 6M6 6l12 12');
        });
        document.querySelectorAll('.mobile-menu-link').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.add('hidden');
                menuIcon.setAttribute('d', 'M4 6h16M4 12h16M4 18h16');
            });
        });

        // ── Live search filter for native <select> ────────────────────────────
        function filterSelect(selectId, query) {
            const q = query.trim().toLowerCase();
            const sel = document.getElementById(selectId);
            let first = null;
            Array.from(sel.options).forEach(opt => {
                const match = opt.text.toLowerCase().includes(q);
                opt.setAttribute('data-hidden', match ? 'false' : 'true');
                // We hide via style since data-hidden CSS trick needs stylesheet support
                opt.style.display = match ? '' : 'none';
                if (match && !first) first = opt;
            });
            // select first visible option automatically
            if (first) { sel.value = first.value; sel.dispatchEvent(new Event('change')); }
        }

        function onTeacherSelect(sel) {
            document.getElementById('submitBtn').disabled = !sel.value;
        }

        function onStudentSelect(sel) {
            document.getElementById('submitBtn').disabled = !sel.value;
            const hint     = document.getElementById('studentParentHint');
            const nameSpan = document.getElementById('studentParentName');
            const parent   = sel.options[sel.selectedIndex]?.dataset.parent || '';
            if (parent) {
                nameSpan.textContent = parent;
                hint.classList.remove('hidden');
            } else {
                hint.classList.add('hidden');
            }
        }

        function validateParentIC() {
            const icInput = document.getElementById('studentIcInput');
            const ic = icInput.value.replace(/[^0-9]/g, '');
            icInput.value = ic;
            return ic.length >= 12;
        }



        // ── Role switcher ─────────────────────────────────────────────────────
        document.getElementById('roleSelect').addEventListener('change', function () {
            const role          = this.value;
            const adminFields   = document.getElementById('adminFields');
            const teacherFields = document.getElementById('teacherFields');
            const parentFields  = document.getElementById('parentFields');
            const submitBtn     = document.getElementById('submitBtn');

            adminFields.classList.add('hidden');
            teacherFields.classList.add('hidden');
            parentFields.classList.add('hidden');
            submitBtn.disabled = true;

            if (role === 'admin') {
                adminFields.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Login as Administrator';
            } else if (role === 'teacher') {
                teacherFields.classList.remove('hidden');
                // auto-select first option
                const sel = document.getElementById('teacherSelect');
                if (sel.options.length > 0) { sel.selectedIndex = 0; }
                submitBtn.disabled = false;
                submitBtn.textContent = 'Login as Teacher';
            } else if (role === 'parent') {
                parentFields.classList.remove('hidden');
                const icInput = document.getElementById('studentIcInput');
                if (icInput) {
                    icInput.value = '';
                }
                document.getElementById('submitBtn').disabled = true;
                submitBtn.textContent = 'Login as Parent';
            }
        });

        // ── Form submit ───────────────────────────────────────────────────────
        document.getElementById('loginForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData  = new FormData(this);
            const submitBtn = document.getElementById('submitBtn');
            const message   = document.getElementById('message');

            submitBtn.disabled    = true;
            submitBtn.textContent = 'Logging in...';
            message.classList.add('hidden');

            try {
                const response = await fetch('backend/login.php', { method: 'POST', body: formData });
                const result   = await response.json();

                if (result.success) {
                    window.location.href = result.redirect;
                } else {
                    message.textContent = result.message;
                    message.classList.remove('hidden');
                    submitBtn.disabled    = false;
                    const role = document.getElementById('roleSelect').value;
                    submitBtn.textContent = role === 'admin' ? 'Login as Administrator'
                                         : role === 'teacher' ? 'Login as Teacher' : 'Login as Parent';
                }
            } catch (err) {
                message.textContent = 'An error occurred. Please try again.';
                message.classList.remove('hidden');
                submitBtn.disabled = false;
            }
        });
    </script>

<?php include 'components/footer.php'; ?>