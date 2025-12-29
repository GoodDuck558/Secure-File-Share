# Security Overview

This document describes the security model, threat assumptions, and limitations
of the Secure File Share project (current version: 1.1).

This project is designed with a privacy-first mindset and assumes a hostile
environment.

---

## Threat Model

### Adversaries Considered

The system assumes the following adversaries may exist:

- Malicious users attempting abuse or data exfiltration
- Automated bots performing brute-force or spam attacks
- Skilled attackers attempting server compromise
- Network-level observers
- Hosting providers and infrastructure operators
- Government-level adversaries with legal or extralegal powers

No party is inherently trusted.

---

## Security Goals

- Minimize trust in the server
- Minimize stored data and metadata
- Prevent unauthorized file access
- Limit impact of server compromise
- Ensure files expire automatically
- Avoid long-term identifiers where possible

---

## What the Server Knows

Currently, the server knows:

- Encrypted or plaintext uploaded file contents (v1.1)
- Randomized internal file identifiers
- File expiration timestamps
- User account identifiers (if logged in)

The server does **not** attempt to infer file meaning or purpose.

---

## What the Server Does NOT Know (Design Intent)

- Why a file was uploaded
- Who the intended recipient is
- The relationship between sender and receiver
- Any user-provided encryption keys (future versions)

---

## Authentication Model

Version 1.1 uses a traditional username + password login system:

- Passwords are hashed using a strong one-way hashing algorithm
- Plaintext passwords are never stored
- Sessions are used to maintain authentication state

Future versions plan to introduce:
- Key-based authentication
- Optional anonymous usage modes
- Passwordless login mechanisms

---

## File Handling & Expiration

- Files are stored with randomized filenames
- Original filenames are not reused
- Files automatically expire after 24 hours
- Expired files are deleted and become inaccessible

Expiration is enforced server-side.

---

## Metadata Minimization

The system attempts to minimize metadata:

- No long-term access logs are intentionally kept
- No tracking or analytics are implemented
- No third-party scripts are used

Some unavoidable metadata may exist at the infrastructure or network level.

---

## Brute Force & Abuse Prevention

Basic protections include:

- Session-based access controls
- Limited upload size
- Restricted file types

Rate limiting and further abuse prevention mechanisms are planned.

---

## HTTPS & Transport Security

The system is intended to be deployed behind HTTPS.

All authentication and file transfers must occur over encrypted connections.
Running the system over plain HTTP is considered insecure.

---

## Server Compromise Considerations

If the server is compromised or seized:

- Stored files may be accessible until expiration
- User account data may be exposed
- Files are designed to expire and delete automatically

Future versions aim to:
- Encrypt files client-side
- Minimize or eliminate server-side secrets
- Reduce the impact of server seizure

---

## Limitations (Important)

This project does NOT currently protect against:

- A fully compromised host at runtime
- Malicious hosting providers
- Global traffic correlation attacks
- Advanced government-level surveillance

Users should not treat the system as an anonymity guarantee.

---

## Responsible Disclosure

Security issues should be reported privately to the project maintainer.

Please include:
- Clear description of the issue
- Steps to reproduce
- Potential impact

Do not publicly disclose vulnerabilities without coordination.

---

## Status

This project is under active development.
Security assumptions and guarantees may change between versions.
