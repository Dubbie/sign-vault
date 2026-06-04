# Security Policy

## Reporting a Vulnerability

If you discover a security vulnerability in SignVault, please **do not open a public issue**. Instead, report it privately by emailing:

**miho.dnl@gmail.com**

Please include:
- A description of the vulnerability and its potential impact
- Steps to reproduce or a proof of concept
- Any suggested fix if you have one

You can expect an acknowledgement within **48 hours** and a resolution or status update within **7 days**.

## Scope

The following are in scope:

- Authentication and session handling (Discord OAuth2 flow, Sanctum tokens)
- File upload validation and storage (sign images)
- Folder visibility and access controls (private, public, password-protected)
- API authorization and data exposure

The following are **out of scope**:

- Vulnerabilities in third-party dependencies (report these upstream)
- Issues requiring physical access to infrastructure
- Social engineering attacks

## Disclosure

Once a fix is in place, we will publish a brief disclosure in the repository. We ask that you allow reasonable time for a fix before any public disclosure.
