/* Practical 6: data access module. Fetches JSON and reports readable errors. */
(function () {
    "use strict";

    async function fetchJson(path) {
        const response = await fetch(path, { headers: { "Accept": "application/json" } });
        if (!response.ok) {
            throw new Error("Request failed (" + response.status + ") while loading " + path + ".");
        }
        return response.json();
    }

    window.StudentHubData = {
        events: function () { return fetchJson("data/events.json"); },
        students: function () { return fetchJson("data/students.json"); },
        faqs: function () { return fetchJson("data/faqs.json"); }
    };
}());
