# **CRAFT 5 BOILERPLATE**

Greetings, user.  
This is an overview of the setup process. More detailed instruction for each step can be found in the [wiki](https://github.com/paulsenmarketing/craft-five-boilerplate/wiki).

## FIRST TIME SETUP GUIDE
### 1. **Clone template** 
- (run this from your /Sites folder!)
- (make sure to replace `<new project name>` with the new repository's name!)
```
gh repo create paulsenmarketing/<new project name> --template paulsenmarketing/craft-five-boilerplate --private --clone
```
### 2. **Local database**
- create a new database using Sequel Ace
### 3. Local env
- CD into `<new project name>`
- create a local env from an example
- the .env example contains comments that can help when following the `php craft install` prompts in the next step.
```
cd <new project name>
cp .env.example.dev .env
```
### 4. **Local development**
- install Composer dependencies
- generate a new app ID
- generate a new security key
- install craft (follow the prompts!)
```
composer install
php craft setup/app-id
php craft setup/security-key
php craft install

```
### 5. **NPM**
- switch to project Node version
- install NPM packages
- build project
```
nvm use
npm install
npm run build
```
### 6. **Fortrabbit**
- set up fortrabbit project
- copy .env variables and database to Fortrabbit
- add the project as a remote
- push to fortrabbit
```
git remote add origin <fortrabbit repo url>
git push fortrabbit master
```
### 7. **Next Steps**
- Add env variables for plugins, S3 bucket storage, etc.

## **Craft Plugins**

| Plugin          | Use Case | Documentation Link |
|-----------------| ------- | ------- | 
| Anchors         | Anchor links | [Docs](https://github.com/craftcms/anchors) |
| AWS S3          | S3 storage | [Docs](https://github.com/craftcms/aws-s3) |
| Blitz           | Page caching | [Docs](https://putyourlightson.com/plugins/blitz) |
| CKEditor        | Rich text | [Docs](https://github.com/craftcms/ckeditor) |
| Embedded Assets | Embedding Videos | [Docs](https://github.com/spicywebau/craft-embedded-assets) |
| Formie          | User forms | [Docs](https://verbb.io/craft-plugins/formie/docs/get-started/installation-setup) |
| Icon Picker     | Icon selection | [Docs](https://verbb.io/craft-plugins/icon-picker/docs/get-started/installation-setup) |
| ImageOptimize   | Image optimization | [Docs](https://nystudio107.com/docs/image-optimize/) |
| Navigation      | Navigation | [Docs](https://verbb.io/craft-plugins/navigation/docs/get-started/installation-setup) |
| SEOmatic        | SEO | [Docs](https://nystudio107.com/docs/seomatic/) |
| Vite            | Vite integration | [Docs](https://nystudio107.com/docs/vite/) |
| Wordsmith       | Text utilities | [Docs](https://github.com/topshelfcraft/wordsmith) |

## **NPM Libraries**

| Plugin      | Use Case | Documentation Link |
|-------------| ------- | ------- | 
| Alpine.js   | JavaScript framework | [Docs](https://alpinejs.dev/start-here) |
| Alpine.js Plugins | Anchor, Collapse, Focus, Intersect, Mask, Persist, Resize, Sort | [Docs](https://alpinejs.dev/plugins) |
| Lucide      | Icons | [Docs](https://lucide.dev/guide/packages/lucide) |
| FontAwesome | Icons | [Docs](https://docs.fontawesome.com/web) |
| Swiper      | Carousel | [Docs](https://swiperjs.com/get-started) |
| Tailwind CSS | CSS framework | [Docs](https://tailwindcss.com/docs/) |
| Tailwind CSS Forms | Form styling | [Docs](https://github.com/tailwindlabs/tailwindcss-forms) |
| Tailwind CSS Typography | Rich text styling | [Docs](https://github.com/tailwindlabs/tailwindcss-typography) |
| Tailwind CSS Animated | Animation utilities | [Docs](https://github.com/MathiasGilson/tailwindcss-animated) |
| Tailwind CSS Intersect | Intersection observer utilities | [Docs](https://github.com/heidkaemper/tailwindcss-intersect) |
| Vanilla Tilt | Tilt effect library | [Docs](https://github.com/micku7zu/vanilla-tilt.js) |

Note that there are additional modifications. To get a full overview of the project dependencies, please review the following configuration files:

- ./package.json
- ./composer.json

For an overview on CSS in this project, please review the notes and comments in ./tailwind.config.js as well as ./src/style.css.

## **Requirements**

- **Node.js**: v24.3.0 (see `.nvmrc`)
- **PHP**: 8.2 (see `composer.json`)
- **Craft CMS**: 5.8.22 