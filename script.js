document.addEventListener("DOMContentLoaded", () => {

    // Prevent double submit
    document.querySelectorAll("form").forEach(form => {
        form.addEventListener("submit", () => {
            const btn = form.querySelector("button[type='submit']");
            if (btn) {
                btn.disabled = true;
                btn.innerText = "Processing...";
            }
        });
    });

    // Password feedback
    document.querySelectorAll("input[type='password']").forEach(input => {
        const counter = document.createElement("small");
        counter.className = "password-counter";
        input.after(counter);

        input.addEventListener("input", () => {
            counter.textContent = `Length: ${input.value.length}`;
            if (input.value.length < 8) {
                input.classList.add("invalid");
                input.classList.remove("valid");
            } else {
                input.classList.add("valid");
                input.classList.remove("invalid");
            }
        });
    });

    // File input display & prevent empty
    const fileInput = document.querySelector("input[type='file']");
    const uploadForm = document.querySelector("form[enctype='multipart/form-data']");
    if (fileInput) {
        const info = document.createElement("div");
        info.className = "file-info";
        fileInput.after(info);

        fileInput.addEventListener("change", () => {
            info.textContent = fileInput.files.length ? fileInput.files[0].name : "";
        });
    }
    if (uploadForm && fileInput) {
        uploadForm.addEventListener("submit", e => {
            if (fileInput.files.length === 0) e.preventDefault();
        });
    }

    // Upload message
    if (uploadForm) {
        const msg = document.createElement("div");
        msg.className = "js-message";
        uploadForm.prepend(msg);

        uploadForm.addEventListener("submit", () => {
            msg.textContent = "Uploading securely... please wait.";
            msg.style.display = "block";
        });
    }

    // Button hover hint
    document.querySelectorAll("button").forEach(btn => {
        btn.addEventListener("mouseenter", () => {
            btn.dataset.original = btn.innerText;
            btn.innerText = "Ready to submit";
        });
        btn.addEventListener("mouseleave", () => {
            btn.innerText = btn.dataset.original;
        });
    });

});
