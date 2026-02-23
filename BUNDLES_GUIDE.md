# 📦 Symfony Bundles Guide - UniLearn PI Project

## 🎯 WHERE TO FIND BUNDLES

### **1. Main Bundle Configuration**
**File**: `config/bundles.php`
- **Purpose**: Lists all enabled Symfony bundles
- **Environment**: All environments (dev, prod, test)

### **2. Package Configurations**
**Directory**: `config/packages/`
- **Purpose**: Individual bundle configurations
- **Files**: YAML files for each bundle

### **3. Composer Dependencies**
**File**: `composer.json`
- **Purpose**: Lists all installed bundle packages
- **Section**: `require` and `require-dev`

---

## 📋 ALL BUNDLES IN YOUR PROJECT

### **🔧 Core Symfony Bundles**
| Bundle | Purpose | Config File |
|--------|---------|------------|
| `FrameworkBundle` | Core framework functionality | `config/packages/framework.yaml` |
| `TwigBundle` | Template engine | `config/packages/twig.yaml` |
| `SecurityBundle` | Authentication & Authorization | `config/packages/security.yaml` |
| `DoctrineBundle` | Database ORM | `config/packages/doctrine.yaml` |
| `MonologBundle` | Logging | `config/packages/monolog.yaml` |
| `MailerBundle` | Email sending | `config/packages/mailer.yaml` |
| `ValidatorBundle` | Form validation | `config/packages/validator.yaml` |
| `TranslationBundle` | Internationalization | `config/packages/translation.yaml` |

### **🎨 UI/UX Bundles**
| Bundle | Purpose | Config File |
|--------|---------|------------|
| `StimulusBundle` | JavaScript interactions | - |
| `TurboBundle` | Fast page navigation | - |
| `TwigExtraBundle` | Advanced Twig features | - |
| `AssetMapperBundle` | Asset management | `config/packages/asset_mapper.yaml` |
| `WebProfilerBundle` | Development toolbar | `config/packages/web_profiler.yaml` |

### **📊 Database Bundles**
| Bundle | Purpose | Config File |
|--------|---------|------------|
| `DoctrineMigrationsBundle` | Database migrations | `config/packages/doctrine_migrations.yaml` |
| `StofDoctrineExtensionsBundle` | Doctrine extensions | `config/packages/stof_doctrine_extensions.yaml` |
| `DoctrineFixturesBundle` | Test data fixtures | - |

### **📄 File Upload Bundles**
| Bundle | Purpose | Config File |
|--------|---------|------------|
| `VichUploaderBundle` | File uploads | `config/packages/vich_uploader.yaml` |

### **🔢 Utility Bundles**
| Bundle | Purpose | Config File |
|--------|---------|------------|
| `KnpPaginatorBundle` | Pagination | - |
| `NotifierBundle` | Notifications | `config/packages/notifier.yaml` |
| `DebugBundle` | Debug toolbar | - |
| `MakerBundle` | Code generation | - |

---

## 🚀 HOW TO OPEN BUNDLE FILES

### **Method 1: Direct File Path**
```bash
# Main bundles configuration
config/bundles.php

# Individual bundle configs
config/packages/framework.yaml
config/packages/security.yaml
config/packages/doctrine.yaml
config/packages/twig.yaml
```

### **Method 2: IDE Navigation**
1. **Open your IDE** (VS Code, PhpStorm, etc.)
2. **Navigate to**: `c:\Users\salah\Downloads\UniLearn-PI-dev\UniLearn-PI-dev\config\`
3. **Open**: `bundles.php` or any file in `packages/` folder

### **Method 3: File Explorer**
```
UniLearn-PI-dev/
├── config/
│   ├── bundles.php          ← MAIN BUNDLE CONFIGURATION
│   ├── packages/
│   │   ├── framework.yaml   ← FRAMEWORK BUNDLE
│   │   ├── security.yaml     ← SECURITY BUNDLE
│   │   ├── doctrine.yaml     ← DOCTRINE BUNDLE
│   │   ├── twig.yaml         ← TWIG BUNDLE
│   │   ├── monolog.yaml      ← MONOLOG BUNDLE
│   │   └── ...
│   └── services.yaml         ← SERVICE DEFINITIONS
```

---

## 🔧 BUNDLE CONFIGURATION EXAMPLES

### **Framework Bundle** (`config/packages/framework.yaml`)
```yaml
framework:
    secret: '%env(APP_SECRET)%'
    #csrf_protection: true
    http_client:
        default_options:
            timeout: 60
```

### **Security Bundle** (`config/packages/security.yaml`)
```yaml
security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordHasher\Argon2idPasswordHasher:
            algorithm: auto
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email
```

### **Doctrine Bundle** (`config/packages/doctrine.yaml`)
```yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
    orm:
        auto_generate_proxy_classes: true
        auto_mapping: true
```

---

## 📝 COMPOSER.JSON BUNDLES LIST

### **Required Bundles**
```json
{
    "require": {
        "symfony/framework-bundle": "6.4.*",
        "symfony/twig-bundle": "6.4.*",
        "symfony/security-bundle": "6.4.*",
        "doctrine/doctrine-bundle": "^2.18",
        "doctrine/doctrine-migrations-bundle": "^3.7",
        "doctrine/orm": "^2.19",
        "knplabs/knp-paginator-bundle": "^6.10",
        "stof/doctrine-extensions-bundle": "^1.15",
        "vich/uploader-bundle": "^1.15",
        "symfony/monolog-bundle": "^3.0|^4.0",
        "symfony/stimulus-bundle": "^2.32",
        "symfony/ux-turbo": "^2.32",
        "twig/extra-bundle": "^2.12|^3.0"
    }
}
```

---

## 🎯 QUICK ACCESS TO IMPORTANT BUNDLES

### **🔧 Most Used Bundle Files**
1. **`config/bundles.php`** - Enable/disable bundles
2. **`config/packages/security.yaml`** - User authentication
3. **`config/packages/doctrine.yaml`** - Database configuration
4. **`config/packages/framework.yaml`** - Core framework settings
5. **`config/services.yaml`** - Service definitions

### **📁 Bundle Package Directory**
```
config/packages/
├── asset_mapper.yaml
├── cache.yaml
├── debug.yaml
├── doctrine.yaml
├── doctrine_migrations.yaml
├── framework.yaml
├── mailer.yaml
├── monolog.yaml
├── notifier.yaml
├── routing.yaml
├── security.yaml
├── stof_doctrine_extensions.yaml
├── twig.yaml
├── validator.yaml
├── vich_uploader.yaml
└── web_profiler.yaml
```

---

## 🔍 HOW TO CHECK BUNDLE STATUS

### **List All Enabled Bundles**
```bash
php bin/console debug:container
```

### **Check Specific Bundle**
```bash
php bin/console debug:router
php bin/console debug:config doctrine
php bin/console debug:config security
```

### **List All Commands**
```bash
php bin/console list
```

---

## 🚀 ADDING NEW BUNDLES

### **Step 1: Install Bundle**
```bash
composer require some/new-bundle
```

### **Step 2: Enable Bundle**
Add to `config/bundles.php`:
```php
return [
    // ... existing bundles
    Some\NewBundle\NewBundle::class => ['all' => true],
];
```

### **Step 3: Configure Bundle**
Create config file: `config/packages/new_bundle.yaml`

---

## 📚 RECOMMENDED FILES TO OPEN

### **🎯 For Development**
- `config/bundles.php` - Bundle management
- `config/packages/security.yaml` - Security settings
- `config/packages/doctrine.yaml` - Database config
- `config/services.yaml` - Service definitions

### **🔧 For Configuration**
- `composer.json` - Package dependencies
- `.env` - Environment variables
- `config/packages/framework.yaml` - Framework settings

### **📊 For Debugging**
- `config/packages/monolog.yaml` - Logging configuration
- `config/packages/web_profiler.yaml` - Debug toolbar

---

*Last Updated: 2024-01-23*
*Version: 1.0*
