# AGENTS.md

## Project Overview

This repository contains the backend API for **SignVault**, a Trackmania sign hosting and sharing platform.

Users can:

- Upload Trackmania sign images
- Organize signs into folders
- Share folders publicly
- Protect folders with passwords
- Copy stable CDN URLs for use directly inside Trackmania

The frontend is a separate Vue application and is not part of this repository.

The primary goal is simplicity, maintainability, and a smooth user experience.

---

# Development Environment

This project is developed using DDEV.

Do not assume PHP, Composer, Node, or Artisan are installed on the host machine.

Prefer DDEV commands.

Examples:

```bash
ddev start

ddev artisan migrate

ddev artisan test

ddev artisan make:model Folder -m

ddev composer install

ddev composer require laravel/sanctum
```

When running Laravel commands, use:

```bash
ddev artisan <command>
```

instead of:

```bash
php artisan <command>
```

When running Composer commands, use:

```bash
ddev composer <command>
```

instead of:

```bash
composer <command>
```

---

# Tech Stack

- Laravel 12
- PHP 8.4+
- MySQL or PostgreSQL
- Laravel Sanctum
- Cloudflare R2 (production)
- MinIO (local development)
- Laravel Filesystem (S3 driver)
- Pest or PHPUnit

---

# Agent Workflow

Before implementing a feature:

1. Read this file.
2. Read README.md.
3. Inspect existing migrations.
4. Inspect existing models.
5. Inspect existing routes.
6. Follow existing conventions.
7. Prefer extending existing code over introducing new patterns.

Always work incrementally.

Do not perform large-scale refactors unless explicitly requested.

---

# Architecture Guidelines

Prefer standard Laravel conventions.

Use:

- Controllers
- Form Requests
- Eloquent Models
- API Resources
- Policies
- Feature Tests

Create services only when business logic becomes genuinely complex.

Keep the codebase boring and maintainable.

---

# Avoid Overengineering

Do not introduce any of the following unless explicitly requested:

- Repository Pattern
- Service Interfaces without multiple implementations
- Command Bus
- CQRS
- Event Sourcing
- Domain Events for simple CRUD
- DTO layers duplicating Form Requests
- Abstract base classes without clear value
- Premature microservice patterns

Favor readability over architectural purity.

---

# Domain Overview

## User

A registered account.

Users own folders and signs.

---

## Folder

Folders organize signs.

Possible structure:

```text
id
user_id
parent_id nullable
name
slug
visibility
password_hash nullable
created_at
updated_at
```

Visibility values:

```text
private
public
password
```

Nested folders may be supported later.

Do not overcomplicate the initial implementation.

---

## Sign

A sign is an uploaded image.

Possible structure:

```text
id
user_id
folder_id
name
description nullable
storage_disk
storage_key
public_url
mime_type
size_bytes
width nullable
height nullable
created_at
updated_at
```

Database stores metadata only.

Files live in object storage.

---

# Storage

Use Laravel's Storage facade.

Use the configured S3-compatible disk.

Local development:

- MinIO

Production:

- Cloudflare R2

Do not write MinIO-specific or R2-specific logic in controllers.

Storage should be abstracted through Laravel's filesystem.

---

# Local MinIO Configuration

Expected local environment:

```env
FILESYSTEM_DISK=s3

AWS_ACCESS_KEY_ID=minio
AWS_SECRET_ACCESS_KEY=minio_password
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=trackmania-signs
AWS_ENDPOINT=http://localhost:9000
AWS_URL=http://localhost:9000/trackmania-signs
AWS_USE_PATH_STYLE_ENDPOINT=true
```

MinIO console:

```text
http://localhost:9001
```

Expected bucket:

```text
trackmania-signs
```

---

# API Principles

All endpoints return JSON.

Use REST-style routing.

Prefer predictable resource naming.

Example:

```text
GET    /api/folders
POST   /api/folders

GET    /api/folders/{folder}
PATCH  /api/folders/{folder}
DELETE /api/folders/{folder}

GET    /api/folders/{folder}/signs
POST   /api/folders/{folder}/signs

GET    /api/signs/{sign}
PATCH  /api/signs/{sign}
DELETE /api/signs/{sign}
```

---

# Authentication

Use Laravel Sanctum.

Users must authenticate before managing folders or signs.

The frontend is a separate Vue application.

Design API responses with SPA usage in mind.

---

# Authorization

Users may only manage their own resources.

Ownership checks should use Policies where practical.

Examples:

- Users cannot view another user's private folder.
- Users cannot upload into another user's folder.
- Users cannot delete another user's signs.

---

# Upload Rules

Allowed file types:

```text
image/png
image/jpeg
image/webp
```

Validate:

- file exists
- mime type
- maximum file size
- ownership of target folder

Future image processing should be isolated from controllers.

---

# Folder Visibility

## Private

Visible only to the owner.

---

## Public

Anyone with the URL can access the folder.

---

## Password Protected

Anyone with the URL may access after supplying the correct password.

Passwords must always be hashed.

Never store plaintext passwords.

---

# Deletion Rules

Deleting a sign should:

1. Remove the database record
2. Remove the file from object storage

Deleting folders should initially be blocked when contents exist.

Only introduce cascade deletion if specifically implemented and tested.

---

# Response Consistency

Prefer consistent JSON responses.

Folder example:

```json
{
    "id": 1,
    "name": "Club Signs",
    "slug": "club-signs",
    "visibility": "private"
}
```

Sign example:

```json
{
    "id": 1,
    "folder_id": 1,
    "name": "Ice Warning",
    "public_url": "https://cdn.example.com/signs/ice-warning.png",
    "mime_type": "image/png",
    "size_bytes": 123456
}
```

---

# Testing Expectations

Every significant feature should include tests.

Prioritize:

- authentication
- folder CRUD
- sign upload
- sign deletion
- ownership enforcement
- public folders
- password-protected folders

Use Laravel fake storage for tests where possible.

Prefer feature tests over excessive unit testing.

---

# Definition of Done

Before completing work:

Run tests:

```bash
ddev artisan test
```

If Pint is installed:

```bash
ddev exec ./vendor/bin/pint
```

Verify:

- routes work
- validation works
- authorization works
- tests pass

---

# Implementation Order

Build features in the following order:

1. Project setup
2. Authentication
3. Folder CRUD
4. Sign upload
5. Sign listing
6. Sign deletion
7. Public folders
8. Password-protected folders
9. Additional polish

Do not build advanced features before the MVP is complete.

---

# Product Goal

The core workflow is:

1. Upload sign
2. Organize sign into a folder
3. View sign in a grid
4. Click sign
5. Copy URL
6. Paste URL into Trackmania

Every implementation decision should support this workflow.
