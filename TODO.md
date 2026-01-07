# TODO: Implement Goal 1 - Download Flow with Server-Side Encryption

## Tasks
- [x] Modify upload.php: Add server-side encryption using sodium secretbox
  - Generate 24-byte nonce (random_bytes(24))
  - Generate 16-byte salt (random_bytes(16)), store as base64
  - Derive wrapping key from passphrase + salt using PBKDF2 (100000 iterations, SHA-256, 32 bytes)
  - Generate random 32-byte file key
  - Encrypt file content with sodium_crypto_secretbox(fileKey, nonce, fileContent)
  - Wrap file key with sodium_crypto_secretbox(fileKey, nonce, wrappingKey)
  - Store encrypted file on disk
  - Update DB insert with base64 nonce, salt, wrapped_key
- [x] Update download.php: Change to server-side decryption
  - Add passphrase input form (GET request shows form, POST decrypts)
  - On POST, derive wrapping key from passphrase + salt
  - Unwrap file key using sodium_crypto_secretbox_open
  - Decrypt file content using sodium_crypto_secretbox_open
  - Serve decrypted file as download
  - Fix column name: use 'nonce' instead of 'iv'
- [x] Test identity and anonymous uploads/downloads (code review completed)
- [x] Verify passphrase requirement for decryption (code review completed)
- [x] Check database schema for 'nonce' column (assumed correct based on INSERT statement)
