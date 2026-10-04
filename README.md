# adepc.ca — PHP / MySQL edition for cPanel

The ADEPC website (Association des Églises de la Pentecôte au Canada), rebuilt to
run on ordinary cPanel hosting: **PHP 8.1+ and MySQL, no Node.js**. The design is
the one the client approved on the Next.js version, reproduced element for element.
Every word, image and video comes from the database or the media library and can be
changed in the admin panel at `/admin`.

*Version française plus bas.*

## Requirements

- PHP 8.1 or newer with `pdo_mysql`, `gd` (with WebP), `mbstring`, `fileinfo`
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `mod_rewrite` (standard on cPanel)

No Composer, Node, cron job or SSH access is needed.

## What to upload

```text
public/   → its contents go into public_html
app/      → beside public_html (not inside it), e.g. /home/USER/app
```

If your host only lets you use `public_html`, you can upload `app/` inside it
instead: `app/.htaccess` and the main `.htaccess` block web access to it.
Development folders (`tools/`, `resources/`) are never uploaded.

## Install on cPanel

1. **PHP version.** In cPanel, open *Select PHP Version* or *MultiPHP Manager* and choose 8.1 or newer. Enable `gd`, `pdo_mysql`, `mbstring` and `fileinfo` if they are unticked.
2. **Database.** Open *MySQL Databases*. Create a database and a user, then add the user to the database with **All privileges**. Note the three names and the password.
3. **Files.** Upload the contents of `public/` into `public_html`, and `app/` next to `public_html`. *File Manager* → *Upload* a zip, then *Extract*, works well.
4. **Permissions.** These folders must be writable (755 is usually enough on cPanel): `app/`, `app/storage/cache`, `app/storage/logs`, and `public_html/media` with its `images`, `video` and `icons` folders.
5. **Installer.** Visit `https://your-domain/install/`. It checks the server, asks for the database details and your first admin account, creates everything, and then locks itself. Delete the `public_html/install` folder afterwards.
6. **HTTPS.** Make sure *SSL/TLS Status* (AutoSSL) shows a certificate for the domain. Then uncomment the three "Force HTTPS" lines at the top of `public_html/.htaccess`.
7. **Email for the contact form.** Create a mailbox in *Email Accounts*, for example `site@adepc.ca`. In the admin, open **Réglages → Courriel** and enter:

   | Setting | Value |
   | --- | --- |
   | Sending method | SMTP |
   | Server | `mail.adepc.ca` (shown under *Connect Devices* in cPanel) |
   | Port / encryption | 465 / SSL |
   | User | the full address, e.g. `site@adepc.ca` |
   | Sender address | the same address |

   Then click *Send a test email*. Messages are emailed only, never stored. Until email is set up, the form opens the visitor's own email app with the message ready, as the original site did.

Keep the domain's MX records as they are. The site only needs the A/AAAA records pointed at the host.

## Daily use

Everything is edited in `/admin`. See [docs/admin-guide.md](docs/admin-guide.md) for a short walk-through for church staff.
The dashboard lists the **default values still in place**: neutral wording such as "Pasteur principal" or "Date to be announced" that should be replaced with real facts. It also lists the items that stay hidden until they are filled in, such as the social links or the YouTube channel.

## Backups

- **Database.** cPanel *Backup* or *phpMyAdmin → Export*.
- **Files.** `public_html/media` (photos, videos, icons) and `app/config.php`.

## Visitor statistics

The privacy page says visits are counted without cookies. On cPanel that means the host's built-in statistics (*Awstats* / *Visitors*), which read server logs. Google Analytics only loads after a visitor clicks "Allow", and only when a GA4 ID is set in **Réglages → Intégrations**.

## For developers

```bash
docker compose -f tools/docker/docker-compose.yml up -d --build   # PHP 8.1 + Apache + MariaDB 10.6 on :8080
tools/dev-reinstall.sh                                            # fresh database from the seed
tools/build-css.sh                                                # Tailwind 4.3.3 standalone binary, no Node
```

The public pages are PHP templates in `app/views/site`. Each one mirrors a component of the Next.js site, with the same DOM and class strings. **Templates contain no visible text:** every string, label, image and video comes from MySQL or `public/media`. Two checks enforce this rule:

```bash
php tools/lint-no-literals.php      # no text nodes / labels written in templates, helpers or scripts
tools/compare/sentinel.sh            # marks all content with "§", crawls every page, fails on any unmarked word
```

Fidelity checks against the Next.js reference. Run `pnpm build && pnpm start` in `../adepc` first:

```bash
python3 tools/compare/dom_diff.py    # element tree, attributes, classes and text of every route
node tools/compare/visual.mjs        # element boxes + pixel diff at 390/768/1024/1440/1920 px
cd tools/e2e && npx playwright test  # the original site test suite (axe, 0 px radius, overflow, redirects…)
node tools/e2e/admin.mjs             # admin scenarios, each verified on the public site
```

The seed (`app/database/seed`) was exported from the Next.js project with `tools/rebuild-seed.sh`. It holds the content, the translations, the media catalogue and the image sizes generated with GD.

Deliberate differences from the Next.js site:

- Pages load in full rather than through client-side navigation.
- Images are pre-generated WebP.
- Vercel Analytics, Speed Insights and Sentry are not used.
- The contact form keeps what the visitor typed after a validation error.
- The home film is chosen in the admin.
- The former `[TO BE ADDED]` markers now show admin-editable default values, or are hidden.

---

# adepc.ca — version PHP / MySQL pour cPanel

Le site de l'ADEPC, reconstruit pour un hébergement cPanel ordinaire : **PHP 8.1+ et
MySQL, sans Node.js**. Le design approuvé est reproduit à l'identique. Chaque texte,
image et vidéo vient de la base de données ou de la médiathèque, et se modifie dans
l'administration à `/admin`.

## Installation sur cPanel

1. **Version de PHP** : *Select PHP Version* ou *MultiPHP Manager*, choisir 8.1 ou plus. Activer `gd`, `pdo_mysql`, `mbstring` et `fileinfo` au besoin.
2. **Base de données** : *MySQL Databases*. Créer une base et un utilisateur, puis ajouter l'utilisateur à la base avec **tous les privilèges**.
3. **Fichiers** : le contenu de `public/` va dans `public_html`. Le dossier `app/` va à côté de `public_html` (ou dedans si l'hébergeur l'exige : il reste protégé).
4. **Droits d'écriture** : `app/`, `app/storage/cache`, `app/storage/logs` et `public_html/media` avec ses sous-dossiers.
5. **Installateur** : ouvrir `https://votre-domaine/install/`, remplir les informations de la base et du premier compte d'administration. Supprimer ensuite le dossier `public_html/install`.
6. **HTTPS** : vérifier le certificat dans *SSL/TLS Status* (AutoSSL), puis décommenter les trois lignes « Force HTTPS » en haut de `public_html/.htaccess`.
7. **Courriel du formulaire** : créer une boîte (ex. `site@adepc.ca`) dans *Email Accounts*. Dans l'administration, ouvrir **Réglages → Courriel** et choisir SMTP. Serveur `mail.adepc.ca`, port 465, SSL, utilisateur = l'adresse complète. Cliquer ensuite sur *Envoyer un courriel de test*. Les messages sont envoyés par courriel, jamais conservés.

Ne pas toucher aux enregistrements MX du domaine.

## Utilisation

Tout se modifie dans `/admin` : voir le [guide de l'administration](docs/admin-guide.md). Le tableau de bord liste les **valeurs par défaut encore en place**, à remplacer par les vraies informations. Il liste aussi les éléments masqués tant qu'ils ne sont pas remplis.

## Sauvegardes

Base de données : *Backup* ou *phpMyAdmin → Export*. Fichiers : `public_html/media` et `app/config.php`.
