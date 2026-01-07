This document describes the security goals, assumptions, and limitations of Secure File Share.

(version 2.0)
---

## Security Goals

- Prevent casual or unauthorized access to shared files
- Limit the amount of data stored on the server
- Reduce the impact of server compromise
- Avoid long-term data retention
- Support anonymous usage

---

## What This Protects Against

- Curious third parties
- Accidental data leaks
- Unauthorized access by other users
- Basic automated attacks
- Server-side data exposure after file expiration

---

## What This Does NOT Protect Against

- Compromised user devices
- Weak or reused passphrases
- Targeted malware or keyloggers
- Advanced state-level targeted attacks
- Network-level surveillance without HTTPS or Tor

---

## Current Security Measures

- Files are encrypted before storage
- Decryption requires a user-supplied passphrase
- Files automatically expire and are deleted
- Anonymous uploads are supported
- Minimal long-term data storage
- OTP verification for accounts

---

## Known Limitations

- Encryption is currently server-assisted
- Metadata such as file size and upload timing may be visible
- IP addresses may be temporarily visible for operational reasons
- HTTPS is required in production but may not be enabled in development

---

## Planned Improvements

- Client-side encryption using the Web Crypto API
- Metadata minimization (filename stripping, padding)
- Tor (.onion) service support
- Strong key derivation (Argon2 or PBKDF2)
- Improved secure deletion strategies
- More explicit abuse prevention mechanisms

---

## Reporting Security Issues

If you discover a security issue, please report it responsibly and do not publicly disclose details without coordination.
