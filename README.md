# Quinnix Software — Website

Marketing and portfolio website for **Quinnix Software** at **quinnix.com**.

---

## Structure

```
quinnix-work/
├── index.html                Main page (all sections)
├── contact.php               Contact form handler (PHP)
├── config.sample.php         Config template — copy to config.php on the server
├── deploy.php                Deployer 7 configuration
├── robots.txt
├── sitemap.xml
├── assets/
│   ├── css/site.css          All styles (CSS custom properties, easy to restyle)
│   ├── js/site.js            Nav, scroll behaviour, form submission
│   └── images/               Placeholder SVGs + favicon
└── README.md
```

No build step, framework, database, or analytics. PHP is only required for the contact form.

---

## Preview locally

```sh
python3 -m http.server 8080
# open http://127.0.0.1:8080
```

The contact form will not function locally without PHP. All other sections work as a static preview.

---

## Content configuration

Branding text, taglines, and section copy are in `index.html`.
CSS custom properties (`--accent`, `--bg`, etc.) are in `assets/css/site.css` under `:root`.
JS-side config (GitHub URL, social links) is in the `CONFIG` object at the top of `assets/js/site.js`.

---

## Contact form setup

The form posts to `contact.php`. That file requires `config.php` in the same directory on the server.
`config.php` is **not in the repository** (it is in `.gitignore`).

**To set it up:**

1. Copy the template:
   ```sh
   cp config.sample.php config.php
   ```

2. Edit `config.php` and set `CONTACT_TO` to the real recipient email address.

3. Upload it to the server:
   ```sh
   scp -P 2222 config.php quinni5@YOUR_SERVER_HOST:/home/quinni5/quinnix.com/config.php
   ```
   Or create/upload it via cPanel File Manager.

4. Verify it is in place:
   ```sh
   ssh -p 2222 quinni5@YOUR_SERVER_HOST "ls -la /home/quinni5/quinnix.com/config.php"
   ```

The site works without `config.php`. Only the contact form requires it; without it,
submissions return a "server configuration not complete" error.

---

## Deployment

### How it works

Same architecture as the VoxMason site. Deployer keeps versioned releases in a sibling
directory and rsyncs the active release into the Apache document root on every deploy and rollback.

```
/home/quinni5/quinnix-deploy/   ← versioned releases (Deployer-managed)
  releases/
    1/
    ...
    5/     ← latest
  current  → releases/5

/home/quinni5/quinnix.com/      ← Apache document root (rsynced from active release)
```

**The document root path must be confirmed before the first deploy.** Do not assume it.

---

### Prerequisites

**Local machine (the machine you deploy from):**

1. PHP 8.0+:
   ```sh
   php --version
   ```

2. Deployer 7 — install system-wide or copy `deployer.phar` from the VoxMason project:
   ```sh
   # System-wide (recommended):
   curl -LO https://deployer.org/deployer.phar
   chmod +x deployer.phar
   sudo mv deployer.phar /usr/local/bin/dep
   dep --version    # expect: Deployer 7.x

   # Or copy from VoxMason (if available locally):
   cp /home/quinn/openvoice-work/voxmason-site/deployer.phar ./deployer.phar
   # then run: php deployer.phar deploy production
   ```

3. SSH access to WebHostingHub (port 2222):
   ```sh
   ssh -p 2222 quinni5@YOUR_SERVER_HOST echo "SSH OK"
   ```

**Server:**

4. Git and rsync must be installed (verify via SSH):
   ```sh
   rsync --version && git --version
   ```

5. Create the deploy base directory on first run:
   ```sh
   mkdir -p /home/quinni5/quinnix-deploy
   ```

6. Document root must exist as a real directory (not a symlink):
   ```sh
   ls -la /home/quinni5/quinnix.com    # ← confirm this path first
   ```

**GitHub repository access on the server:**

If the repository is public, HTTPS cloning works without authentication.
If it is private, choose one:

- **SSH deploy key:** Generate a key on the server, add the public key as a read-only deploy key
  in the GitHub repository settings. Set `QUINNIX_REPOSITORY` to the SSH clone URL.
- **HTTPS token:** Generate a fine-grained GitHub token with repository read access.
  Set `QUINNIX_REPOSITORY` to `https://TOKEN@github.com/USER/REPO.git`.

---

### Environment variables

Set these in your shell before running `dep`. Do not commit them.

| Variable | Required | Description |
|---|---|---|
| `QUINNIX_REPOSITORY` | ✓ | Git clone URL of this repository |
| `QUINNIX_DEPLOY_PATH` | ✓ | Versioned release base, e.g. `/home/quinni5/quinnix-deploy` |
| `QUINNIX_DOCUMENT_ROOT` | ✓ | Confirmed Apache document root — **confirm before first deploy** |
| `QUINNIX_HOST` | — | SSH hostname (default: `ehub63.webhostinghub.com`) |
| `QUINNIX_SSH_PORT` | — | SSH port (default: `2222`) |
| `QUINNIX_DEPLOY_BRANCH` | — | Branch to deploy (default: `main`) |

Example:

```sh
export QUINNIX_HOST='ehub63.webhostinghub.com'
export QUINNIX_REPOSITORY='git@github.com:USER/quinnix-site.git'
export QUINNIX_DEPLOY_PATH='/home/quinni5/quinnix-deploy'
export QUINNIX_DOCUMENT_ROOT='/home/quinni5/quinnix.com'   # ← confirm this first
export QUINNIX_DEPLOY_BRANCH='main'
```

---

### Deploy commands

Run from the `quinnix-work/` directory.

**Dry run (always do this first):**
```sh
dep deploy production --dry-run
```

**Deploy:**
```sh
dep deploy production
```

**List retained releases:**
```sh
dep releases production
```

**Roll back:**
```sh
dep rollback production
```

**Unlock after an interrupted deploy:**
```sh
dep deploy:unlock production
```

---

### What is published to the document root

The rsync task publishes:
```
index.html
contact.php
assets/
robots.txt
sitemap.xml
```

Excluded from the document root:
```
deploy.php
config.php          ← placed manually on the server; never rsynced
config.sample.php
deployer.phar
*.md
.git
```

---

### Open questions before first deployment

1. **Document root path:** SSH in and confirm the exact path of the Apache document root for
   `quinnix.com`. Find it in cPanel → Addon Domains or cPanel → File Manager.
   Do not deploy until this is confirmed.

2. **Deploy base path:** Confirm `/home/quinni5/quinnix-deploy` is usable, or choose another path.

3. **GitHub repository URL:** Create the GitHub repository and confirm the clone URL before deploying.

4. **config.php on server:** Upload `config.php` with the real contact email before testing the form.

5. **Document root contents:** SSH in and check what is in the confirmed document root before the
   first deploy. Do not delete anything unfamiliar.

---

### SSH verification commands (run before first deploy)

```sh
# Confirm git and rsync
rsync --version && git --version

# Confirm document root exists (replace PATH with confirmed path)
ls -la /home/quinni5/quinnix.com

# Check current contents — do not delete anything unfamiliar
ls -la /home/quinni5/quinnix.com/

# Test SSH connection
ssh -p 2222 quinni5@YOUR_SERVER_HOST echo "SSH OK"
```
