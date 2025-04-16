# Image WebP Converter

**Image WebP Converter** is a Drupal module that optimizes image performance by
 converting uploaded images to WebP format. The module supports site-wide 
 configuration and optional per-node settings to control WebP conversion 
 behavior.

---

## Features

- **Multiple Conversion Tools:** Supports `cwebp`, `Imagick`, and `GD` 
for converting images to WebP.
- **Site-Wide Settings:** Configure default image quality, enable lossless 
conversion, and decide whether to display the per-node checkbox.
- **Per-Node Control:** Optionally enable or disable WebP conversion for 
specific nodes via a checkbox on the node add/edit form.
- **Inline Image Conversion:** Converts images embedded in CKEditor fields to 
WebP format.
- **Logging and Error Reporting:** Logs conversion success or failure for 
easier debugging.

---

## Installation

### Step 1: Install the Module
1. Download or clone this module to your `modules/custom` directory:
   ```bash
   git clone https://github.com/uttamkarmakar/image_webp_converter.git 
   modules/custom/image_webp_converter
   ```
   Or use Composer:
   ```bash
   composer require drupal/image_webp_converter
   ```

2. Enable the module:
   ```bash
   drush en image_webp_converter
   ```

3. Clear the cache:
   ```bash
   drush cr
   ```

### Step 2: Install the Required Library
The module uses the `rosell-dk/webp-convert` library for image conversion. 
Install it via Composer:
```bash
composer require rosell-dk/webp-convert
```

---

## Configuration

1. Navigate to **Configuration > Media > Image WebP Converter Settings**.
2. Configure the following options:
   - **Converter Selection:** Choose from `cwebp`, `Imagick`, or `GD`.
   - **Image Quality:** Set a value between 0-100 (lower values reduce file size 
   but may degrade quality).
   - **Lossless Conversion:** Enable or disable lossless WebP conversion.
   - **Per-Node Checkbox:** Decide whether to display a "Convert images to WebP"
    checkbox in the node edit form.

3. Save your settings.

---

## Usage

### Site-Wide WebP Conversion
When enabled in the settings, all uploaded images will be converted to WebP 
format automatically.

### Per-Node WebP Conversion
If the per-node checkbox is enabled:
- A "Convert images to WebP" checkbox will appear in the node add/edit form.
- Check this box to enable WebP conversion for images in that specific node.

### Inline Images in CKEditor
- Inline images added in CKEditor fields (e.g., body field) will be processed 
and converted to WebP format if they are stored in the `public://` directory.

---

## Dependencies

- **Drupal Core:** Version 10.x or higher.
- **External Library:** `rosell-dk/webp-convert` (installed via Composer).

---

## Support

For bug reports, feature requests, or general support, create an issue in the 
repository or contact the maintainer.

**Contributions are welcome!**
