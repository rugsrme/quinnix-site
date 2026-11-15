# Changelog

## [Unreleased]

### Changed
- **Screenshot presentation** — replaced narrow side-by-side layout with dedicated screenshot showcases on all three product pages:
  - `voxmason.html`: wide single showcase of Narration Studio (`VoxMasonNarration.png`); removed duplicate screenshot from Status section
  - `arctic-link.html`: dual showcase — LLM Studio (primary, 3fr) + SSH & network management (secondary, 2fr) using both Arctic Link screenshots; removed Connection screenshot from Status section
  - `reelgenerator.html`: two-panel pair — Storyboard editor (`ReelGenerator.png`, top-cropped to remove empty space) + Actual frame sequence (`ReelGenFrames.png`)
- **Click-to-enlarge lightbox** — accessible image viewer added to `site.js` (Escape key, click outside to close, focus management, `aria-modal`)
- **CSS** (`site.css`): added `.section-intro`, `.screenshot-showcase`, `.screenshot-duo`, `.screenshot-pair`, shared `.ss-link` / `.ss-img` / `.ss-label` / `.ss-caption` helpers, `.lightbox` overlay — all responsive, collapse to single column at ≤600px

### Added
- **Project pages**: `arctic-link.html`, `voxmason.html`, `reelgenerator.html`
  - Each has a hero section with project-specific SVG art, feature list, status block, and technology grid
  - Arctic Link: network topology SVG, feature breakdown across SSH, networking, hardware monitoring, local AI, conversations, Arctic-Audio integration
  - VoxMason: audio waveform SVG, TTS workflows, narration, audiobook production, voice cloning (marked experimental)
  - ReelGenerator: film-strip SVG, conservative early-stage descriptions, all experimental features clearly marked
  - Project nav links between pages; footer with all project links
- **404 page**: `404.html` with animated drifting fractured wireframe sphere, neon landscape, pulsing satellite; respects `prefers-reduced-motion`
- **`.htaccess`**: `ErrorDocument 404 /404.html`, security headers, asset caching, `Options -Indexes`, blocks access to `config.php`, `deploy.php`, `.md` files

### Changed
- About section: replaced all placeholder text with Quinn Stewart's biography
  - Eyebrow subtitle: "From BASIC to AI — and Everything in Between"
  - Full personal history from 1982 TI-99/4A through Linux, web development, and AI
  - Closing tagline "From Widgets to Working Applications." styled with neon accent
  - **Photo**: replaced dashed circular placeholder with `quinn-stewart.png` (rounded rectangular frame, neon border glow)
- About image CSS: `border-radius: 50%` → `border-radius: var(--radius-lg)`, `max-width` 220px → 300px, grid column updated to 300px; `min-width:0` on text column prevents overflow
- Contact form JS: fixed **JavaScript syntax error** (`I'll` apostrophe inside single-quoted string) that prevented `site.js` from loading — causing the browser to display raw JSON instead of handling the response; success feedback now shows "Message sent!" title with body text via `innerHTML`
- CSS: added `.feedback-title`, `.form-feedback p` for richer success message layout
- CSS: added full project-page component system (`.project-page-hero`, `.feature-list`, `.feature-item`, `.tech-pill`, `.status-block`, `.screenshot-placeholder`, `.sibling-nav-*`, error-page styles, animations)
- `index.html`: added "Learn more" links to all three project cards; updated footer nav with individual project links
- Contact form: switched from `mail()` to PHPMailer 6.9.1 over SMTP (PHP mail() non-functional on host)
- `config.sample.php`: added SMTP constants (`SMTP_HOST`, `SMTP_PORT`, `SMTP_SECURE`, `SMTP_USER`, `SMTP_PASS`)

### Added
- Initial site structure: hero, projects, philosophy, services, about, contact, footer
- Three project cards: Arctic Link, VoxMason, ReelGenerator — with placeholder images
- Contact form with server-side PHP handler (`contact.php`), honeypot spam protection, and rate limiting
- Centralized CSS custom properties for easy restyling
- Mobile-first responsive layout with hamburger navigation
- Sticky header with scroll-activated border
- Active section highlighting in navigation (scroll spy)
- Deployer 7 deployment configuration (`deploy.php`) based on VoxMason site approach
- `config.sample.php` — template for server-side contact form configuration
- SEO: page title, meta description, Open Graph tags, Twitter card, canonical URL
- `robots.txt` and `sitemap.xml`
- Placeholder favicon SVG
- Git repository initialized; no GitHub remote created yet
