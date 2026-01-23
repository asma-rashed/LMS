document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("updateForm");

    form.addEventListener("submit", function (e) {
        // Clear old errors
        const errorFields = document.querySelectorAll("[id^='error-']");
        errorFields.forEach(f => f.textContent = "");

        let hasError = false;

        const name = document.getElementById("name").value.trim();
        const username = document.getElementById("username").value.trim();
        const email = document.getElementById("email").value.trim();
        const phone = document.getElementById("phone").value.trim();
        const majorField = document.getElementById("major");

        // Name validation
        if (name === "") {
            document.getElementById("error-name").textContent = "Name is required.";
            hasError = true;
        } else if (name.length < 4) {
            document.getElementById("error-name").textContent = "name must be at least 4 characters.";
            hasError = true;
        }

        // Username validation
        if (username === "") {
            document.getElementById("error-username").textContent = "Username is required.";
            hasError = true;
        } else if (username.length < 4) {
            document.getElementById("error-username").textContent = "Username must be at least 4 characters.";
            hasError = true;
        }

        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email === "") {
            document.getElementById("error-email").textContent = "Email is required.";
            hasError = true;
        } else if (!emailRegex.test(email)) {
            document.getElementById("error-email").textContent = "Enter a valid email address.";
            hasError = true;
        }

        // Phone validation (only digits, 9–15 length)
        const phoneRegex = /^[0-9]{10,15}$/;

        if (phone === "") {
            document.getElementById("error-phone").textContent = "Phone number is required.";
            hasError = true;
        } else if (!phoneRegex.test(phone)) {
            document.getElementById("error-phone").textContent = "Phone must be digits only (10–15 numbers).";
            hasError = true;
        }

        // Major validation (if exists)
        if (majorField && majorField.value.trim() === "") {
            document.getElementById("error-major").textContent = "Major is required.";
            hasError = true;
        }

        // Stop form submission if errors
        if (hasError) e.preventDefault();
    });
});
// ----------- REGEX PATTERNS ----------- //
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const phoneRegex = /^\d{10,15}$/; // FIXED: digits only
const usernameRegex = /^[A-Za-z0-9_]{3,20}$/;

// ----------- VALIDATION FUNCTION ----------- //
function validateForm(prefix) {
    let hasError = false;

    // Get form fields
    const name = document.getElementById(`${prefix}-name`);
    const username = document.getElementById(`${prefix}-username`);
    const email = document.getElementById(`${prefix}-email`);
    const phone = document.getElementById(`${prefix}-phone`);
    const password = document.getElementById(`${prefix}-password`);
    const major = document.getElementById(`${prefix}-major`); // only exists in instructor form

    // --- Name ---
    if (name.value.trim() === "") {
        document.getElementById(`${prefix}-error-name`).textContent = "Name is required.";
        hasError = true;
    } else {
        document.getElementById(`${prefix}-error-name`).textContent = "";
    }

    // --- Username ---
    if (username.value.trim() === "") {
        document.getElementById(`${prefix}-error-username`).textContent = "Username is required.";
        hasError = true;
    } else if (!usernameRegex.test(username.value.trim())) {
        document.getElementById(`${prefix}-error-username`).textContent =
            "Username must be 3–20 characters (letters, numbers, underscore).";
        hasError = true;
    } else {
        document.getElementById(`${prefix}-error-username`).textContent = "";
    }

    // --- Email ---
    if (email.value.trim() === "") {
        document.getElementById(`${prefix}-error-email`).textContent = "Email is required.";
        hasError = true;
    } else if (!emailRegex.test(email.value.trim())) {
        document.getElementById(`${prefix}-error-email`).textContent = "Invalid email format.";
        hasError = true;
    } else {
        document.getElementById(`${prefix}-error-email`).textContent = "";
    }

    // --- Phone ---
    if (phone.value.trim() === "") {
        document.getElementById(`${prefix}-error-phone`).textContent = "Phone number is required.";
        hasError = true;
    } else if (!phoneRegex.test(phone.value.trim())) {
        document.getElementById(`${prefix}-error-phone`).textContent =
            "Phone must be 10–15 digits only.";
        hasError = true;
    } else {
        document.getElementById(`${prefix}-error-phone`).textContent = "";
    }

    // --- Password ---
    if (password.value.trim() === "") {
        document.getElementById(`${prefix}-error-password`).textContent = "Password is required.";
        hasError = true;
    } else if (password.value.length < 6) {
        document.getElementById(`${prefix}-error-password`).textContent =
            "Password must be at least 6 characters.";
        hasError = true;
    } else {
        document.getElementById(`${prefix}-error-password`).textContent = "";
    }

    // --- Major (Instructor Only) ---
    if (major) {
        if (major.value.trim() === "") {
            document.getElementById(`${prefix}-error-major`).textContent = "Major is required.";
            hasError = true;
        } else {
            document.getElementById(`${prefix}-error-major`).textContent = "";
        }
    }

    return !hasError;
}

// ----------- ATTACH VALIDATION TO BOTH FORMS ----------- //
document.addEventListener("DOMContentLoaded", () => {
    // Student form
    const studentForm = document.querySelector("#studentTab form");
    studentForm.addEventListener("submit", function (e) {
        if (!validateForm("s")) {
            e.preventDefault();
        }
    });

    // Instructor form
    const instructorForm = document.querySelector("#teacherTab form");
    instructorForm.addEventListener("submit", function (e) {
        if (!validateForm("t")) {
            e.preventDefault();
        }
    });
});
