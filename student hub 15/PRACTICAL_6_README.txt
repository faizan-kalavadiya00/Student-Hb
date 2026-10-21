STUDENT HUB — PRACTICAL 6
Rendering External JSON Data using Fetch API, Search and Filter
================================================================

WHAT IS INCLUDED
- Existing Student Hub pages and Practical 5 features retained.
- data/events.json: 15 sample event records.
- data/students.json: 15 sample student records.
- data/faqs.json: 15 sample FAQ records.
- js/pr6-data.js: reusable Fetch API/data-access module.
- js/pr6-ui.js: reusable rendering, search, filter, sort, pagination, loading and error handling.
- events.html: events fetched from JSON, searchable/filterable/sortable and paginated.
- students.html: new student profile listing with the same controls.
- faq.html: FAQs fetched from JSON, searchable/filterable/sortable, paginated and collapsible.
- Navigation links updated to include Student Profiles.
- Responsive styles for Practical 6 components.

HOW TO RUN (IMPORTANT)
Fetch API usually cannot load local JSON files when you open an HTML file directly with file://.
1. Extract the ZIP.
2. Open the extracted "student hub 4" folder in Visual Studio Code.
3. Start the folder using the Live Server extension (right-click home.html -> Open with Live Server).
4. Use the navigation to open Events, FAQs, or Student Profiles.
5. Test search, category/department filters, sort order, pagination and loading/error behavior.
   To test the error state, temporarily rename one JSON file, refresh its page, then restore the filename.

PRACTICAL 6 CHECKLIST
[✓] JSON datasets (15 records each)
[✓] Fetch API and JSON parsing
[✓] Dynamic rendering using DOM methods
[✓] Search, filter and sorting
[✓] Pagination with Previous/Next
[✓] Loading, empty-results and error/retry states
[✓] Modular JavaScript files
[✓] Responsive UI and accessible status messages
[✓] Sample student profiles page
[✓] FAQ collapsible answers

NOTES
- Student profiles and events are sample/demo records for the practical.
- The existing registration form remains a client-side demo; it does not submit data to a server.
