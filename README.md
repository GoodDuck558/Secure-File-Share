Secure File Share

Version: 1.2 (Hardened)
Goal: Private, secure, and temporary file sharing with optional identity or anonymous uploads.

Overview

Secure File Share is a web-based system designed to let users upload files safely and share them via a unique, expiring download link. Files automatically expire after 24 hours. The project emphasizes:

Privacy-first: Metadata stripping, randomized filenames, minimal server knowledge

Security-first: MIME-type validation, upload size limits, session hardening, rate limiting

Flexibility: Optional login mode for identity-based uploads or fully anonymous mode

This project began as a university web technologies assignment and is evolving into a robust, real-world secure file-sharing platform.

Features

v1.2 – Hardening Phase

Threat model defined in SECURITY.md

Server-side file validation & metadata stripping

Upload size limit enforcement

Randomized filenames & storage outside web root

Basic rate limiting (IP & session)

Hardened PHP sessions

Expiring download links (default 24h)

Planned (Future)

Client-side end-to-end encryption

Key-based login system (no passwords)

Anonymous vs identity mode selection

Tor / onion service support

Zero-knowledge server design

Installation

Clone the repository:

git clone https://github.com/GoodDuck558/Secure-File-Share.git
cd Secure-File-Upload


Set up a PHP environment (XAMPP, MAMP, LAMP, etc.)

Ensure you have SQLite3 enabled.

Create the database:

sqlite3 secure_file_share.db < database_schema.sql


Adjust file storage path in upload.php to be outside the web root, e.g.:

$uploadDir = '/path/to/secure_storage/uploads/';


Set proper permissions:

chmod 700 /path/to/secure_storage
chmod 600 /path/to/secure_storage/uploads/*


Open index.php in a browser via your local server.

Usage

(Optional) Register or use anonymous mode.

Upload files via the main form.

Copy the generated link and share.

Files expire automatically after 24 hours.

Security Considerations

Read the SECURITY.md for the full threat model.

Never store sensitive files in the web root.

Ensure HTTPS in production.

Passwords (if using login) are hashed with password_hash().

Session hardening and rate limiting are enforced.
