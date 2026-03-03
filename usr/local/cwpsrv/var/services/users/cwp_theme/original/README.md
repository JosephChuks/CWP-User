# CWP Original Theme (Kinsmen Edition)

A customized version of the default **CWP (CentOS Web Panel) "original" user theme**, with enhanced styling and a custom file manager integration.

---

## Features

- Modified layout and styles based on the default CWP `original` theme
- Custom file manager powered by [cwp_filemanager](https://github.com/josephchuks/cwp_filemanager)
- Dark mode support
- Responsive design improvements

---

## Requirements

- CentOS Web Panel (CWP) installed on the server
- SSH/root access to the server
- Git installed on the server (for cloning)

---

## Installation

### 1. Clone the repository

SSH into your server and clone this repository:

```bash
git clone https://github.com/JosephChuks/cwp_original.git
```

### 2. Deploy the theme

Copy (or move) the cloned folder to the CWP user themes directory and name it `original`, overwriting the existing default theme:

```bash
cp -r cwp_original /usr/local/cwpsrv/var/services/users/cwp_theme/original
```

> **Note:** This will overwrite the existing `original` theme. Back it up first if needed:
> ```bash
> cp -r /usr/local/cwpsrv/var/services/users/cwp_theme/original /usr/local/cwpsrv/var/services/users/cwp_theme/original_backup
> ```

### 3. Set correct permissions

```bash
chown -R root:root /usr/local/cwpsrv/var/services/users/cwp_theme/original
chmod -R 755 /usr/local/cwpsrv/var/services/users/cwp_theme/original
```

### 4. Install the custom file manager

This theme uses a custom file manager. Install it separately by following the instructions at:

**[https://github.com/josephchuks/cwp_filemanager](https://github.com/josephchuks/cwp_filemanager)**

---

## Updating

To pull the latest changes:

```bash
cd /usr/local/cwpsrv/var/services/users/cwp_theme/original
git pull origin main
```

---

## Directory Structure

```
original/
├── css/                  # Stylesheets (Bootstrap, dark mode, custom styles)
├── js/                   # JavaScript files and Vue.js components
├── img/                  # Images and icons
├── fonts/                # Web fonts
├── font-awesome/         # Font Awesome icon library
├── modules/              # Theme module templates
├── sound/                # Notification sounds
├── mod_*.html            # CWP module template files
├── index.html            # Main dashboard layout
├── login.html            # Login page template
├── sidebar.html          # Sidebar navigation
└── footer.html           # Footer template
```

---

## License

This project is a modification of the CWP original theme. Use at your own risk. No warranty is provided.
