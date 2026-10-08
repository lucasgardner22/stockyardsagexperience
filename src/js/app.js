import '../css/app.css'

if (document.querySelector('[data-carousel]')) {
    import('./carousel.js').then(({ initCarousels }) => initCarousels())
}

if (document.querySelector('[data-review-cycle]')) {
    import('./review.js').then(({ initReviewCycles }) => initReviewCycles())
}