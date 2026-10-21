document.addEventListener("DOMContentLoaded", function () {
    const body = document.body;

    /* Dark mode */
    const themeBtn = document.createElement("button");
    themeBtn.className = "theme-toggle";
    themeBtn.type = "button";
    themeBtn.textContent = "Dark Mode";
    body.appendChild(themeBtn);

    try {
        if (localStorage.getItem("studentHubTheme") === "dark") {
            body.classList.add("dark-mode");
            themeBtn.textContent = "Light Mode";
        }
    } catch (e) {}

    themeBtn.addEventListener("click", function () {
        body.classList.toggle("dark-mode");
        const dark = body.classList.contains("dark-mode");
        themeBtn.textContent = dark ? "Light Mode" : "Dark Mode";
        try { localStorage.setItem("studentHubTheme", dark ? "dark" : "light"); } catch (e) {}
    });

    /* Right-side hamburger menu */
    const nav = document.querySelector(".external-style-5, .external-style-25");
    const header = document.querySelector(".external-style-2, .external-style-22");

    if (nav && header) {
        nav.classList.add("student-nav");

        const menuBtn = document.createElement("button");
        menuBtn.className = "menu-toggle";
        menuBtn.type = "button";
        menuBtn.textContent = "☰";
        menuBtn.setAttribute("aria-label", "Open menu");
        menuBtn.setAttribute("aria-expanded", "false");
        header.appendChild(menuBtn);

        const overlay = document.createElement("div");
        overlay.className = "menu-overlay";
        body.appendChild(overlay);

        function closeMenu() {
            nav.classList.remove("nav-open");
            overlay.classList.remove("show");
            menuBtn.setAttribute("aria-expanded", "false");
        }

        menuBtn.addEventListener("click", function (event) {
            event.stopPropagation();
            const open = !nav.classList.contains("nav-open");
            nav.classList.toggle("nav-open", open);
            overlay.classList.toggle("show", open);
            menuBtn.setAttribute("aria-expanded", String(open));
        });

        nav.addEventListener("click", function (event) {
            const link = event.target.closest("a");
            if (!link) return;
            // Let the browser perform normal relative-file navigation.
            closeMenu();
        });

        overlay.addEventListener("click", closeMenu);
    }

    /* Notice below navbar */
    const banner = document.createElement("div");
    banner.className = "notice-banner";
    banner.innerHTML = "<span><b>Student Hub Notice:</b> Explore the Events and FAQ pages.</span>" +
        '<button type="button" aria-label="Close notification">×</button>';
    if (header) header.insertAdjacentElement("afterend", banner);
    else body.insertBefore(banner, body.firstElementChild);
    banner.querySelector("button").addEventListener("click", function () { banner.remove(); });

    /* Collapsible FAQ */
    document.querySelectorAll(".external-style-31").forEach(function (question) {
        const answer = question.nextElementSibling;
        if (!answer || answer.tagName !== "P") return;
        question.setAttribute("tabindex", "0");
        question.setAttribute("role", "button");
        question.setAttribute("aria-expanded", "false");
        answer.hidden = true;
        function toggleAnswer() {
            answer.hidden = !answer.hidden;
            question.setAttribute("aria-expanded", String(!answer.hidden));
        }
        question.addEventListener("click", toggleAnswer);
        question.addEventListener("keydown", function (event) {
            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                toggleAnswer();
            }
        });
    });

    /* Modal popup for event details */
    const eventCards = document.querySelectorAll(".event-card");
    if (eventCards.length > 0) {
        const modal = document.createElement("div");
        modal.className = "student-modal";
        modal.innerHTML = '<div class="modal-box" role="dialog" aria-modal="true">' +
            '<button class="modal-close" type="button" aria-label="Close">×</button>' +
            '<div class="modal-content"></div></div>';
        body.appendChild(modal);
        const content = modal.querySelector(".modal-content");
        function closeModal() { modal.classList.remove("show"); }
        modal.querySelector(".modal-close").addEventListener("click", closeModal);
        modal.addEventListener("click", function (event) { if (event.target === modal) closeModal(); });
        document.addEventListener("keydown", function (event) { if (event.key === "Escape") closeModal(); });
        eventCards.forEach(function (card) {
            const button = document.createElement("button");
            button.className = "details-btn";
            button.type = "button";
            button.textContent = "View Details";
            button.addEventListener("click", function () {
                content.innerHTML = card.innerHTML;
                const oldButton = content.querySelector(".details-btn");
                if (oldButton) oldButton.remove();
                modal.classList.add("show");
            });
            card.appendChild(button);
        });
    }

    /* Practical 5: client-side registration validation */
    const registrationForm = document.querySelector("#student-registration");
    if (registrationForm) {
        const fieldRules = {
            name: {
                test: value => /^[A-Za-z][A-Za-z .'-]{1,59}$/.test(value.trim()),
                message: "Enter a valid name (2–60 letters; spaces, apostrophes and hyphens are allowed)."
            },
            email: {
                test: value => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value.trim()),
                message: "Enter a valid email address, such as name@example.com."
            },
            mobile: {
                test: value => { const normalized = value.trim().replace(/[\s()-]/g, "").replace(/^\+91/, ""); return /^[6-9]\d{9}$/.test(normalized); },
                message: "Enter a valid 10-digit Indian mobile number (optionally with +91)."
            },
            course: { test: value => value !== "", message: "Please select your course." },
            year: { test: value => ["1", "2", "3", "4"].includes(value), message: "Please select your year of study." },
            password: {
                test: value => /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9\s])\S{8,}$/.test(value),
                message: "Use at least 8 characters with uppercase, lowercase, a number and a symbol; no spaces."
            },
            "confirm-password": {
                test: value => value !== "" && value === document.querySelector("#password").value,
                message: "Passwords do not match."
            }
        };

        function showError(id, message) {
            const input = document.getElementById(id);
            const error = document.getElementById(id + "-error");
            if (error) error.textContent = message || "";
            if (input) {
                input.classList.toggle("invalid", Boolean(message));
                input.classList.toggle("valid", !message && input.value.trim() !== "");
                input.setAttribute("aria-invalid", String(Boolean(message)));
            }
        }

        function validateField(id) {
            const input = document.getElementById(id);
            const rule = fieldRules[id];
            const value = input.value;
            const message = !value.trim() ? "This field is required." :
                (rule && !rule.test(value) ? rule.message : "");
            showError(id, message);
            return !message;
        }

        Object.keys(fieldRules).forEach(function (id) {
            const input = document.getElementById(id);
            input.addEventListener("blur", function () { validateField(id); });
            input.addEventListener("input", function () {
                if (input.classList.contains("invalid") || id === "password" || id === "confirm-password") {
                    validateField(id);
                }
                if (id === "password") {
                    const value = input.value;
                    let score = 0;
                    if (value.length >= 8) score++;
                    if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
                    if (/\d/.test(value)) score++;
                    if (/[^A-Za-z0-9\s]/.test(value)) score++;
                    const fill = document.querySelector("#strength-fill");
                    const label = document.querySelector("#password-help");
                    const labels = ["No password entered", "Weak password", "Fair password", "Good password", "Strong password"];
                    fill.style.width = (score * 25) + "%";
                    fill.style.backgroundColor = score < 2 ? "#b42318" : score < 4 ? "#c78312" : "#218739";
                    label.textContent = value ? labels[score] : "Use 8+ characters with uppercase, lowercase, a number and a symbol.";
                    if (document.querySelector("#confirm-password").value) validateField("confirm-password");
                }
            });
            input.addEventListener("change", function () { validateField(id); });
        });

        document.querySelectorAll('input[name="gender"]').forEach(function (radio) {
            radio.addEventListener("change", function () {
                document.querySelector("#gender-error").textContent = "";
                registrationForm.querySelector(".gender-field").setAttribute("aria-invalid", "false");
            });
        });
        document.querySelector("#terms").addEventListener("change", function () {
            document.querySelector("#terms-error").textContent = this.checked ? "" : "You must accept the terms to continue.";
        });

        registrationForm.addEventListener("submit", function (event) {
            event.preventDefault();
            let valid = true;
            Object.keys(fieldRules).forEach(function (id) {
                if (!validateField(id)) valid = false;
            });

            const genderSelected = Boolean(registrationForm.querySelector('input[name="gender"]:checked'));
            document.querySelector("#gender-error").textContent = genderSelected ? "" : "Please select a gender option.";
            registrationForm.querySelector(".gender-field").setAttribute("aria-invalid", String(!genderSelected));
            if (!genderSelected) valid = false;

            const termsAccepted = document.querySelector("#terms").checked;
            document.querySelector("#terms-error").textContent = termsAccepted ? "" : "You must accept the terms to continue.";
            document.querySelector("#terms").setAttribute("aria-invalid", String(!termsAccepted));
            if (!termsAccepted) valid = false;

            const result = document.querySelector("#registration-result");
            if (!valid) {
                result.textContent = "Please correct the highlighted fields and try again.";
                result.className = "registration-result failure";
                const firstInvalid = registrationForm.querySelector(".invalid") ||
                    (!genderSelected ? registrationForm.querySelector('input[name="gender"]') : null) ||
                    (!termsAccepted ? document.querySelector("#terms") : null);
                if (firstInvalid) firstInvalid.focus();
                return;
            }

            result.textContent = "Registration form validated successfully! (Demo only — no data has been sent or saved.)";
            result.className = "registration-result success";
        });
    }

    /* Simple content slider */
    const homeSection = document.querySelector(".external-style-42");
    if (homeSection) {
        const slider = document.createElement("section");
        slider.className = "content-slider";
        slider.innerHTML = '<h2>Student Hub Highlights</h2>' +
            '<div class="slide"><h3>Academic Resources</h3><p>Access notes, previous year papers and useful learning resources.</p></div>' +
            '<div class="slide"><h3>Academic Services</h3><p>Find fees, hall ticket, scholarship and medical leave information.</p></div>' +
            '<div class="slide"><h3>Student Events</h3><p>Stay updated with workshops, seminars, hackathons and campus activities.</p></div>' +
            '<div class="slider-controls"><button type="button" class="prev-slide">Previous</button>' +
            '<span class="slide-number">1 / 3</span><button type="button" class="next-slide">Next</button></div>';
        homeSection.after(slider);
        const slides = slider.querySelectorAll(".slide");
        const number = slider.querySelector(".slide-number");
        let current = 0;
        function showSlide(index) {
            current = (index + slides.length) % slides.length;
            slides.forEach(function (slide, i) { slide.hidden = i !== current; });
            number.textContent = (current + 1) + " / " + slides.length;
        }
        slider.querySelector(".prev-slide").addEventListener("click", function () { showSlide(current - 1); });
        slider.querySelector(".next-slide").addEventListener("click", function () { showSlide(current + 1); });
        showSlide(0);
    }
});
