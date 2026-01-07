// crypto_upload.js
await sodium.ready;

async function encryptFileForUpload(file, passphrase) {
    const salt = sodium.randombytes_buf(16);           // FIXED 16 bytes
    const nonce = sodium.randombytes_buf(
        sodium.crypto_secretbox_NONCEBYTES
    ); // 24 bytes

    const key = sodium.crypto_pwhash(
        sodium.crypto_secretbox_KEYBYTES,
        passphrase,
        salt,
        sodium.crypto_pwhash_OPSLIMIT_MODERATE,
        sodium.crypto_pwhash_MEMLIMIT_MODERATE,
        sodium.crypto_pwhash_ALG_DEFAULT
    );

    const plaintext = new Uint8Array(await file.arrayBuffer());
    const ciphertext = sodium.crypto_secretbox_easy(
        plaintext,
        nonce,
        key
    );

    return {
        ciphertext: sodium.to_base64(ciphertext),
        nonce: sodium.to_base64(nonce),
        salt: sodium.to_base64(salt)
    };
}

window.encryptFileForUpload = encryptFileForUpload;
