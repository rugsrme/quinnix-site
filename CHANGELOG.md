# Changelog

## [Unreleased]

### Changed
- About section: replaced all placeholder text with Quinn Stewart's biography
  - Eyebrow subtitle: "From BASIC to AI — and Everything in Between"
  - Full personal history from 1982 TI-99/4A through Linux, web development, and AI
  - Closing tagline "From Widgets to Working Applications." styled with neon accent
  - Photo area retained as placeholder for future portrait
- CSS: added `.about-text p` spacing, `.about-tagline` neon accent style; removed unused `.about-status`
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
