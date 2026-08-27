---
name: laravel-boilerplate-playbook
description: >-
  Expert engineering playbook and architecture skills for this Laravel 13 REST API Boilerplate.
  Use when scaffolding API resources, implementing zero N+1 query builders, handling pre-signed S3 media uploads,
  managing topic localization, configuring developer suite tools, or running pre-commit quality gateways.
---

# Laravel 13 REST API Boilerplate Engineering Skill

This skill guides the AI assistant in developing, extending, refactoring, and maintaining the Laravel 13 REST API Boilerplate according to established enterprise architecture patterns.

---

## 1. REST API Query Building & Zero N+1 Rules

When creating or modifying Eloquent queries and endpoints:
- Use `Model::apiQuery()` or `QueryBuilder::for(Model::class)`.
- Use the `#[RequiresRelation('relationName:columns')]` attribute on computed model accessors (e.g. `initials`, `avatarUrl`, `latestTokenName`) so relations are automatically eager-loaded ONLY when requested via `?append=...`.
- Never execute lazy loads inside resource transformations or controller actions (`Model::shouldBeStrict()` will throw in dev).
- Support standard filters (`?filter[name]=...`), multi-column search (`?search=...`), sorting (`?sort=-created_at`), and sparse fieldsets (`?fields=id,name`).

---

## 2. Direct Pre-Signed Cloud Uploads

When implementing file uploads:
1. Endpoint `POST /api/v1/signed-url` generates a pre-signed S3 PUT URL (or local development route).
2. The client uploads the binary directly to the pre-signed URL.
3. In resource mutation requests (e.g. `StoreRequest`, `UpdateRequest`), use `App\Rules\MediaRule::rules(config('media.tags.tag_name'))` to validate the media object.
4. Call `$model->syncMedia($data['tag_name'], 'tag_name')` to attach media polymorphically.

---

## 3. Topic-Driven Localization

Always use topic-based translation keys under `lang/en/`:
- `entity.*` (`lang/en/entity.php`) — Domain nouns (User, Media, Token, Profile).
- `message.*` (`lang/en/message.php`) — API feedback and error strings.
- `status.*` (`lang/en/status.php`) — State & enum labels (Active, Pending, Degraded).
- `email.*` (`lang/en/email.php`) — Transactional email subjects, greetings, and bodies.
- `notification.*` (`lang/en/notification.php`) — Push & in-app alerts.

---

## 4. Quality Gateway & Pre-Commit Verification

Always run the full pre-commit pipeline before completing tasks:
```bash
./.husky/pre-commit
```
Checks performed:
1. **Laravel Pint** formatting.
2. **OpenAPI & TypeScript Types** auto-generation (`api.json` -> `types/schema.d.ts`).
3. **Pest Architecture Tests**.
4. **Pest Feature & Unit Suite**.
5. **PHPStan Level 6 Strict Static Analysis** (0 errors allowed).
