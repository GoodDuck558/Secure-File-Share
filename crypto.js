// crypto.js — CLEAN envelope-based client-side crypto

const encoder = new TextEncoder();

/* ===============================
   Envelope Encryption
=============================== */

// Encrypt file using random AES-GCM key wrapped by passphrase-derived AES-KW key
async function encryptFileEnvelope(file, passphrase) {
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const salt = crypto.getRandomValues(new Uint8Array(16));

    // Generate random file key
    const fileKey = await crypto.subtle.generateKey(
        { name: "AES-GCM", length: 256 },
        true,
        ["encrypt", "decrypt"]
    );

    const plaintext = await file.arrayBuffer();

    const ciphertext = await crypto.subtle.encrypt(
        { name: "AES-GCM", iv },
        fileKey,
        plaintext
    );

    // Derive wrapping key from passphrase
    const baseKey = await crypto.subtle.importKey(
        "raw",
        encoder.encode(passphrase),
        "PBKDF2",
        false,
        ["deriveKey"]
    );

    const wrapKey = await crypto.subtle.deriveKey(
        {
            name: "PBKDF2",
            salt,
            iterations: 310000,
            hash: "SHA-256"
        },
        baseKey,
        { name: "AES-KW", length: 256 },
        false,
        ["wrapKey"]
    );

    const wrappedKey = await crypto.subtle.wrapKey(
        "raw",
        fileKey,
        wrapKey,
        { name: "AES-KW" }
    );

    return {
        ciphertext: new Uint8Array(ciphertext),
        iv,
        salt,
        wrappedKey: new Uint8Array(wrappedKey)
    };
}

// Decrypt envelope-encrypted file
async function decryptFileEnvelope(ciphertext, iv, salt, wrappedKey, passphrase) {
    const baseKey = await crypto.subtle.importKey(
        "raw",
        encoder.encode(passphrase),
        "PBKDF2",
        false,
        ["deriveKey"]
    );

    const wrapKey = await crypto.subtle.deriveKey(
        {
            name: "PBKDF2",
            salt,
            iterations: 310000,
            hash: "SHA-256"
        },
        baseKey,
        { name: "AES-KW", length: 256 },
        false,
        ["unwrapKey"]
    );

    const fileKey = await crypto.subtle.unwrapKey(
        "raw",
        wrappedKey,
        wrapKey,
        { name: "AES-KW" },
        { name: "AES-GCM", length: 256 },
        false,
        ["decrypt"]
    );

    const plaintext = await crypto.subtle.decrypt(
        { name: "AES-GCM", iv },
        fileKey,
        ciphertext
    );

    return new Uint8Array(plaintext);
}

/* ===============================
   Form Handling
=============================== */

const fileInput = document.querySelector("#fileInput");
const passphraseInput = document.querySelector("#passphraseInput");
const form = document.querySelector("#uploadForm");

form.addEventListener("submit", async e => {
    e.preventDefault();
    const file = fileInput.files[0];
    const passphrase = passphraseInput.value;

    if (!passphrase) {
        alert("Please enter a passphrase for encryption!");
        return;
    }

    const { ciphertext, iv, salt, wrappedKey } = await encryptFileEnvelope(file, passphrase);

    const blob = new Blob([ciphertext]);
    const formData = new FormData();
    formData.append("file", blob, file.name);
    formData.append("csrf", document.querySelector("[name='csrf']").value);
    formData.append("iv", btoa(String.fromCharCode(...iv)));
    formData.append("salt", btoa(String.fromCharCode(...salt)));
    formData.append("wrappedKey", btoa(String.fromCharCode(...wrappedKey)));
    formData.append("mode", document.querySelector("[name='mode']").value);

    const res = await fetch(form.action, { method: "POST", body: formData });
    const html = await res.text();
    document.open();
    document.write(html);
    document.close();
});
