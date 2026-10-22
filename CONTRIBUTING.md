# Contributing

Small, focused changes are easiest to review.

1. Branch from `main`: `feat/<folder>-<scope>` or `fix/<folder>-<scope>`.
2. Use [conventional commits](https://www.conventionalcommits.org/) with the folder as scope: `feat(vue-dashboard): …`, `fix(rails-api): …`, `docs: …`.
3. Keep a change inside one folder when you can; each folder has its own CI workflow and README with the commands to lint and test it locally. Run `gitleaks git .` before pushing if you have it.
4. Open a pull request. The folder's workflow and the gitleaks scan must be green.
5. Merge with a **merge commit** (no squash) so the history stays as it happened.

Keep example data fictional. No real names, customers, stores, credentials or personal data.
