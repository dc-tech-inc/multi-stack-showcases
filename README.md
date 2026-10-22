# multi-stack-showcases

Small, tested reference apps by DCtech., one folder per stack. Each folder is self-contained: its own README, one command to run it, fictional data only, and its own CI workflow that runs only when that folder changes.

| Folder | Stack | What it shows | CI |
|---|---|---|---|
| [`next-graphql-mongo/`](next-graphql-mongo) | Next.js 16 (App Router), GraphQL Yoga, MongoDB | Project tracker: Server Component page + GraphQL API, tested against a real MongoDB, Docker compose smoke test | [![next-graphql-mongo](https://github.com/dc-tech-inc/multi-stack-showcases/actions/workflows/next-graphql-mongo.yml/badge.svg)](https://github.com/dc-tech-inc/multi-stack-showcases/actions/workflows/next-graphql-mongo.yml) |
| [`vue-dashboard/`](vue-dashboard) | Vue 3, TypeScript, Vite | Inventory dashboard over mock data shaped like the warehouse API's OpenAPI spec, component tests | [![vue-dashboard](https://github.com/dc-tech-inc/multi-stack-showcases/actions/workflows/vue-dashboard.yml/badge.svg)](https://github.com/dc-tech-inc/multi-stack-showcases/actions/workflows/vue-dashboard.yml) |
| [`rails-api/`](rails-api) | Ruby on Rails 8.1 (API mode), RSpec | Lending-library JSON API with validations, request specs, Docker compose smoke test | [![rails-api](https://github.com/dc-tech-inc/multi-stack-showcases/actions/workflows/rails-api.yml/badge.svg)](https://github.com/dc-tech-inc/multi-stack-showcases/actions/workflows/rails-api.yml) |
| [`shopify-theme/`](shopify-theme) | Shopify Liquid (Online Store 2.0) | Minimal theme validated with Theme Check, no real store | [![shopify-theme](https://github.com/dc-tech-inc/multi-stack-showcases/actions/workflows/shopify-theme.yml/badge.svg)](https://github.com/dc-tech-inc/multi-stack-showcases/actions/workflows/shopify-theme.yml) |
| [`php-realestate-crm/`](php-realestate-crm) | PHP 8, MySQL | Property listings + lead/visit CRM API, PHPUnit and PHPStan | [![php-realestate-crm](https://github.com/dc-tech-inc/multi-stack-showcases/actions/workflows/php-realestate-crm.yml/badge.svg)](https://github.com/dc-tech-inc/multi-stack-showcases/actions/workflows/php-realestate-crm.yml) |
| [`wordpress-plugin/`](wordpress-plugin) | WordPress, PHP | Lead-capture shortcode plugin tested in the WordPress test suite, PHPCS | [![wordpress-plugin](https://github.com/dc-tech-inc/multi-stack-showcases/actions/workflows/wordpress-plugin.yml/badge.svg)](https://github.com/dc-tech-inc/multi-stack-showcases/actions/workflows/wordpress-plugin.yml) |

```mermaid
flowchart LR
  subgraph repo[multi-stack-showcases]
    A[next-graphql-mongo] --- CI1[CI: next-graphql-mongo.yml]
    B[vue-dashboard] --- CI2[CI: vue-dashboard.yml]
    C[rails-api] --- CI3[CI: rails-api.yml]
    D[shopify-theme] --- CI4[CI: shopify-theme.yml]
    E[php-realestate-crm] --- CI5[CI: php-realestate-crm.yml]
    F[wordpress-plugin] --- CI6[CI: wordpress-plugin.yml]
  end
  S[secrets.yml: gitleaks on every push] --> repo
```

## How it is organised

- **One workflow per folder**, filtered with `paths:` so a change in one stack never runs another stack's tests.
- **Secret scanning** (gitleaks, full history) runs on every push and pull request from `secrets.yml`.
- **Dependabot** watches each folder's package manager and the GitHub Actions versions.
- **Fictional data only.** No real customers, stores, credentials or personal data anywhere.

These are deliberately small. They show that the stack is understood and tested, not a full product. The larger product-style reference is [warehouse-inventory-platform](https://github.com/dc-tech-inc/warehouse-inventory-platform).

## Em português

Monorepo de exemplos pequenos e testados, uma pasta por stack, cada uma com README, um comando para rodar, dados fictícios e CI próprio.

## License

[MIT](LICENSE) © 2026 DC TECH INC
