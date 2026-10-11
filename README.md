# Table of Contents Generator

A WordPress plugin that automatically parses H2 (and optionally H3) headings in post content and injects a linked Table of Contents above the post. No shortcodes required — it just works.

## Features

- Automatically detects H2 headings (and optionally H3 as sub-items)
- Injects a linked TOC above the post content via `the_content` filter
- Adds `id` attributes to headings so anchor links resolve correctly
- Configurable **TOC title**, **minimum heading threshold**, **heading levels**, and **post types**
- Uses PHP's `DOMDocument` for reliable HTML parsing (no regex on HTML)
- Duplicate heading text handled with auto-incremented slug suffixes
- Existing `id` attributes on headings are preserved

## WordPress Concepts Demonstrated

| Concept | Where Used |
|---|---|
| `the_content` filter | Injecting the TOC above post content |
| `DOMDocument` HTML parsing | `class-tocg-parser.php` — extracting and modifying headings without regex |
| `iterator_to_array()` | Safe iteration over a live `DOMNodeList` |
| `sanitize_title()` | Generating URL-safe heading slugs |
| Settings API (post type checkboxes, number, checkbox) | `class-tocg-settings.php` |
| `get_post_types( ['public' => true] )` | Dynamically listing post types in settings |
| `register_activation_hook` | Setting default options on activation |
| `wp_parse_args()` | Merging saved options with defaults |
| `is_singular()` + `get_post_type()` | Conditional output per post type |

## File Structure

```
table-of-contents-generator/
├── table-of-contents-generator.php  # Plugin bootstrap + activation hook
├── includes/
│   ├── class-tocg-parser.php        # DOMDocument-based heading extraction + id injection
│   ├── class-tocg-output.php        # the_content filter + TOC HTML builder
│   └── class-tocg-settings.php      # Settings API page (Settings → Table of Contents)
├── assets/
│   └── css/front.css                # TOC block styles
└── README.md
```

## Installation

1. Upload the `table-of-contents-generator` folder to `/wp-content/plugins/`
2. Activate in **Plugins → Installed Plugins**
3. Configure via **Settings → Table of Contents**
4. Open any post or page with 3+ H2 headings — the TOC appears automatically

## Requirements

- WordPress 6.0+
- PHP 8.0+
