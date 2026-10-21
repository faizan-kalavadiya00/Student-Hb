STUDENT HUB — PRACTICAL 5
Registration Form with Frontend Validation and User-Friendly Error Handling
==========================================================================

HOW TO RUN
1. Extract the ZIP file.
2. Open the "student hub 4" folder.
3. Open home.html to browse Student Hub, or registration.html to test Practical 5.
4. Use a modern browser (Chrome, Edge, Firefox). No server or installation is required.

PRACTICAL 5 IMPLEMENTED
- HTML5 input types, labels, autocomplete and required-field indicators.
- JavaScript client-side validation using correctly escaped regular expressions.
- Mobile validation accepts a 10-digit Indian number with optional +91 and common spaces, hyphens or parentheses.
- Validations for full name, email, Indian mobile number, course, year, password,
  confirm password, gender selection and terms acceptance.
- Field-level error messages displayed beside the relevant controls.
- Password strength meter with live feedback.
- Real-time validation on input/change and validation on form submission.
- Keyboard-accessible controls, visible focus styles, associated labels,
  aria-describedby, aria-invalid and live status/error regions.
- Responsive layout that fits desktop and mobile screens.
- Keeps the existing Student Hub pages, assets, navigation, theme toggle,
  hamburger menu, FAQ interaction, events modal and home-page slider.

IMPORTANT
This is a front-end demonstration. A successful submission displays a confirmation
message but does not send or store personal data. A real registration system needs
a secure backend and server-side validation as well.

SUGGESTED TEST CASES
1. Submit the empty form: required-field errors should appear.
2. Name: "Faizan Kalavadiya" — valid; "F" or "Faizan123" — invalid.
3. Email: "faizan@example.com" — valid; "faizan@" — invalid.
4. Mobile: "9876543210" or "+91 98765 43210" — valid; "12345" — invalid.
5. Leave Course or Year at the default option — error should appear.
6. Password: "Hello@123" — meets the strength rules; "hello" — invalid.
7. Enter different values in Password and Confirm Password — mismatch error.
8. Do not select a gender — error should appear.
9. Leave terms unchecked — acceptance error should appear.
10. Complete every field correctly and accept terms — success message appears.
