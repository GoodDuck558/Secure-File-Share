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
   Ed25519 Identity Functions
=============================== */

async function generateEd25519KeyPair() {
    return crypto.subtle.generateKey(
        { name: "Ed25519" },
        true,
        ["sign", "verify"]
    );
}

async function signMessage(privateKey, message) {
    const data = encoder.encode(message);
    const signature = await crypto.subtle.sign(
        "Ed25519",
        privateKey,
        data
    );
    return new Uint8Array(signature);
}

async function verifySignature(publicKey, message, signature) {
    const data = encoder.encode(message);
    return crypto.subtle.verify(
        "Ed25519",
        publicKey,
        signature,
        data
    );
}

async function exportPublicKey(key) {
    const raw = await crypto.subtle.exportKey("raw", key);
    return btoa(String.fromCharCode(...new Uint8Array(raw)));
}

async function importPublicKey(base64Key) {
    const rawKey = Uint8Array.from(atob(base64Key), c => c.charCodeAt(0));
    return crypto.subtle.importKey(
        "raw",
        rawKey,
        { name: "Ed25519" },
        true,
        ["verify"]
    );
}

/* ===============================
   Global Exports
=============================== */

window.encryptFileEnvelope = encryptFileEnvelope;
window.decryptFileEnvelope = decryptFileEnvelope;

window.generateEd25519KeyPair = generateEd25519KeyPair;
window.signMessage = signMessage;
window.verifySignature = verifySignature;
window.exportPublicKey = exportPublicKey;
window.importPublicKey = importPublicKey;
