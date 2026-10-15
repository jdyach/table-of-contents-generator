# Table of Contents Generator

A WordPress plugin that automatically parses H2 (and optionally H3) headings in post content and injects a linked Table of Contents above the post. No shortcodes required — it just works.

[![Try it live in WordPress Playground](https://img.shields.io/badge/Try%20it%20live-WordPress%20Playground-3858e9?logo=wordpress&logoColor=white&style=for-the-badge)](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/jdyach/table-of-contents-generator/main/_playground/blueprint.json)

The live demo runs a real WordPress site in your browser with this plugin, the [Fieldnote](https://github.com/jdyach/fieldnote) block theme and sample content. It opens a long guide with a linked table of contents generated from its H2 and H3 headings. Nothing to install.

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

## Changelog

### 1.0.1
- Fix: TOC links now jump to their headings. Heading `id` attributes were never written back, because parsing with `LIBXML_HTML_NOIMPLIED` creates no `<body>` element to read the result from. Content is now parsed inside a wrapper element.
- Fix: headings are collected with a single XPath query, which returns them in document order. The previous sort relied on positions cached by `spl_object_id()`, which PHP reuses, so entries could appear out of order.
- Fix: repeated heading text now gets unique ids (`intro`, `intro-1`) applied to the right headings.
- Fix: the TOC box lines up with the content column in block themes (it was `inline-block` with `max-width: 100%`, which overrides how block themes size content).
- WordPress Playground live demo.
