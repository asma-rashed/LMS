document.addEventListener("DOMContentLoaded", function() {
    
    function validateInput(usernameInput, passwordInput, usernameErrorDiv, passwordErrorDiv) {
        let valid = true;

        // Clear previous errors
        usernameErrorDiv.textContent = "";
        passwordErrorDiv.textContent = "";

        // Username validation: 4-14 chars, letters, numbers, only - _ .
        const usernameRegex = /^[A-Za-z0-9._-]{4,14}$/;
        if (!usernameRegex.test(usernameInput.value)) {
            usernameErrorDiv.textContent = "Username must be 4-14 characters and can only include letters, numbers, -, _, .";
            valid = false;
        }

        // Password validation: 8-12 chars, letters, numbers, special chars !@#$%^&*()-_+=<>
        const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[!@#$%^&*()\-_=+<>]).{8,12}$/;
        if (!passwordRegex.test(passwordInput.value)) {
            passwordErrorDiv.textContent = "Password must be 8-12 characters, include letters, numbers, and at least one special character (!@#$%^&*()-_+=<>)";
            valid = false;
        }

        return valid;
    }

    // --- Student form handler ---
    const studentForm = document.querySelector("#student form");
    studentForm.addEventListener("submit", function(e) {
        e.preventDefault();
        const usernameInput = document.getElementById("studentName");
        const passwordInput = document.getElementById("studentPass");
        const usernameErrorDiv = document.getElementById("studentNameError");
        const passwordErrorDiv = document.getElementById("studentPassError");

        if (validateInput(usernameInput, passwordInput, usernameErrorDiv, passwordErrorDiv)) {
            
            // FIX: Manually add the 'login' POST parameter needed by PHP
            const loginInput = document.createElement('input');
            loginInput.type = 'hidden';
            loginInput.name = 'login';
            loginInput.value = '1'; 
            studentForm.appendChild(loginInput);

            // Now submit the form programmatically
            studentForm.submit();
        }
    });

    // --- Teacher form handler ---
    const teacherForm = document.querySelector("#teacher form");
    teacherForm.addEventListener("submit", function(e) {
        e.preventDefault();
        const usernameInput = document.getElementById("teacherName");
        const passwordInput = document.getElementById("teacherPass");
        const usernameErrorDiv = document.getElementById("teacherNameError");
        const passwordErrorDiv = document.getElementById("teacherPassError");

        if (validateInput(usernameInput, passwordInput, usernameErrorDiv, passwordErrorDiv)) {
            
            // FIX: Manually add the 'login' POST parameter needed by PHP
            const loginInput = document.createElement('input');
            loginInput.type = 'hidden';
            loginInput.name = 'login';
            loginInput.value = '1'; 
            teacherForm.appendChild(loginInput);
            
            // Now submit the form programmatically
            teacherForm.submit();
        }
    });
});