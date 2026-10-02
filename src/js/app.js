import '../css/app.css'

if (document.querySelector('[data-carousel]')) {
    import('./carousel.js').then(({ initCarousels }) => initCarousels())
}