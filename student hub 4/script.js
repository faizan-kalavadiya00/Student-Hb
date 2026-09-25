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
