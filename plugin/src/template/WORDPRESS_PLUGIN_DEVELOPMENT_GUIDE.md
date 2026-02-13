# WordPress Plugin Development Guidelines

These guidelines describe a **modern, modular approach** to WordPress plugin development, with an emphasis on **clean code organization**, **separation of concerns**, and **native JavaScript modules**. The goal is to keep plugins understandable, testable, and scalable without unnecessary build complexity.

---

## 1. Core Principles

This is a **highly opinionated framework** that prioritizes simplicity, transparency, and developer experience over tooling complexity.

1. **One responsibility per file**
   - OOP classes in PHP, ES6 modules in JS
   - One class / module per file
   - Main PHP file is mostly WordPress actions, hooks and filters to call OOP classes
2. **Clear separation of concerns**
   - UI logic ≠ business logic ≠ data access
3. **Explicit wiring**
   - A single "entry point" assembles and initializes the system
4. **WordPress-first**
   - Respect WordPress lifecycle hooks
   - Use core APIs before inventing abstractions
5. **No build steps**
   - Ship unbundled ES6 modules directly
   - Browsers natively support ES modules
---

## 2. Plugin Structure

A modular plugin follows a namespace-based directory structure where the plugin slug determines both the namespace and the directory organization:

```
aiplugin123/
├── aiplugin123.php         # Main plugin file
├── readme.txt
├── src/
│   ├── aiplugin123/        # Namespaced PHP directory
│   │   ├── Hooks/
│   │   │   ├── EnqueueAssets.php
│   │   │   └── RegisterPostTypes.php
│   │   ├── Services/
│   │   │   └── ConversationService.php
│   │   └── Controllers/
│   │       └── RestController.php
│   └── js/
│       ├── index.js        # JavaScript entry module
│       ├── ui/
│       │   └── Button.js
│       ├── services/
│       │   └── ApiClient.js
│       └── controllers/
│           └── ConversationController.js
└── tests/
```

**Namespace Convention:**
- Plugin slug: `aiplugin123`
- Root namespace: `aiplugin123`
- Root directory: `aiplugin123/`
- Main file: `aiplugin123/aiplugin123.php`
- Namespaced PHP code: `aiplugin123/src/aiplugin123/`

**Key idea:**
`src/` contains both the namespace directory matching the plugin slug (which houses all PHP classes) and the `js/` directory for JavaScript modules. Both PHP and JavaScript are organized modularly within the `src/` directory.

---

## 3. PHP Architecture (Server-Side)

### 3.1 Main Plugin File

The main plugin file wires together services and hooks directly, using the plugin slug as the root namespace:

```php
// aiplugin123.php
namespace aiplugin123;

use aiplugin123\Hooks\EnqueueAssets;
use aiplugin123\Hooks\RegisterPostTypes;

add_action('plugins_loaded', function () {
    (new RegisterPostTypes())->register();
    (new EnqueueAssets())->register();
});
```

**Namespace Rules:**
- The root namespace matches the plugin slug exactly
- All PHP classes under `src/aiplugin123/` use this namespace as their base
- Subdirectories map to sub-namespaces (e.g., `src/aiplugin123/Hooks/` → `aiplugin123\Hooks`)

This keeps:

- initialization explicit
- dependencies visible
- the entire plugin lifecycle in one place
- namespace collision prevention through unique plugin slugs

---

## 4. JavaScript Modular System (Client-Side)

### 4.1 Goals of the Modular JS System

- One class per file
- Clear import paths
- No global variables
- One entry point per plugin (or per page context)

### 4.2 File Organization

Example:

```
src/js/
├── index.js
├── ui/
│   └── Button.js
├── services/
│   └── ApiClient.js
└── controllers/
    └── ConversationController.js
```

Each folder reflects intent, not technology.

---

## 5. JavaScript Entry Module

The entry module is responsible for wiring, not logic.

```javascript
// src/js/index.js
import { ConversationController } from './controllers/ConversationController.js';

const controller = new ConversationController();
controller.init();
```

Rules:

- No heavy logic here
- No DOM scanning scattered across files
- This is the only file WordPress enqueues

---

## 6. Example Modular JavaScript Classes

### 6.1 UI Component

```javascript
// src/js/ui/Button.js
export class Button {
    constructor(element) {
        this.element = element;
    }

    onClick(handler) {
        this.element.addEventListener('click', handler);
    }
}
```

### 6.2 Service Layer

```javascript
// src/js/services/ApiClient.js
export class ApiClient {
    async post(url, data) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        });

        return response.json();
    }
}
```

### 6.3 Controller

```javascript
// src/js/controllers/ConversationController.js
import { Button } from '../ui/Button.js';
import { ApiClient } from '../services/ApiClient.js';

export class ConversationController {
    init() {
        const buttonEl = document.querySelector('.my-plugin-button');
        if (!buttonEl) return;

        this.api = new ApiClient();
        this.button = new Button(buttonEl);

        this.button.onClick(() => this.handleClick());
    }

    async handleClick() {
        await this.api.post('/wp-json/my-plugin/v1/action', {});
    }
}
```

---

## 7. Enqueuing JavaScript Modules in WordPress

Use Script Modules to load ES modules natively:

```php
wp_enqueue_script_module(
    'aiplugin123/index',
    plugin_dir_url(__FILE__) . 'src/js/index.js',
    [],
    '1.0.0'
);
```

Note: The script handle uses the plugin slug as a prefix to avoid conflicts.

## 8. The No-Build Philosophy

This framework **rejects build steps as a default practice**. Modern browsers support ES6 modules natively, and the complexity introduced by bundlers, transpilers, and build pipelines is rarely justified for WordPress plugins.

---
