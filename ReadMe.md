# VentusForge.Neos.TokenAuthManager

Backend module for managing authentication tokens in [Neos CMS](https://www.neos.io/).

This package provides a UI in the Neos administration area to create, list, edit and delete tokens used by [flownative/token-authentication](https://github.com/flownative/flow-token-auth). Tokens can be assigned Flow roles and an optional expiration date.

## Requirements

- PHP 8.3 or later
- Neos CMS `^8.3`
- [flownative/token-authentication](https://packagist.org/packages/flownative/token-authentication) `^2.5` (installed automatically)

## Installation

```bash
composer require ventusforge/neos-token-auth-manager
```

Please follow the installation instructions provided by the respective package to set up the token storage from `flownative/token-authentication`.

## Usage

Open **Administration → Token Auth Manager** in the Neos backend (Administrators only).

### Create a token

1. Click **Token erstellen**.
2. Enter a label.
3. Optionally set an expiration date. The token will expire on the selected date. Tokens without an expiration date do not expire automatically.
4. Select one or more roles. Requests authenticated with this token receive those roles.
5. Save the token.

A 64-character random token is generated and stored. Use the value shown in the token list for API requests.

### Edit a token

The label of an existing token can be updated from the edit and renew views. Roles and the expiration date are shown as read-only on the edit page and can be changed when renewing the token.

### Delete a token

Delete a token from the list. This is permanent; authenticated requests using that token will fail afterwards.

## Role allowlist

Only roles explicitly set to `true` are shown when creating a token. Submitted roles are checked again on save. Disallowed roles are dropped; the token is still created and a warning flash message lists the ignored roles.

```yaml
VentusForge:
  Neos:
    TokenAuthManager:
      allowedRoles:
        'Some.Package:ApiUser': true
```

Roles that are missing or set to `false` are hidden in the create and renew forms. Existing roles of a token are preselected when renewing it.

## Hide the token column

By default the list shows a shortened token with a copy button. Set `showTokenInList` to `false` to hide that column and disable copying there. The full token is then only visible once, immediately after creating or renewing it.

```yaml
VentusForge:
  Neos:
    TokenAuthManager:
      showTokenInList: false
```

## Access control

The module is protected by the privilege target `VentusForge.Neos.TokenAuthManager:TokenAuthManager`.

`Neos.Neos:Administrator` is granted access by default. To allow another role, grant the same privilege target in your `Policy.yaml`:

```yaml
roles:
  'Your.Package:TokenManager':
    privileges:
      - privilegeTarget: 'VentusForge.Neos.TokenAuthManager:TokenAuthManager'
        permission: GRANT
```

## License

This package is licensed under [GPL-3.0-or-later](LICENSE).
