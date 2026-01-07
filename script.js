const uploadForm = document.getElementById('anonymousUploadForm');

uploadForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fileInput = uploadForm.querySelector('input[type="file"]');
    const passInput = uploadForm.querySelector('input[type="password"]');
    const status = document.getElementById('uploadStatus');

    if (!fileInput.files.length) {
        alert("Please select a file first.");
        return;
    }
    if (!passInput.value || passInput.value.length < 10) {
        alert("Passphrase too short (min 10 chars).");
        return;
    }

    status.textContent = "Encrypting...";
    try {
        // Assuming you have encryptFileEnvelope() defined elsewhere
        const encrypted = await encryptFileEnvelope(fileInput.files[0], passInput.value);

        const fd = new FormData();
        fd.append("file", new Blob([encrypted.ciphertext]), fileInput.files[0].name);
        fd.append("passphrase", passInput.value);
        fd.append("iv", btoa(String.fromCharCode(...encrypted.iv)));
        fd.append("salt", btoa(String.fromCharCode(...encrypted.salt)));
        fd.append("wrapped_key", btoa(String.fromCharCode(...encrypted.wrappedKey)));
        fd.append("csrf", uploadForm.querySelector('input[name="csrf"]').value);

        status.textContent = "Uploading...";
        const res = await fetch(uploadForm.action, { method: "POST", body: fd });
        const html = await res.text();
        document.open();
        document.write(html);
        document.close();
    } catch (err) {
        console.error(err);
        status.textContent = "Upload failed: " + err.message;
    }
});
