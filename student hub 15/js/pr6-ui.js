/* Practical 6: reusable rendering, search, filter, sorting and pagination. */
document.addEventListener("DOMContentLoaded", function () {
    "use strict";
    const page = document.querySelector("[data-pr6-type]");
    if (!page || !window.StudentHubData) return;

    const type = page.dataset.pr6Type;
    const config = {
        events: { load: window.StudentHubData.events, filter: "category", search: ["title", "venue", "description", "category"], sortFields: ["date", "title"], pageSize: 6 },
        students: { load: window.StudentHubData.students, filter: "department", search: ["name", "studentId", "department", "interests"], sortFields: ["name", "studentId"], pageSize: 6 },
        faqs: { load: window.StudentHubData.faqs, filter: "category", search: ["question", "answer", "category"], sortFields: ["question", "id"], pageSize: 5 }
    }[type];
    if (!config) return;

    const searchInput = document.getElementById("pr6-search");
    const filterSelect = document.getElementById("pr6-filter");
    const sortSelect = document.getElementById("pr6-sort");
    const status = document.getElementById("pr6-status");
    const results = document.getElementById("pr6-results");
    const previous = document.getElementById("pr6-prev");
    const next = document.getElementById("pr6-next");
    const pageLabel = document.getElementById("pr6-page-label");
    let records = [];
    let currentPage = 1;

    function element(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function makeCard(record) {
        if (type === "faqs") {
            const item = element("article", "pr6-faq-item");
            const details = element("details", "pr6-faq-details");
            const question = element("summary", "pr6-question", record.question);
            const answer = element("p", "pr6-answer", record.answer);
            const category = element("span", "pr6-tag", record.category);
            details.append(question, answer);
            item.append(details, category);
            return item;
        }
        const card = element("article", "pr6-card");
        if (type === "events") {
            card.append(element("span", "pr6-tag", record.category));
            card.append(element("h2", "", record.title));
            card.append(element("p", "pr6-card-date", new Date(record.date + "T12:00:00").toLocaleDateString(undefined, { year: "numeric", month: "long", day: "numeric" })));
            card.append(element("p", "", record.description));
            card.append(element("p", "pr6-meta", "Venue: " + record.venue));
        } else {
            card.append(element("span", "pr6-tag", record.department));
            card.append(element("h2", "", record.name));
            card.append(element("p", "pr6-meta", "Student ID: " + record.studentId));
            card.append(element("p", "pr6-meta", "Year: " + record.year));
            card.append(element("p", "", "Interests: " + record.interests));
        }
        return card;
    }

    function getVisibleRecords() {
        const term = searchInput.value.trim().toLocaleLowerCase();
        const selected = filterSelect.value;
        let visible = records.filter(function (record) {
            const matchesFilter = !selected || String(record[config.filter]) === selected;
            const searchable = config.search.map(key => String(record[key] ?? "")).join(" ").toLocaleLowerCase();
            return matchesFilter && (!term || searchable.includes(term));
        });
        const parts = sortSelect.value.split("-");
        const field = parts.slice(0, -1).join("-");
        const direction = parts[parts.length - 1] === "desc" ? -1 : 1;
        visible.sort(function (a, b) {
            let left = a[field], right = b[field];
            if (field === "date") {
                left = new Date(left).getTime();
                right = new Date(right).getTime();
            } else if (typeof left === "string") {
                left = left.toLocaleLowerCase();
                right = right.toLocaleLowerCase();
            }
            if (left < right) return -1 * direction;
            if (left > right) return 1 * direction;
            return 0;
        });
        return visible;
    }

    function render() {
        const visible = getVisibleRecords();
        const totalPages = Math.ceil(visible.length / config.pageSize);
        currentPage = Math.min(Math.max(currentPage, 1), Math.max(totalPages, 1));
        results.replaceChildren();
        if (!visible.length) {
            status.textContent = "No matching records found. Try changing your search or filter.";
            status.className = "pr6-status";
        } else {
            status.textContent = visible.length + " record(s) found.";
            status.className = "pr6-status success";
            visible.slice((currentPage - 1) * config.pageSize, currentPage * config.pageSize)
                .forEach(record => results.appendChild(makeCard(record)));
        }
        pageLabel.textContent = "Page " + (visible.length ? currentPage : 0) + " of " + totalPages;
        previous.disabled = currentPage <= 1 || totalPages === 0;
        next.disabled = currentPage >= totalPages || totalPages === 0;
    }

    function fillFilters() {
        const values = Array.from(new Set(records.map(record => record[config.filter])))
            .filter(Boolean).sort((a, b) => String(a).localeCompare(String(b)));
        values.forEach(value => filterSelect.appendChild(element("option", "", value)));
    }

    function showError(error) {
        status.className = "pr6-status error";
        status.textContent = "Could not load data. " + error.message + " Start a local web server (for example, VS Code Live Server), then retry.";
        results.replaceChildren();
        const retry = element("button", "pr6-retry", "Retry loading data");
        retry.type = "button";
        retry.addEventListener("click", loadData);
        results.appendChild(retry);
        pageLabel.textContent = "Page 0 of 0";
        previous.disabled = true;
        next.disabled = true;
    }

    async function loadData() {
        status.className = "pr6-status";
        status.textContent = "Loading data…";
        results.replaceChildren();
        try {
            const data = await config.load();
            if (!Array.isArray(data)) throw new Error("The JSON file must contain an array of records.");
            records = data;
            fillFilters();
            currentPage = 1;
            render();
        } catch (error) {
            showError(error instanceof Error ? error : new Error("An unknown error occurred."));
        }
    }

    [searchInput, filterSelect, sortSelect].forEach(control => control.addEventListener("input", function () {
        currentPage = 1;
        if (records.length) render();
    }));
    previous.addEventListener("click", function () { if (currentPage > 1) { currentPage--; render(); } });
    next.addEventListener("click", function () { currentPage++; render(); });
    loadData();
});
