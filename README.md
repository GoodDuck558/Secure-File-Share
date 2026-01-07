# Secure File Share (version 2.0)

Secure File Share is a privacy-focused web application for sharing files securely and temporarily.

It is designed to minimize data exposure, reduce server trust, and allow users to share files without permanently storing sensitive information.

---

## Features

- 🔐 Encrypted file storage
- ⏱️ Automatic file expiration (default: 1 hour)
- 🕶️ Anonymous uploads supported
- 🔑 Passphrase-required file decryption
- 👤 Optional user accounts
- 📧 Email-based OTP verification
- ♻️ Resend OTP and navigation controls
- 🗑️ Automatic file deletion after expiry

---

## How It Works

1. A user uploads a file (anonymous or logged-in).
2. The file is encrypted before being stored.
3. The server stores only the encrypted file.
4. The user shares a download link and passphrase separately.
5. The recipient downloads and decrypts the file using the passphrase.
6. The file is automatically deleted after expiration.

---

## Technology Stack

- PHP
- SQLite
- PHPMailer
- Composer
- HTML/CSS/JavaScript

---

## Installation (Development)
Clone the repository:
   ```bash
   git clone https://github.com/GoodDuck558/Secure-File-Upload.git
