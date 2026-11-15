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
   scp -P 2222 config.php quinni5@YOUR_SERVER_HOST:/home/quinni5/public_html/config.php
   ```
   Or create/upload it via cPanel File Manager.

4. Verify it is in place:
   ```sh
   ssh -p 2222 quinni5@YOUR_SERVER_HOST "ls -la /home/quinni5/public_html/config.php"
   ```

The site works without `config.php`. Only the contact form requires it; without it,
submissions return a "server configuration not complete" error.

---

## Deployment

### How it works

Same architecture as the VoxMason site. Deployer keeps versioned releases in a sibling
directory and rsyncs the active release into the Apache document root on every deploy and rollback.

```
/home/quinni5/quinnix_deploy/   ← versioned releases (Deployer-managed)
  releases/
    1/
    ...
    5/     ← latest
  current  → releases/5

/home/quinni5/public_html/      ← Apache document root (rsynced from active release)
```

**Publishing is non-destructive:** rsync runs without `--delete`. Existing files and directories in `public_html` that are not part of this site are left untouched. This is intentional — the primary domain root may contain subdomain directories or other cPanel-managed content. See `deploy.php` for notes on enabling `--delete` after the directory contents have been verified.

---

### Prerequisites

**Local machine (the machine you deploy from):**

1. PHP (any version supported by Deployer 6):
   ```sh
   php --version
   ```

2. Deployer 6.8.0 — copy `deployer.phar` from the VoxMason project (already at version 6.8.0):
   ```sh
   cp /home/quinn/openvoice-work/voxmason-site/deployer.phar ./deployer.phar
   php deployer.phar --version    # expect: Deployer 6.8.0

   # Or install system-wide:
   sudo mv deployer.phar /usr/local/bin/dep
   dep --version
   ```
   > **Note:** `deploy.php` targets Deployer 6. Do not replace with Deployer 7 — the
   > API is not backwards-compatible.

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
   mkdir -p /home/quinni5/quinnix_deploy
   ```

6. Document root must exist as a real directory (not a symlink):
   ```sh
   ls -la /home/quinni5/public_html    # confirmed document root
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

All values have confirmed defaults in `deploy.php` and do not need to be set.
Override them only if deploying to a different host or path.

| Variable | Required | Description |
|---|---|---|
| `QUINNIX_REPOSITORY` | ✓ | Git clone URL (confirmed: `git@github.com:rugsrme/quinnix-site.git`) |
| `QUINNIX_DEPLOY_PATH` | ✓ | Versioned release base (confirmed: `/home/quinni5/quinnix_deploy`) |
| `QUINNIX_DOCUMENT_ROOT` | ✓ | Apache document root — confirmed: `/home/quinni5/public_html` |
| `QUINNIX_HOST` | — | SSH hostname (default: `ehub63.webhostinghub.com`) |
| `QUINNIX_SSH_PORT` | — | SSH port (default: `2222`) |
| `QUINNIX_DEPLOY_BRANCH` | — | Branch to deploy (default: `main`) |

Example:

```sh
export QUINNIX_HOST='ehub63.webhostinghub.com'
export QUINNIX_REPOSITORY='git@github.com:rugsrme/quinnix-site.git'
export QUINNIX_DEPLOY_PATH='/home/quinni5/quinnix_deploy'
export QUINNIX_DOCUMENT_ROOT='/home/quinni5/public_html'   # confirmed
export QUINNIX_DEPLOY_BRANCH='main'
```

---

### Deploy commands

Run from the `quinnix-work/` directory. The installed version is **Deployer 6.8.0**,
which does not support `--dry-run`.

**Pre-flight checks (run these before the first deploy):**
```sh
# Verify SSH access
ssh -p 2222 quinni5@ehub63.webhostinghub.com echo "SSH OK"

# Inspect the document root — note every directory and file present
ssh -p 2222 quinni5@ehub63.webhostinghub.com "ls -la /home/quinni5/public_html/"

# Confirm server has rsync and git
ssh -p 2222 quinni5@ehub63.webhostinghub.com "rsync --version && git --version"

# Create the deploy base directory if it does not exist
ssh -p 2222 quinni5@ehub63.webhostinghub.com "mkdir -p /home/quinni5/quinnix_deploy"
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

The rsync task **adds and updates** these files in `/home/quinni5/public_html`:
```
index.html
contact.php
assets/
robots.txt
sitemap.xml
```

Excluded (never written to the document root):
```
deploy.php
config.php          ← placed manually on the server; never rsynced
config.sample.php
deployer.phar
*.md
.git
.well-known/        ← protected (cPanel-managed)
cgi-bin/            ← protected (cPanel-managed)
```

**Non-destructive:** rsync runs without `--delete`. Files already in `public_html`
that are not part of this site are left untouched. Any other directories in the
root (subdomains, cPanel placeholder files) are preserved.

---

### Checklist before first deployment

- [x] Document root confirmed: `/home/quinni5/public_html`
- [x] Deploy base path confirmed: `/home/quinni5/quinnix_deploy`
- [x] Repository confirmed: `git@github.com:rugsrme/quinnix-site.git`
- [x] Publishing is non-destructive (no `--delete`) — existing files in `public_html` are safe
- [ ] SSH pre-flight complete: `ls -la /home/quinni5/public_html/` reviewed
- [ ] Deploy base directory created on server: `mkdir -p /home/quinni5/quinnix_deploy`
- [ ] GitHub repository exists and server has clone access
- [ ] `config.php` uploaded to server (required only for contact form)

---

### SSH verification commands (run before first deploy)

```sh
# Confirm git and rsync
rsync --version && git --version

# Confirm document root exists (replace PATH with confirmed path)
ls -la /home/quinni5/public_html

# Check current contents — do not delete anything unfamiliar
ls -la /home/quinni5/public_html/

# Test SSH connection
ssh -p 2222 quinni5@YOUR_SERVER_HOST echo "SSH OK"
```
