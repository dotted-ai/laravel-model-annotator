# 🔍 Laravel Model Annotator

[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

**Laravel Model Annotator** is a developer tool that automatically adds PHPDoc annotations to your Eloquent model classes based on your database schema. It improves IDE autocompletion and static analysis for properties, relationships, and casted attributes.

---

## 🚀 Features

- ✅ Annotates all model properties based on database columns.
- 🔁 Detects and documents Eloquent relationships (`hasMany`, `belongsTo`, etc).
- 🧠 Adds annotations for casted attributes using Laravel's `$casts`.
- ✨ IDE-friendly annotations for better autocompletion and PHPStan support.
- 🪄 Works out of the box — just install and run.

---

## 📦 Installation

### Option 1: Via Composer

If published to Packagist:

```bash
composer require dottedai/laravel-model-annotator --dev
````

### Option 2: Local Development

1. Clone or download the repository into a `packages` directory:

```bash
mkdir -p packages/dottedai
git clone https://github.com/dottedai/laravel-model-annotator packages/dottedai/laravel-model-annotator
```

2. Add to your `composer.json`:

```json
"repositories": [
  {
    "type": "path",
    "url": "packages/dottedai/laravel-model-annotator"
  }
]
```

3. Require it locally:

```bash
composer require dottedai/laravel-model-annotator:*
```

---

## ⚙️ Usage

After installation, run the Artisan command:

```bash
php artisan models:annotate
```

This will scan the `app/Models` directory and update each model class with a PHPDoc block containing:

* `@property` for columns (with types and nullability),
* `@property` for relationships,
* `@property` for casted attributes.

---

## 🧪 Example Output

```php
/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property \App\Models\Team $team
 * @property Carbon\Carbon $created_at
 */
class User extends Model
```

---

## 🛠 Configuration

By default:

* Scans `app/Models`.
* Infers column types and nullability from DB schema.
* Detects relationship methods with no arguments.
* Reads `$casts` array from the model.

---

## 🧠 How It Works

* Uses Laravel’s Schema Builder to inspect each model’s database table.
* Reflects on model methods to identify Eloquent relationships.
* Combines schema and relationship data into a single annotation block.
* Rewrites the top of the model file with the updated docblock.

---

## 🧱 Requirements

* PHP 8.0+
* Laravel 9, 10, or 11

---

## 📄 License

This project is open-sourced under the [MIT license](LICENSE).
