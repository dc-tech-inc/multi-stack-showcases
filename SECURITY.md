# Security policy

These are demo apps with fictional data and are not deployed anywhere. Reports are still welcome.

## Reporting a vulnerability

Please do not open a public issue. Use GitHub's private vulnerability reporting: **Security → Report a vulnerability** on this repository. If that is unavailable, contact DC TECH INC through https://dctech.live.

Include what you found, how to reproduce it, the folder and the affected commit. You can expect an acknowledgement within a few business days.

## Scope notes

- Every folder uses fictional data. Example credentials in `.env.example` files are placeholders for local containers only.
- The repository is scanned with gitleaks on every push and pull request; Dependabot keeps each folder's dependencies and the GitHub Actions versions current.
