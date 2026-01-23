document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("addCourseForm");

    form.addEventListener("submit", function (e) {
        // Clear old errors
        document.querySelectorAll("[id^='error-']").forEach(err => err.textContent = "");

        let hasError = false;

        // Get input values
        const course_name = document.getElementById("course_name").value.trim();
        const course_code = document.getElementById("course_code").value.trim();
        const credit_hours = document.getElementById("credit_hours").value.trim();
        const description = document.getElementById("description").value.trim();
        const content = document.getElementById("content").value.trim();

        // --- Validations ---

        // Course name: required, min 3 chars
        if (course_name === "") {
            setError("course_name", "Course title is required.");
        } else if (course_name.length < 3) {
            setError("course_name", "Course title must be at least 3 characters.");
        }

        // Course code: required, letters+numbers only (e.g., CS101)
        const codeRegex = /^[A-Za-z]{2,4}\d{2,4}$/;
        if (course_code === "") {
            setError("course_code", "Course code is required.");
        } else if (!codeRegex.test(course_code)) {
            setError("course_code", "Invalid format (e.g., CS101 or ENG202).");
        }

        // Credit hours: must be a number between 1–10
        const credit = Number(credit_hours);
        if (credit_hours === "") {
            setError("credit_hours", "Course hours are required.");
        } else if (isNaN(credit) || credit < 1 || credit > 10) {
            setError("credit_hours", "Credit hours must be a number between 1 and 10.");
        }

        // Description: required, min 10 chars
        if (description === "") {
            setError("description", "Course description is required.");
        } else if (description.length < 10) {
            setError("description", "Description must be at least 10 characters.");
        }

        // Content: required, min 20 chars
        if (content === "") {
            setError("content", "Course content is required.");
        } else if (content.length < 20) {
            setError("content", "Content must be at least 20 characters.");
        }

        // If any error messages exist, prevent form submission
        if (document.querySelectorAll(".text-danger.small:not(:empty)").length > 0) {
            e.preventDefault();
        }

        // Helper function
        function setError(field, message) {
            document.getElementById("error-" + field).textContent = message;
            hasError = true;
        }
    });
});
